<?php
class Bid {
    private $conn;
    private $table = 'bids';

    public $id;
    public $project_id;
    public $task_id;
    public $developer_id;
    public $status_id;
    public $proposed_budget;
    public $cover_letter;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Создание отклика на проект
    public function createProjectBid($project_id, $developer_id, $proposed_budget, $cover_letter) {
        // Проверяем, существует ли уже отклик
        $checkQuery = "SELECT id FROM " . $this->table . " WHERE project_id = :project_id AND developer_id = :developer_id AND status_id = 1";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':project_id', $project_id);
        $checkStmt->bindParam(':developer_id', $developer_id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return false; // Отклик уже существует
        }
        
        $query = "INSERT INTO " . $this->table . " 
                (project_id, developer_id, status_id, proposed_budget, cover_letter, created_at)
                VALUES (:project_id, :developer_id, 1, :proposed_budget, :cover_letter, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->bindParam(':developer_id', $developer_id);
        $stmt->bindParam(':proposed_budget', $proposed_budget);
        $stmt->bindParam(':cover_letter', $cover_letter);
        
        return $stmt->execute();
    }

    // Создание отклика на задачу
    public function createTaskBid($task_id, $developer_id, $proposed_budget, $cover_letter) {
    // Сначала получаем project_id из задачи
    $taskQuery = "SELECT project_id FROM tasks WHERE id = :task_id";
    $taskStmt = $this->conn->prepare($taskQuery);
    $taskStmt->bindParam(':task_id', $task_id);
    $taskStmt->execute();
    $task = $taskStmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$task) return false;
    
    $query = "INSERT INTO " . $this->table . " 
              (project_id, task_id, developer_id, status_id, proposed_budget, cover_letter, created_at)
              VALUES (:project_id, :task_id, :developer_id, 1, :proposed_budget, :cover_letter, NOW())";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':project_id', $task['project_id']);
    $stmt->bindParam(':task_id', $task_id);
    $stmt->bindParam(':developer_id', $developer_id);
    $stmt->bindParam(':proposed_budget', $proposed_budget);
    $stmt->bindParam(':cover_letter', $cover_letter);
    
    return $stmt->execute();
    }

    // Получить отклики на проект
    public function getProjectBids($project_id) {
        $query = "SELECT b.*, u.full_name as developer_name, bs.status_name
                  FROM " . $this->table . " b
                  JOIN users u ON b.developer_id = u.id
                  JOIN bid_statuses bs ON b.status_id = bs.id
                  WHERE b.project_id = :project_id
                  ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $project_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получить отклик по ID
    public function getById($id) {
        $query = "SELECT b.*, p.client_id
                  FROM " . $this->table . " b
                  JOIN projects p ON b.project_id = p.id
                  WHERE b.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Обновить статус отклика
    public function updateStatus($id, $status_id) {
        $query = "UPDATE " . $this->table . "
                  SET status_id = :status_id
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
}
?>