<?php
// Modules/YearlyActivity/Models/ActivityModel.php

require_once __DIR__ . '/../../../core/Database/Database.php';

class ActivityModel
{
    private $conn;

    public function __construct()
    {
        $db = new \Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Get activities for a calendar
     */
    public function getByCalendarId($calendarId)
    {
        // Auto-update statuses based on date
        $this->updateActivityStatuses($calendarId);

        $sql = "SELECT a.*, 
                (SELECT COUNT(*) FROM ya_milestones WHERE activity_id = a.id) as milestone_count,
                u.fullname as created_by_name,
                kp.fullname as key_person_name
                FROM ya_activities a
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN users kp ON a.key_person_id = kp.id
                WHERE a.calendar_id = :calendar_id
                ORDER BY a.start_date ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all milestones for this calendar to calculate progress
        // Join to filter by calendar
        $msSql = "SELECT m.activity_id, m.weight_percent, m.status 
                  FROM ya_milestones m 
                  JOIN ya_activities a ON m.activity_id = a.id 
                  WHERE a.calendar_id = :calendar_id";
        $msStmt = $this->conn->prepare($msSql);
        $msStmt->execute([':calendar_id' => $calendarId]);
        $allMilestones = $msStmt->fetchAll(PDO::FETCH_ASSOC);

        // Group milestones by activity
        $msGrouped = [];
        foreach ($allMilestones as $ms) {
            $msGrouped[$ms['activity_id']][] = $ms;
        }

        // Calculate Progress
        foreach ($activities as &$act) {
            $milestones = $msGrouped[$act['id']] ?? [];

            if (empty($milestones)) {
                $act['progress'] = 0;
                continue;
            }

            $totalDefinedWeight = 0;
            $unweightedCount = 0;

            foreach ($milestones as $m) {
                if ($m['weight_percent'] > 0) {
                    $totalDefinedWeight += $m['weight_percent'];
                } else {
                    $unweightedCount++;
                }
            }

            // Logic:
            // 1. Defined weights contribute their standard value
            // 2. Remaining (100 - totalDefined) is distributed among unweighted
            // 3. If totalDefined > 100, normalize? (Simplest: Cap at 100, unweighted get 0)

            $remainingWeight = max(0, 100 - $totalDefinedWeight);
            $weightPerUnweighted = ($unweightedCount > 0) ? ($remainingWeight / $unweightedCount) : 0;

            $currentProgress = 0;
            foreach ($milestones as $m) {
                $isCompleted = ($m['status'] === 'completed');
                if ($isCompleted) {
                    if ($m['weight_percent'] > 0) {
                        $currentProgress += $m['weight_percent'];
                    } else {
                        $currentProgress += $weightPerUnweighted;
                    }
                }
            }

            $act['progress'] = min(100, round($currentProgress));
        }

        return $activities;
    }

    private function updateActivityStatuses($calendarId)
    {
        $today = date('Y-m-d');

        // 1. Set to In Progress if currently within range
        $sql = "UPDATE ya_activities 
                SET status = 'in_progress' 
                WHERE calendar_id = :cid 
                AND start_date <= :today 
                AND end_date >= :today 
                AND status NOT IN ('completed', 'cancelled', 'on_hold')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':cid' => $calendarId, ':today' => $today]);

        // 2. Set to Incoming (Planned) if in future
        $sql = "UPDATE ya_activities 
                SET status = 'incoming' 
                WHERE calendar_id = :cid 
                AND start_date > :today 
                AND status NOT IN ('completed', 'cancelled', 'on_hold')";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':cid' => $calendarId, ':today' => $today]);

        // 3. Optional: Set to Completed if past end date? 
        // User didn't explicitly ask for auto-complete, but normally if it's over, it's over.
        // However, auto-completing might be annoying if they haven't actually finished.
        // I will leave auto-complete OUT for now, as 'In Progress' is the main concern ("Is it arrived yet?").
    }

    /**
     * Get all activities created by a user
     */
    public function getAllByUserId($userId)
    {
        $sql = "SELECT a.*, c.name as calendar_name, u.fullname as created_by_name
                FROM ya_activities a
                JOIN ya_calendars c ON a.calendar_id = c.id
                LEFT JOIN users u ON a.created_by = u.id
                WHERE a.created_by = :user_id OR c.owner_id = :user_id
                ORDER BY a.start_date DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAccessibleByUserId($userId)
    {
        // Fetch activities where user is Owner of Calendar OR Member of Calendar
        $sql = "SELECT a.*, c.name as calendar_name, u.fullname as created_by_name
                FROM ya_activities a
                JOIN ya_calendars c ON a.calendar_id = c.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN ya_calendar_members m ON c.id = m.calendar_id AND m.user_id = :user_id
                WHERE c.owner_id = :user_id OR m.user_id = :user_id
                ORDER BY a.start_date ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvolvedByUserId($userId)
    {
        // Fetch activities where user is involved (Created, Key Person, or RASCI)
        $sql = "SELECT DISTINCT a.*, c.name as calendar_name, u.fullname as created_by_name
                FROM ya_activities a
                JOIN ya_calendars c ON a.calendar_id = c.id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN ya_milestones m ON a.id = m.activity_id
                LEFT JOIN ya_milestone_rasci r ON m.id = r.milestone_id
                WHERE a.created_by = :user_id 
                   OR a.key_person_id = :user_id 
                   OR r.user_id = :user_id
                ORDER BY a.start_date ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activity by ID
     */
    public function getById($id)
    {
        $sql = "SELECT a.*, u.fullname as key_person_name 
                FROM ya_activities a
                LEFT JOIN users u ON a.key_person_id = u.id
                WHERE a.id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create Activity (Basic info from Step 1)
     */
    public function create($data)
    {
        $sql = "INSERT INTO ya_activities 
                (calendar_id, name, type, objective, description, scope, start_date, end_date, location, key_person_id, created_by) 
                VALUES 
                (:calendar_id, :name, :type, :objective, :description, :scope, :start_date, :end_date, :location, :key_person_id, :created_by)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':calendar_id' => $data['calendar_id'],
            ':name' => $data['name'],
            ':type' => $data['type'] ?? null,
            ':objective' => $data['objective'] ?? null,
            ':description' => $data['description'] ?? null,
            ':scope' => $data['scope'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
            ':location' => $data['location'] ?? null,
            ':key_person_id' => $data['key_person_id'] ?? null,
            ':created_by' => $data['created_by']
        ]);

        return $this->conn->lastInsertId();
    }

    /**
     * Update Activity
     */
    public function update($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            // Allow list of updateable fields
            if (in_array($key, ['name', 'type', 'objective', 'description', 'scope', 'start_date', 'end_date', 'location', 'key_person_id'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE ya_activities SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete Activity
     */
    public function delete($id)
    {
        $sql = "DELETE FROM ya_activities WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    /**
     * Change Activity Status and Log it
     */
    public function changeStatus($id, $newStatus, $note = null, $changedBy = null)
    {
        $activity = $this->getById($id);
        if (!$activity) return false;

        $oldStatus = $activity['status'];

        // Update status in main table
        $sql = "UPDATE ya_activities SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':status' => $newStatus, ':id' => $id]);

        // Log history
        $logSql = "INSERT INTO ya_activity_logs (activity_id, previous_status, new_status, note, changed_by, changed_at) 
                   VALUES (:aid, :old, :new, :note, :by, NOW())";
        $logStmt = $this->conn->prepare($logSql);
        $logStmt->execute([
            ':aid' => $id,
            ':old' => $oldStatus,
            ':new' => $newStatus,
            ':note' => $note,
            ':by' => $changedBy
        ]);

        return true;
    }

    public function logHistory($activityId, $oldStatus, $newStatus, $note, $changedBy)
    {
        $logSql = "INSERT INTO ya_activity_logs (activity_id, previous_status, new_status, note, changed_by, changed_at) 
                   VALUES (:aid, :old, :new, :note, :by, NOW())";
        $logStmt = $this->conn->prepare($logSql);
        $logStmt->execute([
            ':aid' => $activityId,
            ':old' => $oldStatus,
            ':new' => $newStatus,
            ':note' => $note,
            ':by' => $changedBy
        ]);
    }

    /**
     * Get Activity Logs
     */
    public function getLogs($activityId)
    {
        $sql = "SELECT l.*, u.fullname as changed_by_name 
                FROM ya_activity_logs l
                LEFT JOIN users u ON l.changed_by = u.id
                WHERE l.activity_id = :aid 
                ORDER BY l.changed_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':aid' => $activityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all users involved in RASCI for an activity
     */
    public function getRasciUsers($activityId)
    {
        $sql = "SELECT DISTINCT user_id FROM ya_rasci WHERE activity_id = :aid AND user_id > 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':aid' => $activityId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
