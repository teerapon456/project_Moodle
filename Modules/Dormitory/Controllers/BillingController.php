<?php

/**
 * Dormitory Module - Billing Controller
 * ระบบบันทึกมิเตอร์และออกบิล
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../../../core/Services/NotificationService.php';

class BillingController extends DormBaseController
{
    /**
     * ดึงอัตราค่าสาธารณูปโภคปัจจุบัน
     */
    public function getRates()
    {
        $stmt = $this->pdo->query("
            SELECT * FROM dorm_utility_rates 
            WHERE status = 'active'
            ORDER BY rate_type
        ");
        return $this->success(['rates' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * อัพเดทอัตราค่าสาธารณูปโภค
     */
    public function updateRates($data)
    {
        $this->requireAuth();
        $this->requirePermission('manage');

        // Handle both single rate object and array of rates
        if (isset($data['utility_type']) && isset($data['rate_per_unit'])) {
            // Single rate update (from frontend)
            $rates = [$data];
        } elseif (isset($data['rates']) && is_array($data['rates'])) {
            // Array format
            $rates = $data['rates'];
        } else {
            return $this->error('รูปแบบข้อมูลไม่ถูกต้อง');
        }

        foreach ($rates as $rate) {
            $utilityType = $rate['utility_type'] ?? null;
            $ratePerUnit = $rate['rate_per_unit'] ?? null;
            $id = $rate['id'] ?? null;

            if (!$utilityType || $ratePerUnit === null) {
                continue;
            }

            if ($id) {
                // Update existing rate
                $stmt = $this->pdo->prepare("
                    UPDATE dorm_utility_rates 
                    SET rate_per_unit = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$ratePerUnit, $id]);
            } else {
                // Check if exists by utility_type, if so update, otherwise insert
                $stmt = $this->pdo->prepare("SELECT id FROM dorm_utility_rates WHERE rate_type = ?");
                $stmt->execute([$utilityType]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $stmt = $this->pdo->prepare("
                        UPDATE dorm_utility_rates 
                        SET rate_per_unit = ?, updated_at = NOW()
                        WHERE rate_type = ?
                    ");
                    $stmt->execute([$ratePerUnit, $utilityType]);
                } else {
                    // Insert new rate
                    $stmt = $this->pdo->prepare("
                        INSERT INTO dorm_utility_rates (rate_type, rate_name, rate_per_unit, unit, status)
                        VALUES (?, ?, ?, ?, 'active')
                    ");
                    $rateName = $utilityType === 'electric' ? 'ค่าไฟฟ้า' : ($utilityType === 'water' ? 'ค่าน้ำประปา' : $utilityType);
                    $unit = $utilityType === 'electric' ? 'หน่วย' : ($utilityType === 'water' ? 'หน่วย' : 'หน่วย');
                    $stmt->execute([$utilityType, $rateName, $ratePerUnit, $unit]);
                }
            }
        }

        $this->logAudit('update_rates', 'rates', null, null, $data);
        return $this->success([], 'อัพเดทอัตราค่าสาธารณูปโภคสำเร็จ');
    }

    /**
     * ดึงค่ามิเตอร์
     */
    public function getMeterReadings()
    {
        $monthCycle = $_GET['month'] ?? date('Y-m');
        $buildingId = $_GET['building_id'] ?? null;

        $sql = "
            SELECT r.id as room_id, r.room_number, b.code as building_code, b.name as building_name,
                   (
                       SELECT GROUP_CONCAT(employee_name SEPARATOR ', ')
                       FROM dorm_occupancies
                       WHERE room_id = r.id AND status = 'active'
                   ) as occupant_name,
                   m.id as reading_id, m.reading_date,
                   m.prev_electricity, m.curr_electricity, m.electricity_usage,
                   m.prev_water, m.curr_water, m.water_usage,
                   m.notes
            FROM dorm_rooms r
            JOIN dorm_buildings b ON r.building_id = b.id
            LEFT JOIN dorm_meter_readings m ON r.id = m.room_id AND m.month_cycle = ?
            WHERE r.status = 'occupied'
        ";
        $params = [$monthCycle];

        if ($buildingId) {
            $sql .= " AND r.building_id = ?";
            $params[] = $buildingId;
        }

        $sql .= " ORDER BY b.code, r.floor, r.room_number";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->success([
            'month_cycle' => $monthCycle,
            'readings' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /**
     * บันทึกค่ามิเตอร์
     */
    public function saveMeterReading($data)
    {
        $this->requireAuth();

        $required = ['room_id', 'month_cycle', 'curr_electricity', 'curr_water'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        // ดึงค่ามิเตอร์ล่าสุดของห้อง (ไม่จำกัดเฉพาะเดือนก่อนหน้า เพื่อรองรับกรณีห้องว่างหลายเดือน)
        $stmt = $this->pdo->prepare("
            SELECT curr_electricity, curr_water, month_cycle
            FROM dorm_meter_readings 
            WHERE room_id = ? AND month_cycle < ?
            ORDER BY month_cycle DESC
            LIMIT 1
        ");
        $stmt->execute([$data['room_id'], $data['month_cycle']]);
        $prev = $stmt->fetch(PDO::FETCH_ASSOC);

        $prevElec = $prev['curr_electricity'] ?? $data['prev_electricity'] ?? 0;
        $prevWater = $prev['curr_water'] ?? $data['prev_water'] ?? 0;

        // Insert or Update
        $stmt = $this->pdo->prepare("
            INSERT INTO dorm_meter_readings 
            (room_id, reading_date, month_cycle, prev_electricity, curr_electricity, prev_water, curr_water, recorded_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            reading_date = VALUES(reading_date),
            prev_electricity = VALUES(prev_electricity),
            curr_electricity = VALUES(curr_electricity),
            prev_water = VALUES(prev_water),
            curr_water = VALUES(curr_water),
            recorded_by = VALUES(recorded_by),
            notes = VALUES(notes)
        ");
        $stmt->execute([
            $data['room_id'],
            $data['reading_date'] ?? date('Y-m-d'),
            $data['month_cycle'],
            $prevElec,
            $data['curr_electricity'],
            $prevWater,
            $data['curr_water'],
            $this->user['id'] ?? null,
            $data['notes'] ?? null
        ]);

        $this->logAudit('save_meter', 'meter_reading', $data['room_id'], null, $data);

        return $this->success([], 'บันทึกค่ามิเตอร์สำเร็จ');
    }

    /**
     * บันทึกค่ามิเตอร์หลายห้อง
     */
    public function saveBulkMeterReadings($data)
    {
        $this->requireAuth();

        if (empty($data['readings']) || !is_array($data['readings'])) {
            return $this->error('กรุณาส่งข้อมูลมิเตอร์');
        }

        $saved = 0;
        $errors = [];

        foreach ($data['readings'] as $reading) {
            try {
                $result = $this->saveMeterReading($reading);
                if ($result['success']) {
                    $saved++;
                }
            } catch (Exception $e) {
                $errors[] = "ห้อง {$reading['room_id']}: " . $e->getMessage();
            }
        }

        return $this->success([
            'saved' => $saved,
            'errors' => $errors
        ], "บันทึกสำเร็จ $saved รายการ");
    }

    /**
     * รายการบิลทั้งหมด
     */
    public function listInvoices()
    {
        $monthCycle = $_GET['month'] ?? null;
        $status = $_GET['status'] ?? null;
        $buildingId = $_GET['building_id'] ?? null;
        $roomId = $_GET['room_id'] ?? null;

        // RBAC: ถ้าไม่ใช่ admin/manage ต้องดูได้เฉพาะห้องของตัวเอง
        $isAdmin = $this->hasPermission('manage');

        if (!$isAdmin) {
            // หาห้องที่ user นี้อยู่ หรือเคยอยู่ (รวมทั้ง active และ checked_out เพื่อให้ดูบิลเก่าได้)
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT r.id 
                FROM dorm_rooms r
                JOIN dorm_occupancies o ON r.id = o.room_id
                WHERE (o.employee_id = ? OR o.employee_email = ?)
            ");
            $stmt->execute([
                $this->user['employee_id'] ?? '',
                $this->user['email'] ?? ''
            ]);
            $userRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($userRooms)) {
                return $this->success(['invoices' => []]); // ไม่มีห้อง = ไม่มีบิล
            }

            // ถ้ามีการส่ง room_id มา ต้องตรวจสอบว่าเป็นห้องของตัวเองไหม
            if ($roomId && !in_array($roomId, $userRooms)) {
                return $this->error('ไม่มีสิทธิ์ดูข้อมูลห้องนี้', 403);
            }
        }

        $sql = "
        SELECT i.*, 
               r.room_number, b.code as building_code, b.name as building_name,
               r.room_type as room_type_name,
               (
                   SELECT GROUP_CONCAT(employee_name SEPARATOR ', ') 
                   FROM dorm_occupancies 
                   WHERE room_id = i.room_id AND status = 'active'
               ) as room_occupants,
               o.employee_name as bill_owner_name
        FROM dorm_invoices i
        JOIN dorm_rooms r ON i.room_id = r.id
        JOIN dorm_buildings b ON r.building_id = b.id
        LEFT JOIN dorm_occupancies o ON i.occupancy_id = o.id
        WHERE 1=1
    ";
        $params = [];

        // Apply Permission Filter
        if (!$isAdmin) {
            // กรองเฉพาะห้องที่ user อยู่
            $placeholders = implode(',', array_fill(0, count($userRooms), '?'));
            $sql .= " AND i.room_id IN ($placeholders)";
            $params = array_merge($params, $userRooms);
        }

        if ($monthCycle) {
            $sql .= " AND i.month_cycle = ?";
            $params[] = $monthCycle;
        }
        if ($status) {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }
        if ($buildingId) {
            $sql .= " AND r.building_id = ?";
            $params[] = $buildingId;
        }
        if ($roomId) {
            $sql .= " AND i.room_id = ?";
            $params[] = $roomId;
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->success(['invoices' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /**
     * ดูรายละเอียดบิล
     */
    public function getInvoice($data)
    {
        $id = $data['id'] ?? $_GET['id'] ?? null;
        if (!$id) {
            return $this->error('กรุณาระบุ ID บิล');
        }

        $stmt = $this->pdo->prepare("
            SELECT i.*, 
                   r.room_number, b.code as building_code, b.name as building_name,
                   r.room_type as room_type_name,
                   o.employee_name, o.employee_id, o.employee_email, o.department
            FROM dorm_invoices i
            JOIN dorm_rooms r ON i.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            JOIN dorm_occupancies o ON i.occupancy_id = o.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return $this->error('ไม่พบบิล', 404);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM dorm_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$id]);
        $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ดึงข้อมูลการชำระเงิน (ถ้ามี)
        if (!empty($invoice['payment_id'])) {
            $stmt = $this->pdo->prepare("SELECT * FROM dorm_payments WHERE id = ?");
            $stmt->execute([$invoice['payment_id']]);
            $invoice['payment_info'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $this->success(['invoice' => $invoice]);
    }

    /**
     * สร้างบิลประจำเดือน
     */
    public function generateInvoices($data)
    {
        $this->requireAuth();

        $monthCycle = $data['month_cycle'] ?? date('Y-m');
        $buildingId = $data['building_id'] ?? null;
        $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime('+15 days'));

        // ดึงอัตราค่าสาธารณูปโภค
        $stmt = $this->pdo->query("SELECT rate_type, rate_per_unit FROM dorm_utility_rates WHERE status = 'active'");
        $rates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rates[$row['rate_type']] = $row['rate_per_unit'];
        }

        $elecRate = $rates['electricity'] ?? 7;
        $waterRate = $rates['water'] ?? 18;

        // ดึงห้องที่มีผู้พักและมีค่ามิเตอร์ (Group by room เพื่อป้องกันบิลซ้ำกรณีมีหลายคน)
        $sql = "
        SELECT m.*, r.monthly_rent, 
               (SELECT id FROM dorm_occupancies WHERE room_id = r.id AND status = 'active' LIMIT 1) as occupancy_id,
               r.room_number, b.code as building_code
        FROM dorm_meter_readings m
        JOIN dorm_rooms r ON m.room_id = r.id
        JOIN dorm_buildings b ON r.building_id = b.id
        WHERE m.month_cycle = ?
        AND EXISTS (SELECT 1 FROM dorm_occupancies WHERE room_id = r.id AND status = 'active')
    ";
        $params = [$monthCycle];

        if ($buildingId) {
            $sql .= " AND r.building_id = ?";
            $params[] = $buildingId;
        }

        // ตรวจสอบว่ายังไม่มีบิล (ยกเว้นบิลที่ยกเลิกไปแล้ว)
        $sql .= " AND NOT EXISTS (
        SELECT 1 FROM dorm_invoices i 
        WHERE i.room_id = m.room_id 
        AND i.month_cycle = m.month_cycle 
        AND i.status != 'cancelled'
    )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($readings)) {
            return $this->error('ไม่มีห้องที่ต้องสร้างบิล (อาจสร้างไปแล้ว หรือยังไม่ได้บันทึกมิเตอร์)');
        }

        $created = 0;
        $this->pdo->beginTransaction();

        try {
            foreach ($readings as $reading) {
                $elecUnits = max(0, ($reading['curr_electricity'] ?? 0) - ($reading['prev_electricity'] ?? 0));
                $waterUnits = max(0, ($reading['curr_water'] ?? 0) - ($reading['prev_water'] ?? 0));
                $elecAmount = $elecUnits * $elecRate;
                $waterAmount = $waterUnits * $waterRate;
                $roomRent = $reading['monthly_rent'] ?? 0;
                $totalAmount = $elecAmount + $waterAmount + $roomRent;

                $invoiceNumber = $this->generateNumber('INV', 'dorm_invoices', 'invoice_number');

                $stmt = $this->pdo->prepare("
                    INSERT INTO dorm_invoices 
                    (invoice_number, room_id, occupancy_id, month_cycle,
                     electricity_units, electricity_rate, electricity_amount,
                     water_units, water_rate, water_amount,
                     room_rent, total_amount, due_date, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $invoiceNumber,
                    $reading['room_id'],
                    $reading['occupancy_id'],
                    $monthCycle,
                    $elecUnits,
                    $elecRate,
                    $elecAmount,
                    $waterUnits,
                    $waterRate,
                    $waterAmount,
                    $roomRent,
                    $totalAmount,
                    $dueDate,
                    $this->user['id'] ?? null
                ]);

                $invoiceId = $this->pdo->lastInsertId();

                // เพิ่มรายการบิล
                $items = [
                    ['electricity', 'ค่าไฟฟ้า', $elecUnits, $elecRate, $elecAmount],
                    ['water', 'ค่าน้ำประปา', $waterUnits, $waterRate, $waterAmount],
                ];
                if ($roomRent > 0) {
                    $items[] = ['room', 'ค่าห้อง', 1, $roomRent, $roomRent];
                }

                foreach ($items as $item) {
                    if ($item[4] > 0) {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO dorm_invoice_items 
                            (invoice_id, item_type, description, quantity, unit_price, amount)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$invoiceId, $item[0], $item[1], $item[2], $item[3], $item[4]]);
                    }
                }

                $created++;
            }

            $this->pdo->commit();
            $this->logAudit('generate_invoices', 'invoice', null, null, [
                'month_cycle' => $monthCycle,
                'count' => $created
            ]);

            return $this->success(['created' => $created], "สร้างบิลสำเร็จ $created รายการ");
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * บันทึกการชำระเงิน
     */
    public function recordPayment($data)
    {
        $this->requireAuth();

        $required = ['invoice_id', 'amount', 'payment_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->error("กรุณากรอก $field");
            }
        }

        // ตรวจสอบบิล
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_invoices WHERE id = ?");
        $stmt->execute([$data['invoice_id']]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return $this->error('ไม่พบบิล', 404);
        }

        $this->pdo->beginTransaction();

        try {
            // บันทึกการชำระเงิน
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_payments 
                (invoice_id, payment_date, amount, payment_method, reference_number, receipt_number, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['invoice_id'],
                $data['payment_date'],
                $data['amount'],
                $data['payment_method'] ?? 'transfer',
                $data['reference_number'] ?? null,
                $data['receipt_number'] ?? null,
                $data['notes'] ?? null,
                $this->user['id'] ?? null
            ]);

            // อัพเดทยอดชำระในบิล
            $newPaidAmount = $invoice['paid_amount'] + $data['amount'];
            $newStatus = $newPaidAmount >= $invoice['total_amount'] ? 'paid' : 'partial';

            $stmt = $this->pdo->prepare("
                UPDATE dorm_invoices 
                SET paid_amount = ?, status = ?, paid_date = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $newPaidAmount,
                $newStatus,
                $newStatus === 'paid' ? $data['payment_date'] : null,
                $data['invoice_id']
            ]);

            $this->pdo->commit();
            $this->logAudit('record_payment', 'payment', $data['invoice_id'], null, $data);

            return $this->success([
                'new_status' => $newStatus,
                'paid_amount' => $newPaidAmount
            ], 'บันทึกการชำระเงินสำเร็จ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * สรุปยอดค่าใช้จ่าย
     */
    public function summary()
    {
        $monthCycle = $_GET['month'] ?? date('Y-m');
        $roomId = $_GET['room_id'] ?? null;

        // RBAC
        $isAdmin = $this->hasPermission('manage');

        $roomFilter = "";
        $params = [$monthCycle];
        $userRooms = [];

        if (!$isAdmin) {
            // หาห้องที่ user นี้อยู่
            $stmt = $this->pdo->prepare("
                SELECT r.id 
                FROM dorm_rooms r
                JOIN dorm_occupancies o ON r.id = o.room_id
                WHERE o.employee_id = ? AND o.status = 'active'
            ");
            $stmt->execute([$this->user['employee_id'] ?? '']);
            $userRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($userRooms)) {
                return $this->success(['summary' => [
                    'current_month' => ['total_invoices' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'outstanding' => 0],
                    'total_outstanding' => 0,
                    'by_building' => []
                ], 'month_cycle' => $monthCycle]);
            }
            // Filter only user rooms
            $placeholders = implode(',', array_fill(0, count($userRooms), '?'));
            $roomFilter = " AND room_id IN ($placeholders) ";
            // Add userRooms to params.
            // But wait, existing params has $monthCycle. 
            // The query structure needs to change to append params.
            // Let's restructure properly.
        }

        $summary = [];

        // Helper to build secure query
        $buildQuery = function ($baseSql) use ($roomFilter, $monthCycle, $isAdmin, $params, $userRooms) {
            // Rebuild params
            $localParams = [$monthCycle];
            if (!$isAdmin) {
                $localParams = array_merge($localParams, $userRooms);
            }
            return ['sql' => $baseSql . $roomFilter, 'params' => $localParams];
        };

        // สรุปบิลประจำเดือน
        $baseSql = "
            SELECT 
                COUNT(*) as total_invoices,
                SUM(total_amount) as total_amount,
                SUM(paid_amount) as paid_amount,
                SUM(total_amount - paid_amount) as outstanding,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
            FROM dorm_invoices
            WHERE month_cycle = ?
        ";

        $queryInfo = $buildQuery($baseSql);
        $stmt = $this->pdo->prepare($queryInfo['sql']);
        $stmt->execute($queryInfo['params']);
        $summary['current_month'] = $stmt->fetch(PDO::FETCH_ASSOC);

        // ยอดค้างชำระทั้งหมด
        // Base params for outstanding is None (no month), but we need room filter.
        $outParams = [];
        if (!$isAdmin) {
            $outParams = $userRooms;
        }
        $outSql = "
            SELECT SUM(total_amount - paid_amount) as total_outstanding
            FROM dorm_invoices
            WHERE status IN ('pending', 'partial', 'overdue')
            " . ($roomFilter);

        $stmt = $this->pdo->prepare($outSql);
        $stmt->execute($outParams);
        $summary['total_outstanding'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_outstanding'] ?? 0;

        // สรุปรายอาคาร (Only if admin, probably? Or if resident, it just shows their building)
        $buildSql = "
            SELECT b.name as building_name,
                   COUNT(i.id) as total_invoices,
                   SUM(i.total_amount) as total_amount,
                   SUM(i.paid_amount) as paid_amount
            FROM dorm_invoices i
            JOIN dorm_rooms r ON i.room_id = r.id
            JOIN dorm_buildings b ON r.building_id = b.id
            WHERE i.month_cycle = ?
            $roomFilter
            GROUP BY b.id
        ";

        $stmt = $this->pdo->prepare($buildSql);
        $localParams = [$monthCycle];
        if (!$isAdmin) $localParams = array_merge($localParams, $userRooms);
        $stmt->execute($localParams);
        $summary['by_building'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['summary' => $summary, 'month_cycle' => $monthCycle]);
    }

    /**
     * ยกเลิกบิล
     */
    public function cancelInvoice($data)
    {
        $this->requireAuth();

        $id = $data['id'] ?? $data['invoice_id'] ?? null;
        $reason = $data['reason'] ?? '';

        if (!$id) {
            return $this->error('กรุณาระบุ ID บิล');
        }

        // ตรวจสอบบิล
        $stmt = $this->pdo->prepare("SELECT * FROM dorm_invoices WHERE id = ?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) {
            return $this->error('ไม่พบบิล', 404);
        }

        if ($invoice['status'] === 'cancelled') {
            return $this->error('บิลนี้ถูกยกเลิกไปแล้ว');
        }

        // อัพเดทสถานะเป็น cancelled
        $stmt = $this->pdo->prepare("
            UPDATE dorm_invoices 
            SET status = 'cancelled', notes = CONCAT(IFNULL(notes, ''), '\n[ยกเลิก] ', ?)
            WHERE id = ?
        ");
        $stmt->execute([$reason, $id]);

        $this->logAudit('cancel_invoice', 'invoice', $id, $invoice, ['reason' => $reason]);

        return $this->success([], 'ยกเลิกบิลสำเร็จ');
    }
    /**
     * สร้างรายการชำระเงิน (แจ้งโอน)
     */
    public function createPayment($data)
    {


        $this->requireAuth();

        $invoiceIds = $data['invoice_ids'] ?? [];
        if (is_string($invoiceIds)) {
            $invoiceIds = explode(',', $invoiceIds);
        }
        $transferDate = $data['transfer_date'] ?? date('Y-m-d');
        $transferTime = $data['transfer_time'] ?? date('H:i');
        // $files = $data['files'] ?? []; // For unit testing if needed

        if (empty($invoiceIds)) {
            return $this->error('กรุณาเลือกรายการบิลที่ต้องการชำระ');
        }

        // ตรวจสอบไฟล์แนบ
        if (empty($_FILES['proof_file'])) {
            return $this->error('กรุณาแนบหลักฐานการโอนเงิน');
        }

        if ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
            $error = $_FILES['proof_file']['error'];
            $message = 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์';

            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $message = 'ไฟล์มีขนาดใหญ่เกินไป (รองรับไม่เกิน ' . ini_get('upload_max_filesize') . ')';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $message = 'การอัพโหลดไม่สมบูรณ์ กรุณาลองใหม่';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $message = 'กรุณาเลือกไฟล์หลักฐานการโอน';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $message = 'ไม่พบโฟลเดอร์สำหรับพักไฟล์ (Server Error)';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $message = 'บันทึกไฟล์ไม่สำเร็จ (Server Error)';
                    break;
            }
            return $this->error($message);
        }

        // คำนวณยอดรวมจากบิลที่เลือก
        $placeholders = implode(',', array_fill(0, count($invoiceIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id, total_amount, paid_amount FROM dorm_invoices WHERE id IN ($placeholders)");
        $stmt->execute($invoiceIds);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalAmount = 0;
        foreach ($invoices as $inv) {
            $totalAmount += ($inv['total_amount'] - $inv['paid_amount']);
        }

        // Upload File
        $uploadDir = __DIR__ . '/../public/uploads/slips/' . date('Y/m');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('slip_') . '.' . $extension;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($_FILES['proof_file']['tmp_name'], $destPath)) {
            return $this->error('บันทึกไฟล์ไม่สำเร็จ');
        }

        require_once __DIR__ . '/../../../core/Helpers/UrlHelper.php';
        // Store just relative path from uploads/slips folder (e.g. 2025/12/slip_xxx.png)
        $proofUrl = date('Y/m') . '/' . $filename;

        $this->pdo->beginTransaction();
        try {
            // 1. สร้าง record การจ่ายเงิน
            $stmt = $this->pdo->prepare("
                INSERT INTO dorm_payments (total_amount, payment_date, payment_time, proof_file, status, paid_by, created_at)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                $totalAmount,
                $transferDate,
                $transferTime,
                $proofUrl,
                $this->user['employee_id'] ?? $this->user['id'] ?? 'unknown'
            ]);
            $paymentId = $this->pdo->lastInsertId();

            // 2. อัพเดทสถานะบิล
            $stmt = $this->pdo->prepare("
                UPDATE dorm_invoices 
                SET status = 'pending_verification', payment_id = ?
                WHERE id IN ($placeholders)
            ");
            // Merge parameters: payment_id + invoice_ids
            $params = array_merge([$paymentId], $invoiceIds);
            $stmt->execute($params);

            $this->pdo->commit();

            // Send email notification to admin
            try {
                require_once __DIR__ . '/../Helpers/NotificationHelper.php';
                NotificationHelper::notifyPaymentSubmitted([
                    'total_amount' => $totalAmount,
                    'payment_date' => $transferDate,
                    'payment_time' => $transferTime,
                    'invoice_count' => count($invoiceIds)
                ]);
            } catch (Exception $notifyError) {
                // Email failure should not break the payment flow
                error_log('Payment notification failed: ' . $notifyError->getMessage());
            }

            // In-app notification to admins
            $this->notifyDormAdmins(
                'info',
                'มีการแจ้งชำระเงินใหม่',
                "รอตรวจสอบ " . count($invoiceIds) . " บิล (ยอด " . number_format($totalAmount) . " บ.)",
                "Modules/Dormitory/?page=payments"
            );

            return $this->success(['payment_id' => $paymentId], 'แจ้งชำระเงินเรียบร้อย รอการตรวจสอบ');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * อนุมัติการชำระเงิน
     */
    public function approvePayment($data)
    {
        $this->requireAuth();
        // Check permissions logic here if needed

        $paymentId = $data['payment_id'];
        $notes = $data['notes'] ?? '';

        $this->pdo->beginTransaction();
        try {
            // Update Payment
            $stmt = $this->pdo->prepare("UPDATE dorm_payments SET status = 'approved', remark = ? WHERE id = ?");
            $stmt->execute([$notes, $paymentId]);

            // Update Invoices (Set to paid)
            $stmt = $this->pdo->prepare("
                UPDATE dorm_invoices 
                SET status = 'paid', paid_amount = total_amount 
                WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);

            $this->pdo->commit();

            // Notify payer that payment is approved
            $stmt = $this->pdo->prepare("SELECT paid_by FROM dorm_payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!empty($payment['paid_by'])) {
                // Get user id from employee_id or id
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE employee_id = ? OR id = ?");
                $stmt->execute([$payment['paid_by'], $payment['paid_by']]);
                $userId = $stmt->fetchColumn();
                if ($userId) {
                    NotificationService::create(
                        $userId,
                        'success',
                        'ชำระเงินสำเร็จ',
                        'การแจ้งชำระค่าหอพักได้รับการอนุมัติแล้ว',
                        [],
                        "Modules/Dormitory/?page=invoices"
                    );
                }
            }

            return $this->success([], 'อนุมัติการชำระเงินเรียบร้อย');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ปฏิเสธการชำระเงิน
     */
    public function rejectPayment($data)
    {
        $this->requireAuth();

        $paymentId = $data['payment_id'];
        $reason = $data['reason'] ?? 'หลักฐานไม่ถูกต้อง';

        $this->pdo->beginTransaction();
        try {
            // Update Payment
            $stmt = $this->pdo->prepare("UPDATE dorm_payments SET status = 'rejected', remark = ? WHERE id = ?");
            $stmt->execute([$reason, $paymentId]);

            // Update Invoices (Revert to pending, remove payment link)
            $stmt = $this->pdo->prepare("
                UPDATE dorm_invoices 
                SET status = 'pending', payment_id = NULL 
                WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);

            $this->pdo->commit();

            // Notify payer that payment is rejected
            $stmt = $this->pdo->prepare("SELECT paid_by FROM dorm_payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!empty($payment['paid_by'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE employee_id = ? OR id = ?");
                $stmt->execute([$payment['paid_by'], $payment['paid_by']]);
                $userId = $stmt->fetchColumn();
                if ($userId) {
                    NotificationService::create(
                        $userId,
                        'error',
                        'การชำระเงินถูกปฏิเสธ',
                        mb_substr($reason, 0, 50),
                        [],
                        "Modules/Dormitory/?page=invoices"
                    );
                }
            }

            return $this->success([], 'บันทึกรายการปฏิเสธเรียบร้อย');
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
