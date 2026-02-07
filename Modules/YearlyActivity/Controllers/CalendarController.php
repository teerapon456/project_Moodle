<?php
// Modules/YearlyActivity/Controllers/CalendarController.php

require_once __DIR__ . '/../Models/CalendarModel.php';
require_once __DIR__ . '/../Models/ActivityModel.php';
require_once __DIR__ . '/../Models/MilestoneModel.php';
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';

class CalendarController
{
    private $model;
    private $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            \startOptimizedSession();
        }
        $this->userId = $_SESSION['user']['id'] ?? 0;
        $this->model = new CalendarModel();
    }

    /**
     * Get all calendars for the user (Returns Array)
     */
    public function getUserCalendars()
    {
        return $this->model->getUserCalendars($this->userId);
    }

    /**
     * Store a new calendar
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=dashboard');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $description = $_POST['description'] ?? '';

        if (!empty($name)) {
            $this->model->create($name, $year, $this->userId, $description);
        }

        header('Location: ?page=dashboard');
        exit;
    }

    /**
     * Delete a calendar
     */
    public function delete($id)
    {
        $calendar = $this->model->getCalendar($id, $this->userId);
        if ($calendar && $calendar['user_role'] === 'owner') {
            $this->model->delete($id);
        }
        header('Location: ?page=dashboard');
        exit;
    }

    /**
     * Show a specific calendar
     */
    public function show($id)
    {
        $calendar = $this->model->getCalendar($id, $this->userId);

        if (!$calendar) {
            echo "Access Denied or Calendar Not Found";
            return;
        }

        // Fetch Activities
        $activityModel = new ActivityModel();
        $activities = $activityModel->getByCalendarId($id);

        // Fetch Aggregated Data for Matrices
        $milestoneModel = new MilestoneModel();
        $allRisks = $milestoneModel->getAllRisksByCalendar($id);
        $allRasci = $milestoneModel->getAllRasciByCalendar($id);
        $calendarMembers = $this->model->getMembers($id);

        // Fetch Owner for Members List (if not redundant)
        $owner = null;
        if ($calendar['owner_id']) {
            $db = new \Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id as user_id, fullname FROM users WHERE id = ?");
            $stmt->execute([$calendar['owner_id']]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($owner) {
                $owner['role'] = 'owner';
                $exists = false;
                foreach ($calendarMembers as $mem) {
                    if ($mem['user_id'] == $owner['user_id']) $exists = true;
                }
                if (!$exists) array_unshift($calendarMembers, $owner);
            }
        }

        require __DIR__ . '/../Views/calendar_view.php';
    }

    /**
     * Show Settings Page
     */
    public function settings($id)
    {
        $calendar = $this->model->getCalendar($id, $this->userId);

        if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin'])) {
            echo "Access Denied";
            return;
        }

        $members = $this->model->getMembers($id);

        require __DIR__ . '/../Views/calendar_settings_view.php';
    }

    /**
     * Update Calendar Details
     */
    public function update()
    {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $year = $_POST['year'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'active';

        // Security check
        $calendar = $this->model->getCalendar($id, $this->userId);
        if (!$calendar || $calendar['user_role'] !== 'owner') {
            // Only owner can rename/change year
            die('Access Denied');
        }

        $this->model->update($id, $name, $year, $status, $description);
        header("Location: ?page=calendar_settings&id=$id&msg=updated");
        exit;
    }

    /**
     * Add Member
     */
    public function addMember()
    {
        $calendarId = $_POST['calendar_id'];
        $email = $_POST['email'];
        $role = $_POST['role'];

        // Security Check
        $calendar = $this->model->getCalendar($calendarId, $this->userId);
        if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin'])) {
            die('Access Denied');
        }

        // Check if user exists (need a user lookup in model, or just try insert)
        // ideally we look up user by email to get ID
        // For now, let's look up user ID from email directly here using DB (quick fix)
        // or add method to model. Let's add lookup to model.

        $db = new \Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if user is the Owner
            if ($user['id'] == $calendar['owner_id']) {
                header("Location: ?page=calendar_settings&id=$calendarId&error=cannot_add_owner");
                exit;
            }

            // Check if user is already a member
            $members = $this->model->getMembers($calendarId);
            foreach ($members as $member) {
                if ($member['id'] == $user['id']) {
                    header("Location: ?page=calendar_settings&id=$calendarId&error=already_member");
                    exit;
                }
            }

            $this->model->addMember($calendarId, $user['id'], $role);
            header("Location: ?page=calendar_settings&id=$calendarId&msg=member_added");
        } else {
            header("Location: ?page=calendar_settings&id=$calendarId&error=user_not_found");
        }
        exit;
    }

    /**
     * Remove Member
     */
    public function removeMember()
    {
        $calendarId = $_POST['calendar_id'];
        $userId = $_POST['user_id'];

        // Security Check
        $calendar = $this->model->getCalendar($calendarId, $this->userId);
        if (!$calendar || $calendar['user_role'] !== 'owner') {
            die('Access Denied: Only Owner can remove members');
        }

        // Prevent removing self or owner
        if ($userId == $calendar['owner_id']) {
            die("Cannot remove owner");
        }

        $this->model->removeMember($calendarId, $userId);
        header("Location: ?page=calendar_settings&id=$calendarId&msg=member_removed");
        exit;
    }
}
