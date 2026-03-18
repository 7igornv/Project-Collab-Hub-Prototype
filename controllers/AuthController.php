<?php
require_once __DIR__ . '/../config/jwt.php';

class AuthController {
    private $user;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function register() {
        $data = json_decode(file_get_contents("php://input"));

        if(!isset($data->email) || !isset($data->password) || !isset($data->full_name)) {
            $this->sendResponse(['status' => 'error', 'message' => 'Все поля обязательны'], 400);
            return;
        }

        $this->user->email = $data->email;
        if($this->user->emailExists()) {
            $this->sendResponse(['status' => 'error', 'message' => 'Email уже зарегистрирован'], 400);
            return;
        }

        $this->user->role_id = $data->role_id ?? 1;
        $this->user->password_hash = $data->password;
        $this->user->full_name = $data->full_name;
        $this->user->hourly_rate = $data->hourly_rate ?? null;

        if($this->user->create()) {
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Регистрация успешна',
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'full_name' => $this->user->full_name
                ]
            ], 201);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Ошибка регистрации'], 500);
        }
    }

    public function login() {
        $data = json_decode(file_get_contents("php://input"));

        if(!isset($data->email) || !isset($data->password)) {
            $this->sendResponse(['status' => 'error', 'message' => 'Email и пароль обязательны'], 400);
            return;
        }

        $this->user->email = $data->email;
        if(!$this->user->emailExists()) {
            $this->sendResponse(['status' => 'error', 'message' => 'Неверный email или пароль'], 401);
            return;
        }

        if(password_verify($data->password, $this->user->password_hash)) {
            $token = JWT::generateToken($this->user->id, $this->user->email, $this->user->role_id);
            
            $this->sendResponse([
                'status' => 'success',
                'message' => 'Вход выполнен',
                'data' => [
                    'id' => $this->user->id,
                    'email' => $this->user->email,
                    'full_name' => $this->user->full_name,
                    'role_id' => $this->user->role_id,
                    'token' => $token
                ]
            ]);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Неверный email или пароль'], 401);
        }
    }

    public function me() {
        $headers = getallheaders();
        $token = null;
        
        if(isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }
        
        if(!$token) {
            $this->sendResponse(['status' => 'error', 'message' => 'Требуется авторизация'], 401);
            return;
        }
        
        $payload = JWT::verifyToken($token);
        if(!$payload) {
            $this->sendResponse(['status' => 'error', 'message' => 'Недействительный токен'], 401);
            return;
        }
        
        $userData = $this->user->getById($payload['user_id']);
        if($userData) {
            $this->sendResponse([
                'status' => 'success',
                'data' => $userData
            ]);
        } else {
            $this->sendResponse(['status' => 'error', 'message' => 'Пользователь не найден'], 404);
        }
    }

    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>