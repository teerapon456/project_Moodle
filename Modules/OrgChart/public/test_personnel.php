<?php
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    $positionId = '06f21dc2-e495-4002-a4c0-b9d0548493a1';

    $sql = "
        SELECT user_id as id, 
               fullname, 
               email, 
               OrgUnitName as department,
               Level3Name as section,
               PositionName
        FROM users 
        WHERE PositionID = ? AND is_active = 1 AND user_id != '1111'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$positionId]);
    $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $personnel]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
