<?php
require_once __DIR__ . '/../config/jwt.php';

class BidController {
    private $bid;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->bid = new Bid($db);
    }

    // POST /api/projects/{id}/bid - откликнуться на проект
    public function createProjectBid($projectId) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        // Проверяем роль (только developer)
        $user = new User($this->db);
        $userData = $user->getById($userId);
        if($userData['role_name'] !== 'developer') {
            $this->sendResponse(['status' => 'error', 'message' => 'Только исполнители могут откликаться'], 403);
            return;
        }

        // ПРОВЕРЯЕМ, НЕТ ЛИ УЖЕ ОТКЛИКА
        $checkQuery = "SELECT id FROM bids WHERE project_id = :project_id AND developer_id = :developer_id AND status_id = 1";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':project_id', $projectId);
        $checkStmt->bindParam(':developer_id', $userId);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            $this->sendResponse(['status' => 'error', 'message' => 'Вы уже откликались на этот проект'], 400);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        $bid = new Bid($this->db);
        if($bid->createProjectBid($projectId, $userId, $data->budget ?? null, $data->letter ?? '')) {
            $this->sendResponse(['status' => 'success', 'message' => 'Отклик отправлен']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка при создании отклика'], 500);
        }
    }

    // GET /api/bids/my - получить отклики текущего пользователя
    public function my() {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $filter = $_GET['filter'] ?? 'all';
        
        $query = "SELECT b.*, 
                        p.title as project_title,
                        bs.status_name
                FROM bids b
                JOIN projects p ON b.project_id = p.id
                JOIN bid_statuses bs ON b.status_id = bs.id
                WHERE b.developer_id = :user_id";
        
        if($filter !== 'all') {
            $query .= " AND bs.status_name = :filter";
        }
        
        $query .= " ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if($filter !== 'all') {
            $stmt->bindParam(':filter', $filter);
        }
        
        $stmt->execute();
        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendResponse(['status' => 'success', 'data' => $bids]);
    }

    // GET /api/projects/{id}/bids - получить отклики на проект
    // GET /api/projects/{id}/bids - получить отклики на проект
    // GET /api/projects/{id}/bids - получить отклики на проект
    public function getProjectBids($projectId) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $project = new Project($this->db);
        $projectData = $project->readOne($projectId);
        
        if(!$projectData || $projectData['client_id'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет доступа к откликам'], 403);
            return;
        }

        $query = "SELECT b.*, 
                        u.full_name as developer_name,
                        bs.status_name,
                        p.is_deleted as project_deleted
                FROM bids b
                JOIN users u ON b.developer_id = u.id
                JOIN bid_statuses bs ON b.status_id = bs.id
                JOIN projects p ON b.project_id = p.id
                WHERE b.project_id = :project_id
                ORDER BY b.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->execute();
        
        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->sendResponse(['status' => 'success', 'data' => $bids]);
    }

    // POST /api/bids/{id}/accept - принять отклик
    public function accept($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $bidData = $this->bid->getById($id);
        if(!$bidData) {
            $this->sendResponse(['status' => 'error', 'message' => 'Отклик не найден'], 404);
            return;
        }

        // Проверяем, что пользователь - владелец проекта
        if($bidData['client_id'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет прав для этого действия'], 403);
            return;
        }

        if($this->bid->updateStatus($id, 2)) { // 2 = accepted
            $this->sendResponse(['status' => 'success', 'message' => 'Отклик принят']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка'], 500);
        }
    }

    // POST /api/bids/{id}/reject - отклонить отклик
    public function reject($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $bidData = $this->bid->getById($id);
        if(!$bidData) {
            $this->sendResponse(['status' => 'error', 'message' => 'Отклик не найден'], 404);
            return;
        }

        if($bidData['client_id'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет прав для этого действия'], 403);
            return;
        }

        if($this->bid->updateStatus($id, 3)) { // 3 = rejected
            $this->sendResponse(['status' => 'success', 'message' => 'Отклик отклонен']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка'], 500);
        }
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        if(isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            $payload = JWT::verifyToken($token);
            if($payload) {
                return $payload['user_id'];
            }
        }
        return null;
    }

    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // POST /api/tasks/{id}/bids - откликнуться на задачу
    public function createTaskBid($taskId) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        $bid = new Bid($this->db);
        if($bid->createTaskBid($taskId, $userId, $data->budget ?? null, $data->letter ?? '')) {
            $this->sendResponse(['status' => 'success', 'message' => 'Отклик на задачу отправлен']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка'], 500);
        }
    }
}
?>