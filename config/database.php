<?php
class Database {
    private $host = 'MySQL-8.0'; // Имя модуля MySQL в OpenServer
    private $db_name = 'project_collab';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
            exit;
        }

        return $this->conn;
    }
}
?>