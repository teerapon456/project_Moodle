<?php

/**
 * Car Booking Module - Fleet Card Controller
 */

require_once __DIR__ . '/BaseController.php';

class FleetCardController extends CBBaseController
{
    /**
     * List all fleet cards
     */
    public function list()
    {
        $this->requireAuth();

        $stmt = $this->pdo->query("SELECT * FROM cb_fleet_cards ORDER BY card_number ASC");
        $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['cards' => $cards]);
    }

    /**
     * Get single fleet card
     */
    public function get($input)
    {
        $this->requireAuth();
        $id = $input['id'] ?? null;

        if (!$id) {
            return $this->error('ID is required');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM cb_fleet_cards WHERE id = ?");
        $stmt->execute([$id]);
        $card = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$card) {
            return $this->error('Fleet card not found', 404);
        }

        return $this->success(['card' => $card]);
    }

    /**
     * Create new fleet card
     */
    public function create($input)
    {
        $this->requirePermission('manage');

        $cardNumber = trim($input['card_number'] ?? '');
        $department = trim($input['department'] ?? '');
        $creditLimit = floatval($input['credit_limit'] ?? 0);
        $currentBalance = floatval($input['current_balance'] ?? 0);
        $status = $input['status'] ?? 'active';
        $notes = trim($input['notes'] ?? '');

        if (!$cardNumber) {
            return $this->error('กรุณาระบุหมายเลขบัตร');
        }

        // Check duplicate
        $stmt = $this->pdo->prepare("SELECT id FROM cb_fleet_cards WHERE card_number = ?");
        $stmt->execute([$cardNumber]);
        if ($stmt->fetch()) {
            return $this->error('หมายเลขบัตรนี้มีอยู่ในระบบแล้ว');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO cb_fleet_cards (card_number, department, credit_limit, current_balance, status, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$cardNumber, $department, $creditLimit, $currentBalance, $status, $notes]);
        $id = $this->pdo->lastInsertId();

        $this->logAudit('create_fleet_card', 'fleet_card', $id, null, $input);

        return $this->success(['id' => $id], 'เพิ่ม Fleet Card สำเร็จ');
    }

    /**
     * Update fleet card
     */
    public function update($input)
    {
        $this->requirePermission('manage');

        $id = $input['id'] ?? null;
        if (!$id) {
            return $this->error('ID is required');
        }

        // Get old values
        $stmt = $this->pdo->prepare("SELECT * FROM cb_fleet_cards WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            return $this->error('Fleet card not found', 404);
        }

        $cardNumber = trim($input['card_number'] ?? $old['card_number']);
        $department = trim($input['department'] ?? $old['department']);
        $creditLimit = floatval($input['credit_limit'] ?? $old['credit_limit']);
        $currentBalance = floatval($input['current_balance'] ?? $old['current_balance']);
        $status = $input['status'] ?? $old['status'];
        $notes = trim($input['notes'] ?? $old['notes']);

        // Check duplicate (exclude current)
        $stmt = $this->pdo->prepare("SELECT id FROM cb_fleet_cards WHERE card_number = ? AND id != ?");
        $stmt->execute([$cardNumber, $id]);
        if ($stmt->fetch()) {
            return $this->error('หมายเลขบัตรนี้มีอยู่ในระบบแล้ว');
        }

        $stmt = $this->pdo->prepare("
            UPDATE cb_fleet_cards 
            SET card_number = ?, department = ?, credit_limit = ?, current_balance = ?, status = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$cardNumber, $department, $creditLimit, $currentBalance, $status, $notes, $id]);

        $this->logAudit('update_fleet_card', 'fleet_card', $id, $old, $input);

        return $this->success([], 'แก้ไข Fleet Card สำเร็จ');
    }

    /**
     * Delete fleet card
     */
    public function delete($input)
    {
        $this->requirePermission('manage');

        $id = $input['id'] ?? null;
        if (!$id) {
            return $this->error('ID is required');
        }

        // Get old values
        $stmt = $this->pdo->prepare("SELECT * FROM cb_fleet_cards WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            return $this->error('Fleet card not found', 404);
        }

        $stmt = $this->pdo->prepare("DELETE FROM cb_fleet_cards WHERE id = ?");
        $stmt->execute([$id]);

        $this->logAudit('delete_fleet_card', 'fleet_card', $id, $old, null);

        return $this->success([], 'ลบ Fleet Card สำเร็จ');
    }
}
