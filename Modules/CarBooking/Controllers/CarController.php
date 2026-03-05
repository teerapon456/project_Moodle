<?php

require_once 'BaseController.php';

class CarController extends CBBaseController
{
    /**
     * List all cars
     */
    public function listAll()
    {
        $stmt = $this->pdo->query("SELECT * FROM cb_cars ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get car by ID
     */
    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cb_cars WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new car
     */
    public function create(array $data)
    {
        $this->requirePermission('manage');

        // Validation
        if (empty($data['name'])) {
            return $this->error('กรุณาระบุชื่อรถ');
        }
        if (empty($data['license_plate'])) {
            return $this->error('กรุณาระบุทะเบียนรถ');
        }
        if (empty($data['type'])) {
            return $this->error('กรุณาระบุประเภทรถ');
        }

        // Check for duplicate license plate
        $stmt = $this->pdo->prepare("SELECT id FROM cb_cars WHERE license_plate = :license_plate");
        $stmt->execute([':license_plate' => $data['license_plate']]);
        if ($stmt->fetch()) {
            return $this->error('ทะเบียนรถนี้มีอยู่ในระบบแล้ว');
        }

        try {
            $sql = "INSERT INTO cb_cars (name, brand, model, license_plate, type, capacity, status, is_company_car, created_at) 
                    VALUES (:name, :brand, :model, :license_plate, :type, :capacity, :status, :is_company_car, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $data['name'],
                ':brand' => $data['brand'] ?? null,
                ':model' => $data['model'] ?? null,
                ':license_plate' => $data['license_plate'],
                ':type' => $data['type'],
                ':capacity' => $data['capacity'] ?? null,
                ':status' => $data['status'] ?? 'available',
                ':is_company_car' => !empty($data['is_company_car']) ? 1 : 0
            ]);

            $id = $this->pdo->lastInsertId();

            // Log audit
            $this->logAudit('create_car', 'car', $id, null, $data);

            return [
                'success' => true,
                'message' => 'เพิ่มรถสำเร็จ',
                'id' => $id
            ];
        } catch (Exception $e) {
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Update car
     */
    public function update(array $data)
    {
        $this->requirePermission('manage');

        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->error('ID is required');
        }

        // Check if car exists
        $car = $this->getById($id);
        if (!$car) {
            return $this->error('ไม่พบรถที่ต้องการแก้ไข');
        }

        // Validation
        if (empty($data['name'])) {
            return $this->error('กรุณาระบุชื่อรถ');
        }
        if (empty($data['license_plate'])) {
            return $this->error('กรุณาระบุทะเบียนรถ');
        }
        if (empty($data['type'])) {
            return $this->error('กรุณาระบุประเภทรถ');
        }

        // Check for duplicate license plate (excluding current car)
        $stmt = $this->pdo->prepare("SELECT id FROM cb_cars WHERE license_plate = :license_plate AND id != :id");
        $stmt->execute([
            ':license_plate' => $data['license_plate'],
            ':id' => $id
        ]);
        if ($stmt->fetch()) {
            return $this->error('ทะเบียนรถนี้มีอยู่ในระบบแล้ว');
        }

        try {
            $sql = "UPDATE cb_cars 
                    SET name = :name,
                    brand = :brand,
                    model = :model,
                    license_plate = :license_plate,
                    type = :type,
                    capacity = :capacity,
                    status = :status,
                    is_company_car = :is_company_car
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':brand' => $data['brand'] ?? null,
                ':model' => $data['model'] ?? null,
                ':license_plate' => $data['license_plate'],
                ':type' => $data['type'],
                ':capacity' => $data['capacity'] ?? null,
                ':status' => $data['status'] ?? 'available',
                ':is_company_car' => !empty($data['is_company_car']) ? 1 : 0
            ]);

            // Log audit
            $this->logAudit('update_car', 'car', $id, $car, $data);

            return [
                'success' => true,
                'message' => 'แก้ไขข้อมูลรถสำเร็จ'
            ];
        } catch (Exception $e) {
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Delete car
     */
    public function delete($id)
    {
        $this->requirePermission('manage');

        // Check if car exists
        $car = $this->getById($id);
        if (!$car) {
            return $this->error('ไม่พบรถที่ต้องการลบ');
        }

        // Check if car is assigned to any active bookings
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM cb_bookings 
            WHERE assigned_car_id = :car_id 
            AND status IN ('pending_manager', 'approved')
        ");
        $stmt->execute([':car_id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return $this->error('ไม่สามารถลบรถได้ เนื่องจากมีการจองที่ใช้รถคันนี้อยู่');
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM cb_cars WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Log audit
            $this->logAudit('delete_car', 'car', $id, $car, null);

            return [
                'success' => true,
                'message' => 'ลบรถสำเร็จ'
            ];
        } catch (Exception $e) {
            return $this->error('เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
