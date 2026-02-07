<?php

require_once __DIR__ . '/IGABaseController.php';

class AdminController extends IGABaseController
{
    /**
     * Dashboard Page
     */
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view'); // Or 'admin' depending on strictness

        // Mock stats for now
        $stats = [
            'total_tests' => 0,
            'active_tests' => 0,
            'total_attempts' => 0
        ];

        // Should fetch real stats from DB later

        $this->render('admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats
        ]);
    }
}
