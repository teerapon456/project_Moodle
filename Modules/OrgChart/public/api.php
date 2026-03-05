<?php
ini_set('display_errors', 0); // Prevent HTML warnings from breaking the JSON response
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';
require_once __DIR__ . '/../../../core/Database/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    $sessionConfig = __DIR__ . '/../../../core/Config/SessionConfig.php';
    if (file_exists($sessionConfig)) {
        require_once $sessionConfig;
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            session_start();
        }
    } else {
        session_start();
    }
}

if (empty($_SESSION['user'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'session_expired', 'message' => 'Session expired. Please log in again.']);
    exit;
}

$action = $_GET['action'] ?? '';

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Action is required.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    if ($action === 'get-tree') {
        // Fetch all active positions with headcounts
        $sql = "
            SELECT p.PositionID as id, 
                   p.PositionName as title, 
                   p.ReportTo as parent_id,
                   o.OrgUnitName as department,
                   (SELECT COUNT(u.id) FROM users u WHERE u.PositionID = p.PositionID AND u.is_active = 1 AND u.user_id != '1111') as headcount
            FROM core_positions p
            LEFT JOIN (SELECT DISTINCT OrgUnitID, OrgUnitName FROM users WHERE OrgUnitID IS NOT NULL OR OrgUnitName IS NOT NULL LIMIT 1000) o ON FALSE -- (We'll optimize department later if needed, users may not have OrgUnitID reliably)
            WHERE p.IsInactive = 0 AND p.IsDeleted = 0
            ORDER BY headcount DESC, p.PositionName ASC
        ";

        // Let's refine the query: users table has OrgUnitName which we might want to attach to the position
        $sql = "
            SELECT p.PositionID as id, 
                   p.PositionName as title, 
                   p.PositionCode as code,
                   p.ReportTo as parent_id,
                   (SELECT COUNT(u.id) FROM users u WHERE u.PositionID = p.PositionID AND u.is_active = 1 AND u.user_id != '1111') as headcount
            FROM core_positions p
            WHERE p.IsInactive = 0 AND p.IsDeleted = 0
        ";

        $stmt = $conn->query($sql);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tree = [];
        $mapped = [];

        // First pass: put items into a map
        foreach ($positions as $pos) {
            $mapped[$pos['id']] = $pos;
        }

        // Determine valid IDs to check for orphans
        $validIds = array_keys($mapped);
        $validIdsMap = array_flip($validIds);

        // Flatten back out and re-link orphans to ROOT
        $flatData = [];

        // Synthesize ROOT node for disjointed graphs
        $flatData[] = [
            'id' => 'ROOT',
            'title' => 'INTEQC Group',
            'code' => 'บริษัท',
            'parentId' => '',
            'headcount' => 0
        ];

        foreach ($mapped as $id => $node) {
            $parentId = $node['parent_id'];
            if (empty($parentId) || !isset($validIdsMap[$parentId])) {
                $parentId = 'ROOT';
            }

            $flatData[] = [
                'id' => $node['id'],
                'title' => $node['title'],
                'code' => $node['code'],
                'parentId' => $parentId,
                'headcount' => $node['headcount']
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $flatData]);
        exit;
    }

    if ($action === 'get-personnel') {
        $positionId = $_GET['position'] ?? '';
        if (!$positionId) {
            echo json_encode(['success' => false, 'error' => 'Position ID is required.']);
            exit;
        }

        $sql = "
            SELECT user_id as id, 
                   fullname, 
                   email, 
                   OrgUnitName as department,
                   Level3Name as section,
                   PositionName,
                   BirthDate,
                   StartDate
            FROM users 
            WHERE PositionID = ? AND is_active = 1 AND user_id != '1111'
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$positionId]);
        $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $personnel]);
        exit;
    }

    if ($action === 'get-export-excel') {
        // Fetch ALL positions regardless of IsInactive or IsDeleted
        $sql = "
            SELECT p.PositionID as id, 
                   p.PositionName as title, 
                   p.PositionCode as code,
                   p.ReportTo as parent_id,
                   p.IsInactive,
                   p.IsDeleted
            FROM core_positions p
            ORDER BY p.PositionName ASC
        ";
        $stmt = $conn->query($sql);
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch ALL users and their relations to positions
        $sqlUsers = "
            SELECT user_id as id, 
                   fullname, 
                   email, 
                   OrgUnitName as department,
                   PositionID,
                   is_active
            FROM users 
            WHERE user_id != '1111'
        ";
        $stmtUsers = $conn->query($sqlUsers);
        $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        // Map users by PositionID
        $usersByPosition = [];
        foreach ($users as $u) {
            $pid = $u['PositionID'];
            if (!$pid) continue;
            if (!isset($usersByPosition[$pid])) {
                $usersByPosition[$pid] = [];
            }
            $usersByPosition[$pid][] = $u;
        }

        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=Organization_Chart_Export_' . date('Y-m-d_H-i-s') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Headers
        fputcsv($output, ['Position ID', 'Position Code', 'Position Name', 'Report To (Parent ID)', 'Position Status', 'Employee ID', 'Employee Name', 'Email', 'Department', 'Employee Active Status']);

        foreach ($positions as $pos) {
            $status = [];
            if ($pos['IsInactive']) $status[] = 'Inactive';
            if ($pos['IsDeleted']) $status[] = 'Deleted';
            $posStatus = empty($status) ? 'Active' : implode(', ', $status);

            if (isset($usersByPosition[$pos['id']])) {
                foreach ($usersByPosition[$pos['id']] as $user) {
                    $userStatus = $user['is_active'] ? 'Active' : 'Inactive';
                    fputcsv($output, [
                        $pos['id'],
                        $pos['code'] ?? '',
                        $pos['title'],
                        $pos['parent_id'] ?? '',
                        $posStatus,
                        $user['id'],
                        $user['fullname'],
                        $user['email'] ?? '',
                        $user['department'] ?? '',
                        $userStatus
                    ]);
                }
            } else {
                // Position with no users
                fputcsv($output, [
                    $pos['id'],
                    $pos['code'] ?? '',
                    $pos['title'],
                    $pos['parent_id'] ?? '',
                    $posStatus,
                    '', // Employee ID
                    '', // Employee Name
                    '', // Email
                    '', // Department
                    ''  // Employee Active Status
                ]);
            }
        }

        fclose($output);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
