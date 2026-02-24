<?php
// Modules/YearlyActivity/Controllers/ActivityController.php

require_once __DIR__ . '/../Models/ActivityModel.php';
require_once __DIR__ . '/../Models/MilestoneModel.php';
require_once __DIR__ . '/../Models/CalendarModel.php';

class ActivityController
{
    private $activityModel;
    private $milestoneModel;
    private $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            \startOptimizedSession();
        }
        $this->userId = $_SESSION['user']['id'] ?? 0;
        $this->activityModel = new ActivityModel();
        $this->milestoneModel = new MilestoneModel();
    }

    /**
     * Get Activities for a Calendar
     */
    /**
     * Get Activities for a Calendar (JSON for Ajax)
     */
    public function index()
    {
        $calendarId = $_GET['calendar_id'] ?? 0;
        $activities = $this->getActivities(['calendar_id' => $calendarId]);
        echo json_encode($activities);
        exit;
    }

    /**
     * Get Activities (Array for View)
     */
    public function getActivities($filters = [])
    {
        $calendarId = $filters['calendar_id'] ?? 0;

        if ($calendarId) {
            // Check access to specific calendar
            $calModel = new CalendarModel();
            $calendar = $calModel->getCalendar($calendarId, $this->userId);
            if (!$calendar) return [];
            return $this->activityModel->getByCalendarId($calendarId);
        } else {
            // Fetch ALL activities for this user (where they are creator or calendar member)
            // For now, simpler implementation: verify logic in Model or just return empty array if we want to force Calendar view.
            // User requested "Activities" tab to work. Let's return all activities created by user for now.
            return $this->activityModel->getAllByUserId($this->userId);
        }
    }

    public function getActivityById($id)
    {
        $activity = $this->activityModel->getById($id);
        if (!$activity) return null;

        // Check permission (check if user has access to the calendar of this activity)
        $calModel = new CalendarModel();
        $calendar = $calModel->getCalendar($activity['calendar_id'], $this->userId);

        return $calendar ? $activity : null;
    }

    public function saveActivity($data)
    {
        // Permission check
        $calendarId = $data['calendar_id'] ?? 0;

        // If update, get calendar_id from existing
        if (!empty($data['id'])) {
            $existing = $this->activityModel->getById($data['id']);
            if ($existing) $calendarId = $existing['calendar_id'];
        }

        $calModel = new CalendarModel();
        $calendar = $calModel->getCalendar($calendarId, $this->userId);

        if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin', 'editor'])) {
            return ['success' => false, 'message' => 'Access Denied'];
        }

        require_once __DIR__ . '/../../../core/Security/InputSanitizer.php';

        // Prepare data map
        $dbData = [
            'calendar_id' => $calendarId,
            'name' => InputSanitizer::sanitize($data['title'] ?? $data['name']),
            'type' => InputSanitizer::sanitize($data['activity_type'] ?? $data['type']),
            'objective' => InputSanitizer::sanitize($data['objective'] ?? ''),
            'description' => InputSanitizer::sanitize($data['description'] ?? '', 'string'), // Keep string, maybe allow some html later?
            'start_date' => InputSanitizer::sanitize($data['start_date'] ?? null),
            'end_date' => InputSanitizer::sanitize($data['end_date'] ?? null),
            'location' => InputSanitizer::sanitize($data['location'] ?? ''),
            'created_by' => $this->userId
        ];

        if (!empty($data['id'])) {
            $this->activityModel->update($data['id'], $dbData);
            return ['success' => true, 'id' => $data['id']];
        } else {
            $id = $this->activityModel->create($dbData);
            return ['success' => true, 'id' => $id];
        }
    }

    public function deleteActivity($id)
    {
        $existing = $this->activityModel->getById($id);
        if (!$existing) return false;

        $calModel = new CalendarModel();
        $calendar = $calModel->getCalendar($existing['calendar_id'], $this->userId);

        if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin'])) {
            return false;
        }

        return $this->activityModel->delete($id);
    }

    // Stub for sub-activities since schema doesn't support it yet
    public function getSubActivities($parentId)
    {
        return [];
    }


    /**
     * Wizard: StepHandler
     */
    /**
     * Wizard: StepHandler
     */
    public function wizard()
    {
        $step = $_GET['step'] ?? 1;
        $id = $_GET['id'] ?? null;
        $calendarId = $_GET['calendar_id'] ?? null;

        // Security check: if calendarId provided, check access
        $calModel = new CalendarModel();
        if ($calendarId) {
            $calendar = $calModel->getCalendar($calendarId, $this->userId);
            if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin', 'editor'])) {
                die("Access Denied");
            }
        }

        // Load data if editing existing activity
        $data = [];
        if ($id) {
            $data = $this->activityModel->getById($id);
            if (!$data) die("Activity not found");
            $calendarId = $data['calendar_id']; // Ensure calendar_id matches activity
        }

        // Fetch Members for Key Person Dropdown (Available in Step 1)
        // We need to fetch members of the calendar.
        if ($calendarId) {
            $calMembers = $calModel->getMembers($calendarId);
            // Add Owner too
            $calDetails = $calModel->getCalendar($calendarId, $this->userId);
            if ($calDetails) {
                // Use details from CalendarModel instead of direct query
                $owner = [
                    'user_id' => $calDetails['owner_id'],
                    'fullname' => $calDetails['owner_name'],
                    'role' => 'owner'
                ];

                // check duplicate
                $exists = false;
                foreach ($calMembers as $cm) {
                    if ($cm['user_id'] == $owner['user_id']) $exists = true;
                }
                if (!$exists) array_unshift($calMembers, $owner);
            }
        } else {
            $calMembers = []; // Should not happen if calendar_id is required
        }

        // View will handle including specific step partials
        require __DIR__ . "/../Views/activity_wizard_view.php";
    }

    /**
     * Save Wizard Step Data
     */
    /**
     * Save Wizard Step Data
     */
    public function saveWizard()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=dashboard');
            exit;
        }

        $step = $_POST['step'] ?? 1;
        $id = $_POST['id'] ?? null;
        $calendarId = $_POST['calendar_id'] ?? null;

        // Security Check
        $calModel = new CalendarModel();

        // Validate Activity ID and Calendar ID consistency
        if ($id) {
            $existing = $this->activityModel->getById($id);
            if ($existing) {
                $calendarId = $existing['calendar_id'];
            } else {
                die('Activity not found');
            }
        }

        if (!$calendarId) die('Calendar ID missing');

        // Check Permissions
        $calendar = $calModel->getCalendar($calendarId, $this->userId);
        if (!$calendar || !in_array($calendar['user_role'], ['owner', 'admin', 'editor'])) {
            die('Access Denied');
        }

        // --- STEP SAVING LOGIC ---

        if ($step == 1) {
            // Step 1: General Info
            $data = [
                'calendar_id' => $calendarId,
                'name' => $_POST['name'],
                'type' => $_POST['type'],
                'objective' => $_POST['objective'],
                'key_person_id' => !empty($_POST['key_person_id']) ? $_POST['key_person_id'] : null,
                'status' => $_POST['status'] ?? 'planned',
                'created_by' => $this->userId
            ];

            // Handle Save as Draft (Force status proposed)
            if (isset($_POST['action_type']) && $_POST['action_type'] === 'save_draft') {
                $data['status'] = 'proposed';
            }

            if ($id) {
                $this->activityModel->update($id, $data);
            } else {
                $id = $this->activityModel->create($data);
            }
        } elseif ($step == 2) {
            // Step 2: Details
            $data = [
                'description' => $_POST['description'],
                'scope' => $_POST['scope']
            ];
            $this->activityModel->update($id, $data);
        } elseif ($step == 3) {
            // Step 3: Dates
            $data = [
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date']
            ];
            $this->activityModel->update($id, $data);
        } elseif ($step == 4) {
            // Step 4: Location
            $data = ['location' => $_POST['location']];
            $this->activityModel->update($id, $data);
        }
        // Steps 5-8 (Milestone, RASCI, Resources, Risk) are typically handled via AJAX/API calls immediately,
        // but if there were form inputs, we'd handle them here. 
        // Currently they seem to rely on the 'Next' button just moving forward.


        // --- REDIRECT LOGIC ---

        // 1. Save as Draft (Step 1 only usually) -> Calendar
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'save_draft') {
            header("Location: ?page=calendar&id=$calendarId");
            exit;
        }

        // 2. Save & Exit (Any Step) -> 5W2H Summary
        if (isset($_POST['action_type']) && $_POST['action_type'] === 'save_exit') {
            header("Location: ?page=summary_5w2h&id=$id");
            exit;
        }

        // 3. Next Step
        $nextStep = $step + 1;
        if ($nextStep > 8) {
            // Finish -> Calendar
            header("Location: ?page=calendar&id=$calendarId");
        } else {
            // Go to next step
            header("Location: ?page=activity_wizard&step=$nextStep&id=$id&calendar_id=$calendarId");
        }
        exit;
    }

    public function getMilestones($activityId)
    {
        $milestones = $this->milestoneModel->getByActivityId($activityId);
        echo json_encode($milestones);
    }

    public function addMilestone()
    {
        $activityId = $_POST['activity_id'];
        $weight = intval($_POST['weight_percent']);

        // Weight Validation
        $existingMilestones = $this->milestoneModel->getByActivityId($activityId);
        $totalWeight = 0;
        foreach ($existingMilestones as $ms) {
            $totalWeight += $ms['weight_percent'];
        }

        if (($totalWeight + $weight) > 100) {
            echo json_encode(['success' => false, 'message' => "Total weight cannot exceed 100%. Current total: $totalWeight%"]);
            return;
        }

        $data = [
            'activity_id' => $activityId,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'start_date' => str_replace('T', ' ', $_POST['start_date']),
            'due_date' => str_replace('T', ' ', $_POST['due_date']),
            'weight_percent' => $weight
        ];
        $id = $this->milestoneModel->create($data);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function updateMilestone()
    {
        $id = $_POST['id'];
        $activityId = $_POST['activity_id'];
        $weight = intval($_POST['weight_percent']);

        // Weight Validation (Exclude current milestone from sum)
        $existingMilestones = $this->milestoneModel->getByActivityId($activityId);
        $totalWeight = 0;
        foreach ($existingMilestones as $ms) {
            if ($ms['id'] != $id) {
                $totalWeight += $ms['weight_percent'];
            }
        }

        if (($totalWeight + $weight) > 100) {
            echo json_encode(['success' => false, 'message' => "Total weight cannot exceed 100%. Current other milestones total: $totalWeight%"]);
            return;
        }

        $data = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'start_date' => str_replace('T', ' ', $_POST['start_date']),
            'due_date' => str_replace('T', ' ', $_POST['due_date']),
            'weight_percent' => $weight
        ];

        if ($this->milestoneModel->update($id, $data)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    }

    public function removeMilestone()
    {
        $id = $_POST['id'];
        if ($this->milestoneModel->delete($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed']);
        }
    }

    // RASCI API
    public function getCalendarMembers()
    {
        $calendarId = $_GET['calendar_id'];
        $calModel = new CalendarModel();
        $members = $calModel->getMembers($calendarId);
        // Also include the owner
        $calendar = $calModel->getCalendar($calendarId, $this->userId);
        if ($calendar) {
            // Fetch owner details from calendar object
            $owner = [
                'user_id' => $calendar['owner_id'],
                'fullname' => $calendar['owner_name'],
                'email' => $calendar['owner_email'],
                'role' => 'owner'
            ];
            array_unshift($members, $owner);
        }
        echo json_encode($members);
    }

    public function getRasci()
    {
        $milestoneId = $_GET['milestone_id'];
        $rasci = $this->milestoneModel->getRasci($milestoneId);
        echo json_encode($rasci);
    }

    public function getAllRasci()
    {
        $activityId = $_GET['activity_id'];
        $rasci = $this->milestoneModel->getAllRasciByActivity($activityId);
        echo json_encode($rasci);
    }

    public function addRasci()
    {
        $milestoneId = $_POST['milestone_id'];
        $userId = $_POST['user_id'];
        $role = $_POST['role'];
        $id = $this->milestoneModel->addRasci($milestoneId, $userId, $role);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function removeRasci()
    {
        $id = $_POST['id'];
        $this->milestoneModel->deleteRasci($id);
        echo json_encode(['success' => true]);
    }

    // Resource API
    public function getResources()
    {
        $milestoneId = $_GET['milestone_id'];
        $data = $this->milestoneModel->getResources($milestoneId);
        echo json_encode($data);
    }

    public function addResource()
    {
        $data = [
            'milestone_id' => $_POST['milestone_id'],
            'resource_name' => $_POST['resource_name'],
            'quantity' => $_POST['quantity'],
            'unit_cost' => $_POST['unit_cost'],
            'unit' => $_POST['unit']
        ];
        $id = $this->milestoneModel->addResource($data);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function removeResource()
    {
        $id = $_POST['id'];
        $this->milestoneModel->deleteResource($id);
        echo json_encode(['success' => true]);
    }

    // Risk API
    public function getRisks()
    {
        $milestoneId = $_GET['milestone_id'];
        $data = $this->milestoneModel->getRisks($milestoneId);
        echo json_encode($data);
    }

    public function addRisk()
    {
        $data = [
            'milestone_id' => $_POST['milestone_id'],
            'risk_description' => $_POST['risk_description'],
            'impact' => $_POST['impact'],
            'probability' => $_POST['probability'],
            'mitigation_plan' => $_POST['mitigation_plan']
        ];
        $id = $this->milestoneModel->addRisk($data);
        echo json_encode(['success' => true, 'id' => $id]);
    }

    public function removeRisk()
    {
        $id = $_POST['id'];
        $this->milestoneModel->deleteRisk($id);
        echo json_encode(['success' => true]);
    }

    // 5W2H Summary Generation
    public function summary5w2h($id)
    {
        $summary = $this->activityModel->get5w2hSummary($id);
        if (!$summary) {
            echo "Activity not found";
            return;
        }

        // Pre-extract variables for the legacy view template
        $activity = $summary['Activity'];
        $milestones = $summary['Milestones'];
        $rasciByMs = $summary['RasciMap'];
        $involvedPeople = $summary['InvolvedPeople'];
        $milestoneRoleMatrix = $summary['RoleMatrix'];
        $milestoneLogsMap = $summary['MilestoneLogs'];
        $milestoneAttachmentsMap = $summary['MilestoneAttachments'];
        $comments = $summary['Comments'];
        $logs = $summary['Logs'];
        $averageEvaluation = $summary['AverageEvaluation'] ?? null;

        // Find if current user has already rated this activity
        $userRating = null;
        if ($averageEvaluation && !empty($averageEvaluation['feedbacks'])) {
            foreach ($averageEvaluation['feedbacks'] as $fb) {
                if (($fb['user_id'] ?? 0) == $this->userId) {
                    $userRating = $fb;
                    break;
                }
            }
        }

        // If feedbacks don't have user_id, we might need to fetch it differently or add it to the query

        // Permission Check for View
        $calModel = new CalendarModel();
        $calendar = $calModel->getCalendar($activity['calendar_id'], $this->userId);
        $role = $calendar['user_role'] ?? 'viewer';
        $canEdit = in_array($role, ['owner', 'admin', 'editor']);

        require __DIR__ . '/../Views/summary_5w2h.php';
    }

    public function updateMilestoneStatus()
    {
        header('Content-Type: application/json');

        $msId = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $actualStartDate = $_POST['actual_start_date'] ?? null;
        $actualEndDate = $_POST['actual_end_date'] ?? null;

        if (!$msId || !$status) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        // Get RASCI for this milestone
        $rasci = $this->milestoneModel->getRasci($msId);

        // Check permission: Must be 'R' (Responsible)
        $canUpdate = false;
        foreach ($rasci as $r) {
            if ($r['user_id'] == $this->userId && $r['role'] === 'R') {
                $canUpdate = true;
                break;
            }
        }

        if (!$canUpdate) {
            echo json_encode(['success' => false, 'message' => 'Permission denied. Only the person responsible (R) can update this milestone.']);
            return;
        }


        // Fetch old status for logging
        $oldMilestone = $this->milestoneModel->getById($msId);
        $oldMsStatus = $oldMilestone['status'];
        $activityId = $oldMilestone['activity_id'];
        $activity = $this->activityModel->getById($activityId);
        $activityStatus = $activity['status'];

        $note = $_POST['note'] ?? '';

        $data = [
            'status' => $status,
            'actual_start_date' => $actualStartDate && $actualStartDate !== '' ? $actualStartDate : null,
            'actual_end_date' => $actualEndDate && $actualEndDate !== '' ? $actualEndDate : null
        ];

        if ($this->milestoneModel->update($msId, $data)) {
            // Log History if changed or note exists
            if ($oldMsStatus !== $status || !empty($note)) {
                // New Dedicated Milestone Logging
                $this->milestoneModel->logHistory(
                    $msId,
                    $oldMsStatus,
                    $status,
                    $note,
                    $this->userId,
                    // Ensure nulls are passed if empty string
                    $actualStartDate && $actualStartDate !== '' ? $actualStartDate : null,
                    $actualEndDate && $actualEndDate !== '' ? $actualEndDate : null
                );

                // Keep Activity Log for high-level visibility (Optional, but good for summary)
                $statusText = str_replace('_', ' ', $status);
                $oldStatusText = str_replace('_', ' ', $oldMsStatus);
                $logMsg = "Milestone '{$oldMilestone['name']}' updated: $oldStatusText -> $statusText.";

                $this->activityModel->logHistory(
                    $activityId,
                    $activityStatus,
                    $activityStatus,
                    $logMsg,
                    $this->userId
                );
            }

            // Check for auto-completion
            $this->checkAndAutoCompleteActivity($msId);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    }

    private function checkAndAutoCompleteActivity($milestoneId)
    {
        // 1. Get Activity ID from Milestone
        $ms = $this->milestoneModel->getById($milestoneId);
        if (!$ms) return;
        $activityId = $ms['activity_id'];

        // 2. Get all milestones for this activity
        $milestones = $this->milestoneModel->getByActivityId($activityId);

        // 3. Check if ALL are completed
        $allCompleted = true;
        foreach ($milestones as $m) {
            if ($m['status'] !== 'completed') {
                $allCompleted = false;
                break;
            }
        }

        if ($allCompleted) {
            // Auto-complete activity
            // Use changeStatus to ensure logging
            $this->activityModel->changeStatus(
                $activityId,
                'completed',
                'Auto-completed by System: All milestones are marked as completed.',
                $this->userId // Or 0 for system
            );
        }
    }

    public function changeActivityStatus()
    {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? '';
        $note = $_POST['note'] ?? '';
        $actualStart = $_POST['actual_start_date'] ?? null;
        $actualEnd = $_POST['actual_end_date'] ?? null;

        if (!$id || !$status) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            return;
        }

        // Prevent manual 'completed' status
        if ($status === 'completed') {
            echo json_encode(['success' => false, 'message' => 'Cannot set to Completed manually. All milestones must be completed first.']);
            return;
        }

        if ($this->activityModel->changeStatus($id, $status, $note, $this->userId)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
    }

    public function rateActivity()
    {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? 0;
        $scores = [
            'quality' => $_POST['score_quality'] ?? null,
            'timeliness' => $_POST['score_timeliness'] ?? null,
            'impact' => $_POST['score_impact'] ?? null
        ];
        $note = $_POST['note'] ?? '';

        if (!$id || $scores['quality'] === null || $scores['timeliness'] === null || $scores['impact'] === null) {
            echo json_encode(['success' => false, 'message' => 'Missing evaluation scores']);
            return;
        }

        // Permission check
        $activity = $this->activityModel->getById($id);
        if (!$activity) {
            echo json_encode(['success' => false, 'message' => 'Activity not found']);
            return;
        }

        $calModel = new CalendarModel();
        $calendar = $calModel->getCalendar($activity['calendar_id'], $this->userId);
        $role = $calendar['user_role'] ?? 'viewer';

        if (!in_array($role, ['owner', 'admin'])) {
            echo json_encode(['success' => false, 'message' => 'Only Owner or Admin can rate the activity']);
            return;
        }

        if ($this->activityModel->saveIndividualEvaluation($id, $this->userId, $scores, $note)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save evaluation']);
        }
    }

    /* ========================== ATTACHMENTS ========================== */

    public function uploadAttachment()
    {
        header('Content-Type: application/json');

        $milestoneId = $_POST['milestone_id'] ?? 0;
        if (!$milestoneId || empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }

        // Permission check
        $milestone = $this->milestoneModel->getById($milestoneId);
        if (!$milestone) {
            echo json_encode(['success' => false, 'message' => 'Milestone not found']);
            exit;
        }

        // Upload path
        $uploadDir = __DIR__ . '/../../../public/uploads/yearly_activity/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $file = $_FILES['file'];
        $fileName = time() . '_' . basename($file['name']);
        $targetFile = $uploadDir . $fileName;
        $relPath = 'public/uploads/yearly_activity/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $data = [
                'milestone_id' => $milestoneId,
                'file_name' => $file['name'],
                'file_path' => $relPath,
                'file_type' => $file['type'],
                'uploaded_by' => $this->userId
            ];
            $id = $this->milestoneModel->addAttachment($data);
            echo json_encode(['success' => true, 'id' => $id, 'file_name' => $file['name'], 'file_path' => $relPath]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
        }
        exit;
    }

    public function deleteAttachment()
    {
        header('Content-Type: application/json');
        $id = $_POST['id'] ?? 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID missing']);
            exit;
        }

        $relPath = $this->milestoneModel->deleteAttachment($id);
        if ($relPath) {
            $fullPath = __DIR__ . '/../../../' . $relPath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Attachment not found']);
        }
        exit;
    }

    /* ========================== COMMENTS ========================== */

    public function addComment()
    {
        header('Content-Type: application/json');
        $activityId = $_POST['activity_id'] ?? 0;
        $text = $_POST['comment_text'] ?? '';

        if (!$activityId || empty(trim($text))) {
            echo json_encode(['success' => false, 'message' => 'Missing data']);
            exit;
        }

        $data = [
            'activity_id' => $activityId,
            'user_id' => $this->userId,
            'comment_text' => $text
        ];

        $id = $this->activityModel->addComment($data);
        if ($id) {
            $mentionedUserIds = [];
            $allMembersMentioned = false;

            // Fetch summary first to map names to IDs
            $summary = $this->activityModel->get5w2hSummary($activityId);
            $involvedPeople = $summary['InvolvedPeople'] ?? [];

            // Check for @All Members
            if (stripos($text, '@All Members') !== false) {
                $allMembersMentioned = true;
                foreach ($involvedPeople as $uid => $name) {
                    $mentionedUserIds[] = (int)$uid;
                }
            } else {
                // Parse individual @Name mentions
                foreach ($involvedPeople as $uid => $name) {
                    // Check if "@Name" exists in the text. Add boundary checks or simple string pos
                    $searchName = '@' . $name;
                    if (stripos($text, $searchName) !== false) {
                        $mentionedUserIds[] = (int)$uid;
                    }
                }
            }

            // Unique and exclude self
            $mentionedUserIds = array_unique($mentionedUserIds);

            require_once __DIR__ . '/../../../core/Services/NotificationService.php';
            $currentUser = $_SESSION['user']['fullname'] ?? 'Someone';
            $link = "Modules/YearlyActivity/index.php?page=summary_5w2h&id={$activityId}";

            foreach ($mentionedUserIds as $targetUid) {
                if ($targetUid == $this->userId) continue;

                \NotificationService::create(
                    $targetUid,
                    'info',
                    'มีการพูดถึงคุณในกิจกรรม',
                    "{$currentUser} กล่าวถึงคุณในกิจกรรม: " . ($summary['What'] ?? "#{$activityId}"),
                    ['activity_id' => $activityId],
                    $link
                );
            }

            echo json_encode([
                'success' => true,
                'id' => $id,
                'user_name' => $currentUser,
                'comment_text' => $data['comment_text'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
        exit;
    }
}
