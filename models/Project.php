<?php
class Project {
    private $conn;
    private $table = 'projects';

    public $id;
    public $client_id;
    public $status_id;
    public $title;
    public $description;
    public $budget;
    public $deadline;
    public $is_deleted;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function read($page = 1, $limit = 10, $skill = null, $search = null) {
        $offset = ($page - 1) * $limit;

        $query = "SELECT p.*, u.full_name as client_name,
                         GROUP_CONCAT(s.skill_name) as skills
                  FROM " . $this->table . " p
                  JOIN users u ON p.client_id = u.id
                  LEFT JOIN project_skills ps ON p.id = ps.project_id
                  LEFT JOIN skills s ON ps.skill_id = s.id
                  WHERE p.is_deleted = FALSE ";

        if($skill) {
            $query .= " AND p.id IN (
                        SELECT project_id FROM project_skills ps2
                        JOIN skills s2 ON ps2.skill_id = s2.id
                        WHERE s2.skill_name = :skill
                      )";
        }

        if($search) {
            $query .= " AND p.title LIKE :search";
        }

        $query .= " GROUP BY p.id
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

        $stmt = $this->conn->prepare($query);

        if($skill) $stmt->bindParam(':skill', $skill);
        if($search) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(':search', $searchTerm);
        }
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT p.*, u.full_name as client_name,
                         GROUP_CONCAT(s.skill_name) as skills
                  FROM " . $this->table . " p
                  JOIN users u ON p.client_id = u.id
                  LEFT JOIN project_skills ps ON p.id = ps.project_id
                  LEFT JOIN skills s ON ps.skill_id = s.id
                  WHERE p.id = :id AND p.is_deleted = FALSE
                  GROUP BY p.id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserProjects($user_id, $role = 'client') {
        $query = "SELECT p.*, u.full_name as client_name,
                         GROUP_CONCAT(s.skill_name) as skills
                  FROM " . $this->table . " p
                  JOIN users u ON p.client_id = u.id
                  LEFT JOIN project_skills ps ON p.id = ps.project_id
                  LEFT JOIN skills s ON ps.skill_id = s.id
                  WHERE p.is_deleted = FALSE ";
        
        if($role === 'client') {
            $query .= " AND p.client_id = :user_id";
        } else {
            $query .= " AND p.id IN (SELECT project_id FROM teams WHERE user_id = :user_id)";
        }
        
        $query .= " GROUP BY p.id ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  SET client_id = :client_id,
                      title = :title,
                      description = :description,
                      budget = :budget,
                      deadline = :deadline,
                      published_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':budget', $this->budget);
        $stmt->bindParam(':deadline', $this->deadline);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET title = :title,
                      description = :description,
                      budget = :budget,
                      deadline = :deadline
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':title', $data->title);
        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':budget', $data->budget);
        $stmt->bindParam(':deadline', $data->deadline);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function softDelete($id) {
        $query = "UPDATE " . $this->table . "
                  SET is_deleted = TRUE
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function addSkills($skillIds) {
        if(empty($skillIds)) return true;
        
        $query = "INSERT INTO project_skills (project_id, skill_id) VALUES ";
        $values = [];
        foreach($skillIds as $skillId) {
            $values[] = "({$this->id}, {$skillId})";
        }
        $query .= implode(',', $values);

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }

    public function updateSkills($projectId, $skillIds) {
        // Сначала удаляем старые навыки
        $query = "DELETE FROM project_skills WHERE project_id = :project_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':project_id', $projectId);
        $stmt->execute();
        
        // Добавляем новые
        if(empty($skillIds)) return true;
        
        $query = "INSERT INTO project_skills (project_id, skill_id) VALUES ";
        $values = [];
        foreach($skillIds as $skillId) {
            $values[] = "({$projectId}, {$skillId})";
        }
        $query .= implode(',', $values);

        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>