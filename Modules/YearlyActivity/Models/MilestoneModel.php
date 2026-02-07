<?php
// Modules/YearlyActivity/Models/MilestoneModel.php

require_once __DIR__ . '/../../../core/Database/Database.php';

class MilestoneModel
{
    private $conn;

    public function __construct()
    {
        $db = new \Database();
        $this->conn = $db->getConnection();
    }

    /* ========================== MILESTONES ========================== */

    public function getById($id)
    {
        $sql = "SELECT * FROM ya_milestones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByActivityId($activityId)
    {
        $sql = "SELECT m.*, 
                (SELECT COUNT(*) FROM ya_milestone_risks WHERE milestone_id = m.id) as risk_count
                FROM ya_milestones m
                WHERE m.activity_id = :activity_id
                ORDER BY m.order_index ASC, m.due_date ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':activity_id', $activityId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $sql = "INSERT INTO ya_milestones (activity_id, name, description, start_date, due_date, weight_percent, order_index) 
                VALUES (:activity_id, :name, :description, :start_date, :due_date, :weight_percent, :order_index)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':activity_id' => $data['activity_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':due_date' => $data['due_date'] ?? null,
            ':weight_percent' => $data['weight_percent'] ?? 0,
            ':order_index' => $data['order_index'] ?? 0
        ]);
        return $this->conn->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        $allowed = ['name', 'description', 'start_date', 'due_date', 'status', 'weight_percent', 'order_index'];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) return false;

        $sql = "UPDATE ya_milestones SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id)
    {
        // Cascade delete will handle related tables if configured, but good to be explicit if needed.
        // Relying on FK ON DELETE CASCADE as per schema.
        $sql = "DELETE FROM ya_milestones WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ========================== RASCI ========================== */

    public function getRasci($milestoneId)
    {
        $sql = "SELECT r.*, u.fullname, u.username, u.email 
                FROM ya_milestone_rasci r
                JOIN users u ON r.user_id = u.id
                WHERE r.milestone_id = :milestone_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':milestone_id', $milestoneId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRasciByActivity($activityId)
    {
        $sql = "SELECT r.*, m.id as milestone_id, u.fullname, u.username, u.email 
                FROM ya_milestone_rasci r
                JOIN ya_milestones m ON r.milestone_id = m.id
                JOIN users u ON r.user_id = u.id
                WHERE m.activity_id = :activity_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':activity_id', $activityId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRasci($milestoneId, $userId, $role)
    {
        $sql = "INSERT INTO ya_milestone_rasci (milestone_id, user_id, role) 
                VALUES (:milestone_id, :user_id, :role)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':milestone_id' => $milestoneId, ':user_id' => $userId, ':role' => $role]);
        return $this->conn->lastInsertId();
    }

    public function clearRasci($milestoneId)
    {
        $sql = "DELETE FROM ya_milestone_rasci WHERE milestone_id = :milestone_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':milestone_id', $milestoneId);
        return $stmt->execute();
    }

    public function deleteRasci($id)
    {
        $sql = "DELETE FROM ya_milestone_rasci WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ========================== RESOURCES ========================== */

    public function getResources($milestoneId)
    {
        $sql = "SELECT * FROM ya_milestone_resources WHERE milestone_id = :milestone_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':milestone_id', $milestoneId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addResource($data)
    {
        $sql = "INSERT INTO ya_milestone_resources (milestone_id, resource_name, quantity, unit_cost, unit) 
                VALUES (:milestone_id, :resource_name, :quantity, :unit_cost, :unit)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':milestone_id' => $data['milestone_id'],
            ':resource_name' => $data['resource_name'],
            ':quantity' => $data['quantity'] ?? 1,
            ':unit_cost' => $data['unit_cost'] ?? 0,
            ':unit' => $data['unit'] ?? null
        ]);
        return $this->conn->lastInsertId();
    }

    public function deleteResource($id)
    {
        $sql = "DELETE FROM ya_milestone_resources WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ========================== RISKS ========================== */

    public function getRisks($milestoneId)
    {
        $sql = "SELECT * FROM ya_milestone_risks WHERE milestone_id = :milestone_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':milestone_id', $milestoneId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRisksByActivity($activityId)
    {
        $sql = "SELECT r.*, m.name as milestone_name 
                FROM ya_milestone_risks r
                JOIN ya_milestones m ON r.milestone_id = m.id
                WHERE m.activity_id = :activity_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':activity_id', $activityId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addRisk($data)
    {
        $sql = "INSERT INTO ya_milestone_risks (milestone_id, risk_description, impact, probability, mitigation_plan) 
                VALUES (:milestone_id, :risk_description, :impact, :probability, :mitigation_plan)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':milestone_id' => $data['milestone_id'],
            ':risk_description' => $data['risk_description'],
            ':impact' => $data['impact'] ?? 3, // Default medium
            ':probability' => $data['probability'] ?? 3,
            ':mitigation_plan' => $data['mitigation_plan'] ?? null
        ]);
        return $this->conn->lastInsertId();
    }

    public function deleteRisk($id)
    {
        $sql = "DELETE FROM ya_milestone_risks WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /* ========================== CALENDAR AGGREGATES ========================== */

    public function getAllRasciByCalendar($calendarId)
    {
        // Join Calendar -> Activities -> Milestones -> RASCI -> Users
        $sql = "SELECT r.*, m.id as milestone_id, a.id as activity_id, a.name as activity_name, m.name as milestone_name, u.fullname, u.username, u.email
                FROM ya_milestone_rasci r
                JOIN ya_milestones m ON r.milestone_id = m.id
                JOIN ya_activities a ON m.activity_id = a.id
                JOIN users u ON r.user_id = u.id
                WHERE a.calendar_id = :calendar_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRisksByCalendar($calendarId)
    {
        // Join Calendar -> Activities -> Milestones -> Risks
        $sql = "SELECT r.*, m.name as milestone_name, a.id as activity_id, a.name as activity_name
                FROM ya_milestone_risks r
                JOIN ya_milestones m ON r.milestone_id = m.id
                JOIN ya_activities a ON m.activity_id = a.id
                WHERE a.calendar_id = :calendar_id
                ORDER BY (r.impact * r.probability) DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /* ========================== LOGS ========================== */

    public function logHistory($milestoneId, $previousStatus, $newStatus, $note, $changedBy, $actualStartDate = null, $actualEndDate = null)
    {
        $sql = "INSERT INTO ya_milestone_logs (milestone_id, previous_status, new_status, note, changed_by, actual_start_date, actual_end_date, changed_at) 
                VALUES (:mid, :prev, :new, :note, :by, :start, :end, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':mid' => $milestoneId,
            ':prev' => $previousStatus,
            ':new' => $newStatus,
            ':note' => $note,
            ':by' => $changedBy,
            ':start' => $actualStartDate,
            ':end' => $actualEndDate
        ]);
        return $this->conn->lastInsertId();
    }

    public function getLogs($milestoneId)
    {
        $sql = "SELECT l.*, u.fullname as changed_by_name 
                FROM ya_milestone_logs l
                LEFT JOIN users u ON l.changed_by = u.id
                WHERE l.milestone_id = :mid 
                ORDER BY l.changed_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':mid', $milestoneId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
