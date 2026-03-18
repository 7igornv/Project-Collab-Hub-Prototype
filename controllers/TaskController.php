<?php
require_once __DIR__ . '/../config/jwt.php';

class TaskController {
    private $task;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->task = new Task($db);
    }

    public function index() {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $filter = $_GET['filter'] ?? 'all';
        $tasks = $this->task->getUserTasks($userId, $filter);
        
        $this->sendResponse(['status' => 'success', 'data' => $tasks]);
    }

    public function available() {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $filters = [];
        if(isset($_GET['priority'])) $filters['priority'] = $_GET['priority'];
        if(isset($_GET['project_id'])) $filters['project_id'] = $_GET['project_id'];

        $tasks = $this->task->getAvailableTasks($filters);
        
        $this->sendResponse(['status' => 'success', 'data' => $tasks]);
    }

    public function show($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $task = $this->task->getById($id);
        
        if($task) {
            $this->sendResponse(['status' => 'success', 'data' => $task]);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Задача не найдена'], 404);
        }
    }

    public function update($id) {
        $userId = $this->getUserIdFromToken();
        if(!$userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));
        $task = $this->task->getById($id);

        if(!$task) {
            $this->sendResponse(['status' => 'error', 'message' => 'Задача не найдена'], 404);
            return;
        }

        if($task['created_by'] != $userId && $task['assigned_to'] != $userId) {
            $this->sendResponse(['status' => 'error', 'message' => 'Нет доступа'], 403);
            return;
        }

        if(isset($data->status)) {
            $statusMap = ['todo' => 1, 'in_progress' => 2, 'review' => 3, 'done' => 4];
            $status_id = $statusMap[$data->status] ?? 1;
            
            if($this->task->updateStatus($id, $status_id)) {
                $this->sendResponse(['status' => 'success', 'message' => 'Статус обновлен']);
            } else {
                $this->sendResponse(['status' => 'error', 'message' => 'Ошибка обновления'], 500);
            }
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