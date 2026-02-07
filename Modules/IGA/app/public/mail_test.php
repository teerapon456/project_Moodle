<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';
use PHPMailer\PHPMailer\PHPMailer;

$m = new PHPMailer(true);
$m->isSMTP();
$m->Host = '172.17.100.6';   
$m->Port = 25;              
$m->SMTPAuth = false; 


$m->setFrom('porames_bua@inteqc.com', 'IGA');
$m->addAddress('porames_bua@inteqc.com');
$m->Subject = 'PHPMailer OK';
$m->Body = 'Hello from container!';
$m->send();
echo 'Mail sent';
