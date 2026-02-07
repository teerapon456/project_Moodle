@echo off
REM ========================================
REM Car Booking CRON Job - Update Status
REM Run every 5 minutes via Windows Task Scheduler
REM ========================================

cd /d "C:\xampp\htdocs\MyHR Portal\Modules\CarBooking"
"C:\xampp\php\php.exe" cron_update_status.php

REM Log output (optional)
REM >> "C:\xampp\htdocs\MyHR Portal\Logs\cron.log" 2>&1
