<?php
// Modules/YearlyActivity/Models/CalendarModel.php

require_once __DIR__ . '/../../../core/Database/Database.php';

class CalendarModel
{
    private $conn;

    public function __construct()
    {
        $db = new \Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Get all calendars accessible by a user (Owner or Member)
     */
    public function getUserCalendars($userId)
    {
        $sql = "SELECT DISTINCT c.*, u.fullname as owner_name, u.email as owner_email,
                CASE 
                    WHEN c.owner_id = :user_id THEN 'owner'
                    ELSE cm.role 
                END as user_role,
                (SELECT COUNT(*) FROM ya_activities WHERE calendar_id = c.id) as activity_count
                FROM ya_calendars c
                JOIN users u ON c.owner_id = u.id
                LEFT JOIN ya_calendar_members cm ON c.id = cm.calendar_id
                WHERE c.owner_id = :user_id OR cm.user_id = :user_id
                ORDER BY c.year DESC, c.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get specific calendar if user has access
     */
    public function getCalendar($id, $userId)
    {
        $sql = "SELECT c.*, u.fullname as owner_name, u.email as owner_email,
                CASE 
                    WHEN c.owner_id = :user_id THEN 'owner'
                    ELSE cm.role 
                END as user_role
                FROM ya_calendars c
                JOIN users u ON c.owner_id = u.id
                LEFT JOIN ya_calendar_members cm ON c.id = cm.calendar_id
                WHERE c.id = :id AND (c.owner_id = :user_id OR cm.user_id = :user_id)
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new calendar
     */
    public function create($name, $year, $ownerId, $description = null)
    {
        $sql = "INSERT INTO ya_calendars (name, year, description, owner_id) VALUES (:name, :year, :description, :owner_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':owner_id', $ownerId);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Update calendar
     */
    public function update($id, $name, $year, $status, $description = null)
    {
        $sql = "UPDATE ya_calendars SET name = :name, year = :year, status = :status, description = :description WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Delete calendar (Cascades to activities etc due to FK)
     */
    public function delete($id)
    {
        $sql = "DELETE FROM ya_calendars WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Get members of a calendar
     */
    public function getMembers($calendarId)
    {
        $sql = "SELECT m.*, u.fullname, u.email, u.username, u.Level3Name as department 
                FROM ya_calendar_members m
                JOIN users u ON m.user_id = u.id
                WHERE m.calendar_id = :calendar_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add member to calendar
     */
    public function addMember($calendarId, $userId, $role)
    {
        $sql = "INSERT INTO ya_calendar_members (calendar_id, user_id, role) 
                VALUES (:calendar_id, :user_id, :role)
                ON DUPLICATE KEY UPDATE role = :role_update";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':role_update', $role);
        return $stmt->execute();
    }

    /**
     * Remove member
     */
    public function removeMember($calendarId, $userId)
    {
        $sql = "DELETE FROM ya_calendar_members WHERE calendar_id = :calendar_id AND user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':calendar_id', $calendarId);
        $stmt->bindParam(':user_id', $userId);
        return $stmt->execute();
    }
}
