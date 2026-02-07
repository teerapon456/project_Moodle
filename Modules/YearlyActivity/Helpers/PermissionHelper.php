<?php

/**
 * Permission Helper for Yearly Activity Module
 * 
 * Permission Levels:
 * - VIEW: Can only see activities they're involved in (RASCI assignment) or created
 * - EDIT: Can create activities and update progress on their own activities
 * - MANAGE: Can see and manage all activities (Admin/Manager)
 */

class YAPermissionHelper
{
    private static $instance = null;
    private $userId;
    private $userRole;
    private $permissionLevel; // 'view', 'edit', 'manage'

    private function __construct()
    {
        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = strtolower($_SESSION['user']['role'] ?? '');

        // Determine permission level based on role
        if (in_array($this->userRole, ['admin', 'superadmin', 'manager'])) {
            $this->permissionLevel = 'manage';
        } elseif (in_array($this->userRole, ['editor', 'staff', 'user'])) {
            $this->permissionLevel = 'edit';
        } else {
            $this->permissionLevel = 'view';
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    public function getPermissionLevel()
    {
        return $this->permissionLevel;
    }

    // Check functions
    public function canManage()
    {
        return $this->permissionLevel === 'manage';
    }

    public function canEdit()
    {
        return in_array($this->permissionLevel, ['edit', 'manage']);
    }

    public function canView()
    {
        return true; // Everyone can at least view their own
    }

    // Check if user can access specific activity
    public function canAccessActivity($activity)
    {
        if ($this->canManage()) {
            return true;
        }

        // User can access if they created it
        if (isset($activity['created_by']) && $activity['created_by'] == $this->userId) {
            return true;
        }

        return false;
    }

    // Check if user can edit specific activity
    public function canEditActivity($activity)
    {
        if ($this->canManage()) {
            return true;
        }

        if (!$this->canEdit()) {
            return false;
        }

        // Can edit if they created it
        if (isset($activity['created_by']) && $activity['created_by'] == $this->userId) {
            return true;
        }

        return false;
    }

    // Build WHERE clause for activity queries
    public function getActivityFilterSQL($tableAlias = 'a', $rasciAlias = 'r')
    {
        if ($this->canManage()) {
            return ''; // No filter needed
        }

        $userId = intval($this->userId);

        return "AND (
            {$tableAlias}.created_by = {$userId} 
            OR {$tableAlias}.id IN (SELECT activity_id FROM ya_rasci WHERE user_id = {$userId})
        )";
    }

    // Get activities user has access to
    public function filterActivities($activities)
    {
        if ($this->canManage()) {
            return $activities;
        }

        return array_filter($activities, function ($act) {
            // User can see if they created it or have RASCI assignment
            return ($act['created_by'] ?? null) == $this->userId;
        });
    }
}
