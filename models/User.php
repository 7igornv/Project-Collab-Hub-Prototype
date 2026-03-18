<?php
class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $role_id;
    public $email;
    public $password_hash;
    public $full_name;
    public $avatar_url;
    public $bio;
    public $hourly_rate;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET role_id = :role_id,
                      email = :email,
                      password_hash = :password_hash,
                      full_name = :full_name,
                      hourly_rate = :hourly_rate";

        $stmt = $this->conn->prepare($query);

        $this->password_hash = password_hash($this->password_hash, PASSWORD_DEFAULT);

        $stmt->bindParam(':role_id', $this->role_id);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password_hash', $this->password_hash);
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':hourly_rate', $this->hourly_rate);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id, email, password_hash, full_name, role_id
                  FROM " . $this->table . "
                  WHERE email = :email
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->full_name = $row['full_name'];
            $this->role_id = $row['role_id'];
            $this->password_hash = $row['password_hash'];
            return true;
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT u.*, r.role_name 
                  FROM " . $this->table . " u
                  JOIN roles r ON u.role_id = r.id
                  WHERE u.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>