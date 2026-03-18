<?php
class Task {
    private $conn;
    private $table = 'tasks';

    public $id;
    public $project_id;
    public $created_by;
    public $assigned_to;
    public $status_id;
    public $title;
    public $description;
    public $priority;
    public $position;
    public $deadline;
    public $is_deleted;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserTasks($user_id, $filter = 'all') {
        $query = "SELECT t.*, 
                         p.title as project_title,
                         ts.status_name,
                         u.full_name as assignee_name
                  FROM " . $this->table . " t
                  JOIN projects p ON t.project_id = p.id
                  JOIN task_statuses ts ON t.status_id = ts.id
                  LEFT JOIN users u ON t.assigned_to = u.id
                  WHERE t.assigned_to = :user_id AND t.is_deleted = FALSE";
        
        if($filter !== 'all') {
            $query .= " AND ts.status_name = :filter";
        }
        
        $query .= " ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if($filter !== 'all') {
            $stmt->bindParam(':filter', $filter);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableTasks($filters = []) {
        $query = "SELECT t.*, 
                         p.title as project_title,
                         u.full_name as creator_name
                  FROM " . $this->table . " t
                  JOIN projects p ON t.project_id = p.id
                  JOIN users u ON t.created_by = u.id
                  WHERE t.assigned_to IS NULL 
                    AND t.status_id = 1
                    AND t.is_deleted = FALSE";
        
        if(isset($filters['priority'])) {
            $query .= " AND t.priority = :priority";
        }
        
        if(isset($filters['project_id'])) {
            $query .= " AND t.project_id = :project_id";
        }
        
        $query .= " ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if(isset($filters['priority'])) {
            $stmt->bindParam(':priority', $filters['priority']);
        }
        
        if(isset($filters['project_id'])) {
            $stmt->bindParam(':project_id', $filters['project_id']);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT t.*, 
                         p.title as project_title,
                         ts.status_name,
                         creator.full_name as creator_name,
                         assignee.full_name as assignee_name
                  FROM " . $this->table . " t
                  JOIN projects p ON t.project_id = p.id
                  JOIN task_statuses ts ON t.status_id = ts.id
                  JOIN users creator ON t.created_by = creator.id
                  LEFT JOIN users assignee ON t.assigned_to = assignee.id
                  WHERE t.id = :id AND t.is_deleted = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStatus($id, $status_id) {
        $query = "UPDATE " . $this->table . "
                  SET status_id = :status_id,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_id', $status_id);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function assignUser($id, $user_id) {
        $query = "UPDATE " . $this->table . "
                  SET assigned_to = :user_id,
                      status_id = 2,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }
}
?>