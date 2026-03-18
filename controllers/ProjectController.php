<?php
require_once __DIR__ . '/../config/jwt.php';

class ProjectController {
    private $project;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->project = new Project($db);
    }

    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $skill = isset($_GET['skill']) ? $_GET['skill'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
        
        $stmt = $this->project->read($page, $limit, $skill, $search);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Сортировка
        if($sort === 'budget_asc') {
            usort($projects, function($a, $b) {
                return $a['budget'] <=> $b['budget'];
            });
        } elseif($sort === 'budget_desc') {
            usort($projects, function($a, $b) {
                return $b['budget'] <=> $a['budget'];
            });
        }

        $this->sendResponse([
            'status' => 'success',
            'data' => $projects
        ]);
    }

    public function show($id) {
        $project = $this->project->readOne($id);

        if($project) {
            $this->sendResponse(['status' => 'success', 'data' => $project]);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Проект не найден'], 404);
        }
    }

   // GET /api/projects/my - проекты текущего пользователя
    public function my() {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $user = new User($this->db);
        $userData = $user->getById($userId);
        $role = $userData['role_name'];

        $projects = $this->project->getUserProjects($userId, $role);
        
        // Добавляем количество откликов для каждого проекта
        foreach($projects as &$project) {
            $bidQuery = "SELECT COUNT(*) as count FROM bids WHERE project_id = :project_id";
            $bidStmt = $this->db->prepare($bidQuery);
            $bidStmt->bindParam(':project_id', $project['id']);
            $bidStmt->execute();
            $bidResult = $bidStmt->fetch(PDO::FETCH_ASSOC);
            $project['bids_count'] = $bidResult['count'];
        }
        
        $this->sendResponse(['status' => 'success', 'data' => $projects]);
    }

    public function store() {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if(!isset($data->title) || !isset($data->description)) {
            $this->sendResponse(['status' => 'error', 'message' => 'Название и описание обязательны'], 400);
            return;
        }

        $this->project->client_id = $userId;
        $this->project->title = $data->title;
        $this->project->description = $data->description;
        $this->project->budget = $data->budget ?? null;
        $this->project->deadline = $data->deadline ?? null;

        if($this->project->create()) {
            if(isset($data->skills) && is_array($data->skills)) {
                $this->project->addSkills($data->skills);
            }
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Проект создан',
                'data' => ['id' => $this->project->id]
            ], 201);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка создания проекта'], 500);
        }
    }

    public function update($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $project = $this->project->readOne($id);
        if(!$project) {
            $this->sendResponse(['status' => 'error', 'message' => 'Проект не найден'], 404);
            return;
        }

        if($project['client_id'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет прав для редактирования'], 403);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        
        if($this->project->update($id, $data)) {
            if(isset($data->skills) && is_array($data->skills)) {
                $this->project->updateSkills($id, $data->skills);
            }
            
            $this->sendResponse(['status' => 'success', 'message' => 'Проект обновлен']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка обновления'], 500);
        }
    }

    public function delete($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $project = $this->project->readOne($id);
        if(!$project) {
            $this->sendResponse(['status' => 'error', 'message' => 'Проект не найден'], 404);
            return;
        }

        if($project['client_id'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет прав для удаления'], 403);
            return;
        }

        if($this->project->softDelete($id)) {
            $this->sendResponse(['status' => 'success', 'message' => 'Проект удален']);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка удаления'], 500);
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
}
?>