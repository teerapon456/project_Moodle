<?php
// Modules/YearlyActivity/Controllers/DashboardController.php

require_once __DIR__ . '/../Models/CalendarModel.php';
require_once __DIR__ . '/../Models/ActivityModel.php';

class DashboardController
{
    private $calendarModel;
    private $activityModel;
    private $userId;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (function_exists('startOptimizedSession')) {
                \startOptimizedSession();
            } else {
                session_start();
            }
        }
        $this->userId = $_SESSION['user']['id'] ?? 0;
        $this->calendarModel = new CalendarModel();
        $this->activityModel = new ActivityModel();
    }

    public function overview()
    {
        $stats = [
            'calendars' => count($this->calendarModel->getUserCalendars($this->userId)),
            'activities' => 0
        ];

        $upcoming = $this->activityModel->getInvolvedByUserId($this->userId);
        $stats['activities'] = count($upcoming);

        // Analytics: Status Distribution
        $statusCounts = [];
        foreach ($upcoming as $act) {
            $s = $act['status'] ?? 'unknown';
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }

        // Analytics: Monthly Volume - REMOVE THIS (Redundant with Workload?)
        // Actually keep logic clean: Workload (R-role) is distinct from ALL activities volume if requested.
        // But the previous code removed monthly_volume returning, so let's stick to workload_by_calendar.

        // Analytics: Workload by Calendar (Where role is 'R')
        $db = new \Database(); // Need direct DB access for complex join
        $conn = $db->getConnection();

        // USER REQUEST: Count MILESTONES where user is 'R', grouped by Calendar
        $sql = "
            SELECT 
                c.name as calendar_name,
                MONTH(m.start_date) as month,
                COUNT(DISTINCT m.id) as count
            FROM ya_activities a
            JOIN ya_calendars c ON a.calendar_id = c.id
            JOIN ya_milestones m ON a.id = m.activity_id
            JOIN ya_milestone_rasci r ON m.id = r.milestone_id
            WHERE r.user_id = :user_id 
            AND r.role = 'R'
            AND m.start_date IS NOT NULL
            GROUP BY c.name, MONTH(m.start_date)
            ORDER BY c.name, month
        ";

        $stmt = $conn->prepare($sql);
        $stmt->execute([':user_id' => $this->userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process into Chart datasets (Calendar => [0..11])
        $workloadData = [];
        foreach ($rows as $r) {
            $calName = $r['calendar_name'];
            $m = (int)$r['month'];
            // month is 1-12. Array index 0-11
            $idx = max(0, min(11, $m - 1));

            if (!isset($workloadData[$calName])) {
                $workloadData[$calName] = array_fill(0, 12, 0);
            }
            $workloadData[$calName][$idx] = (int)$r['count'];
        }

        // Apply array_values to inner arrays to ensure purely indexed arrays
        foreach ($workloadData as $calName => $data) {
            $workloadData[$calName] = array_values($data);
        }

        return [
            'stats' => $stats,
            'upcoming' => array_slice($upcoming, 0, 10), // Top 10
            'analytics' => [
                'status_distribution' => $statusCounts,
                'workload_by_calendar' => $workloadData // Now purely arrays
            ]
        ];
    }

    public function index()
    {
        $calendars = $this->calendarModel->getUserCalendars($this->userId);

        // Pass data to view
        // The view file content will assume $calendars is available
        return $calendars;
    }

    // Keep existing stats methods but adapted? 
    // The previous implementation queried tables directly. 
    // We can keep them if tables are compatible, but tables changed names/structure.
    // user wants 'ya_activities' etc. Schema created 'ya_activities'. 
    // Old controller: "SELECT COUNT(*) FROM ya_activities" (Gets global count? insecure?)
    // New requirement: "User sees only calendars they own or are member of".

    // Updated getStats to respect permissions via Calendars
    public function getStats()
    {
        // Complex query to get stats for all accessible calendars
        // Simplified: just count total calendars for now
        $calendars = $this->calendarModel->getUserCalendars($this->userId);
        $totalCalendars = count($calendars);
        $totalActivities = 0;
        foreach ($calendars as $cal) {
            $totalActivities += $cal['activity_count'];
        }

        return [
            'total_calendars' => $totalCalendars,
            'total_activities' => $totalActivities
        ];
    }
}
