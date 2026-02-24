<?php
require_once __DIR__ . '/core/Config/Env.php';
$SQLSRV_HOST = Env::get('SQLSRV_HOST', '172.17.100.26');
$SQLSRV_DB = Env::get('SQLSRV_DB', 'HRMULTI_INTEQC');
$SQLSRV_UID = Env::get('SQLSRV_UID', 'HRIS');
$SQLSRV_PWD = Env::get('SQLSRV_PWD', 'Hris@2024');
try {
    $dsn = "odbc:Driver={ODBC Driver 18 for SQL Server};Server=$SQLSRV_HOST;Database=$SQLSRV_DB;TrustServerCertificate=yes";
    $conn = new PDO($dsn, $SQLSRV_UID, $SQLSRV_PWD);
    $stmt = $conn->query("SELECT TOP 1 * FROM uv_employee");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r(array_keys($row));
} catch (Exception $e) {
    echo $e->getMessage();
}
