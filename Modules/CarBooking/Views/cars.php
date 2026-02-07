<?php

/**
 * Car Booking - Cars Management View
 * Migrated to Tailwind CSS
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;

$cars = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM cb_cars ORDER BY name ASC, brand ASC");
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cars = [];
}

$statusBadges = [
    'active' => 'bg-emerald-100 text-emerald-800',
    'available' => 'bg-emerald-100 text-emerald-800',
    'maintenance' => 'bg-amber-100 text-amber-800',
    'retired' => 'bg-gray-100 text-gray-600'
];

$statusLabels = [
    'active' => 'พร้อมใช้งาน',
    'available' => 'พร้อมใช้งาน',
    'maintenance' => 'ซ่อมบำรุง',
    'retired' => 'ปลดระวาง'
];
?>

<!-- Page Actions -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" id="filterStatus" onchange="filterCars()">
            <option value="">สถานะทั้งหมด</option>
            <option value="available">พร้อมใช้งาน</option>
            <option value="maintenance">ซ่อมบำรุง</option>
            <option value="retired">ปลดระวาง</option>
        </select>
        <input type="text" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" id="searchInput" placeholder="ค้นหารถ..." onkeyup="filterCars()">
    </div>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors" onclick="openCarModal()">
        <i class="ri-add-line"></i> เพิ่มรถใหม่
    </button>
</div>

<!-- Cars Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-roadster-line text-primary"></i>
            รายการรถ
        </h3>
        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">ทั้งหมด <?= count($cars) ?> คัน</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full" id="carsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อรถ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยี่ห้อ/รุ่น</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ทะเบียน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ที่นั่ง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($cars)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            <i class="ri-roadster-line text-3xl mb-2 block"></i>
                            <p>ยังไม่มีรถในระบบ</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($cars as $car): ?>
                        <tr data-status="<?= $car['status'] ?>" class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($car['name'] ?: '-') ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(($car['brand'] ?? '') . ' ' . ($car['model'] ?? '')) ?></td>
                            <td class="px-4 py-3"><code class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-sm"><?= htmlspecialchars($car['license_plate']) ?></code></td>
                            <td class="px-4 py-3 text-gray-600"><?= $car['capacity'] ?? '-' ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$car['status']] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$car['status']] ?? $car['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors" onclick='editCar(<?= json_encode($car) ?>)' title="แก้ไข">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded transition-colors" onclick='deleteCar(<?= $car['id'] ?>)' title="ลบ">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Car Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="carModal">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900" id="carModalTitle">เพิ่มรถใหม่</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeCarModal()">&times;</button>
        </div>
        <div class="p-6">
            <form id="carForm" class="space-y-4">
                <input type="hidden" name="id" id="carId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อรถ</label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="name" id="carName" placeholder="เช่น รถตู้ 1, รถเก๋ง A">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ยี่ห้อ</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="brand" id="carBrand" placeholder="Toyota, Honda">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">รุ่น</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="model" id="carModel" placeholder="Commuter, Civic">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทรถ <span class="text-red-500">*</span></label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="type" id="carType" required>
                        <option value="">-- เลือกประเภทรถ --</option>
                        <option value="van">รถตู้ (Van)</option>
                        <option value="sedan">รถเก๋ง (Sedan)</option>
                        <option value="pickup">รถกระบะ (Pickup)</option>
                        <option value="suv">รถ SUV</option>
                        <option value="other">อื่นๆ</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ทะเบียนรถ <span class="text-red-500">*</span></label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="license_plate" id="carPlate" placeholder="กข 1234" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนที่นั่ง</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="capacity" id="carCapacity" value="4" min="1">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="status" id="carStatus">
                        <option value="available">พร้อมใช้งาน</option>
                        <option value="maintenance">ซ่อมบำรุง</option>
                        <option value="retired">ปลดระวาง</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeCarModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="saveCar()">
                <i class="ri-save-line"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<style>
    #carModal.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let editingCarId = null;

    function filterCars() {
        const status = document.getElementById('filterStatus').value;
        const search = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#carsTable tbody tr[data-status]').forEach(row => {
            const matchStatus = !status || row.dataset.status === status;
            const matchSearch = !search || row.textContent.toLowerCase().includes(search);
            row.style.display = (matchStatus && matchSearch) ? '' : 'none';
        });
    }

    function openCarModal() {
        editingCarId = null;
        document.getElementById('carModalTitle').textContent = 'เพิ่มรถใหม่';
        document.getElementById('carForm').reset();
        document.getElementById('carId').value = '';
        document.getElementById('carModal').classList.add('active');
    }

    function closeCarModal() {
        document.getElementById('carModal').classList.remove('active');
    }

    function editCar(car) {
        editingCarId = car.id;
        document.getElementById('carModalTitle').textContent = 'แก้ไขข้อมูลรถ';
        document.getElementById('carId').value = car.id;
        document.getElementById('carName').value = car.name || '';
        document.getElementById('carBrand').value = car.brand || '';
        document.getElementById('carModel').value = car.model || '';
        document.getElementById('carType').value = car.type || '';
        document.getElementById('carPlate').value = car.license_plate || '';
        document.getElementById('carCapacity').value = car.capacity || 4;
        document.getElementById('carStatus').value = car.status || 'available';
        document.getElementById('carModal').classList.add('active');
    }

    async function saveCar() {
        const form = document.getElementById('carForm');
        const data = Object.fromEntries(new FormData(form));
        if (!data.license_plate) {
            showToast('กรุณาระบุทะเบียนรถ', 'error');
            return;
        }

        try {
            const response = await fetch(`${API_BASE}?controller=cars&action=${editingCarId ? 'update' : 'create'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (response.ok && result.success) {
                showToast(editingCarId ? 'แก้ไขสำเร็จ' : 'เพิ่มรถสำเร็จ', 'success');
                closeCarModal();
                setTimeout(() => location.reload(), 1000);
            } else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }

    async function deleteCar(carId) {
        const confirmed = await showConfirm('ต้องการลบรถนี้หรือไม่?', 'ยืนยันการลบ');
        if (!confirmed) return;

        try {
            const response = await fetch(`${API_BASE}?controller=cars&action=delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: carId
                })
            });
            const result = await response.json();
            if (response.ok) {
                showToast('ลบสำเร็จ', 'success');
                setTimeout(() => location.reload(), 1000);
            } else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeCarModal();
    });
</script>