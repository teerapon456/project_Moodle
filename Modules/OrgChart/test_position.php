<?php
require_once __DIR__ . '/../../core/Config/Env.php';
use Core\Config\Env;
$SQLSRV_HOST = Env::get('SQLSRV_HOST', '172.17.100.26');
$SQLSRV_DB = 'HRMULTI_INTEQC';
$SQLSRV_UID = 'HRIS';
$SQLSRV_PWD = 'Hris@2024';

try {
    $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};Server=$SQLSRV_HOST;Database=$SQLSRV_DB;TrustServerCertificate=yes";
    $conn = new PDO($dsn, $SQLSRV_UID, $SQLSRV_PWD);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Get total active positions
    $stmt = $conn->query("SELECT COUNT(*) as cnt FROM emPosition WHERE IsDeleted=0 AND IsInactive=0");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "Total Active Positions: $count\n\n";

    // 2. Sample 5 positions with their ReportTo to see if they match PositionIDs
    $sql = "
    SELECT TOP 5 
        p1.PositionCode, 
        p1.PositionName, 
        p1.ReportTo,
        p2.PositionName as ManagerPosition
    FROM emPosition p1
    LEFT JOIN emPosition p2 ON p1.ReportTo = p2.PositionID
    WHERE p1.IsDeleted=0 AND p1.IsInactive=0 AND p1.ReportTo IS NOT NULL
    ";

    $stmt = $conn->query($sql);
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample Reporting Lines:\n";
    print_r($samples);

    // 3. See how we can link it to Organization Units
    $sql2 = "
    SELECT TOP 3
        p.PositionName,
        o.OrgUnitCode,
        o.OrgUnitName
    FROM emPosition p
    LEFT JOIN emOrgUnit o ON p.OrgUnitID = o.OrgUnitID
    WHERE p.IsDeleted=0 AND p.IsInactive=0
    ";
    $stmt = $conn->query($sql2);
    echo "\nSample Unit Links:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
