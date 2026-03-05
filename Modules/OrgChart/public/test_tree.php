<?php
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Fetch all active positions with headcounts
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

    // Build the tree
    $tree = [];
    $mapped = [];

    // First pass: put items into a map
    foreach ($positions as $pos) {
        $mapped[$pos['id']] = $pos;
        $mapped[$pos['id']]['children'] = [];
    }

    // Determine valid IDs to check for orphans
    $validIds = array_column($mapped, 'id');
    $validIdsMap = array_flip($validIds);

    // Third pass: Flatten back out and re-link orphans to ROOT
    $flatData = [];
    
    // Synthesize ROOT node
    $rootNode = [
        'id' => 'ROOT',
        'title' => 'INTEQC Group',
        'code' => 'บริษัท',
        'parent_id' => '',
        'headcount' => 0,
        'total_headcount' => 0,
        'children' => []
    ];

    foreach ($mapped as $id => &$node) {
        $parentId = $node['parent_id'];
        if (empty($parentId) || !isset($validIdsMap[$parentId])) {
            $node['parent_id'] = 'ROOT';
            $rootNode['children'][] = &$node;
        } else {
            $mapped[$parentId]['children'][] = &$node;
        }
    }
    
    // Calculate total headcount recursively
    function calcHeadcount(&$node)
    {
        $total = (int)$node['headcount'];
        foreach ($node['children'] as &$child) {
            $total += calcHeadcount($child);
        }
        $node['total_headcount'] = $total;
        return $total;
    }
    
    calcHeadcount($rootNode);
    
    $flatData[] = [
        'id' => $rootNode['id'],
        'title' => $rootNode['title'],
        'code' => $rootNode['code'],
        'parentId' => $rootNode['parent_id'],
        'headcount' => $rootNode['headcount'],
        'total_headcount' => $rootNode['total_headcount']
    ];
    
    foreach ($mapped as $node) {
        $flatData[] = [
            'id' => $node['id'],
            'title' => $node['title'],
            'code' => $node['code'],
            'parentId' => $node['parent_id'],
            'headcount' => $node['headcount'],
            'total_headcount' => $node['total_headcount']
        ];
    }

    echo json_encode(['success' => true, 'data' => base64_encode(json_encode($flatData))]);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
