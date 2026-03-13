<?php

/**
 * Car Booking - Fleet Cards Management View
 * Migrated to Tailwind CSS
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;

$fleetCards = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT * FROM cb_fleet_cards ORDER BY card_number ASC");
    $fleetCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $fleetCards = [];
}

$statusBadges = [
    'active' => 'bg-emerald-100 text-emerald-800',
    'inactive' => 'bg-red-100 text-red-800',
    'lost' => 'bg-amber-100 text-amber-800'
];

$statusLabels = [
    'active' => 'ใช้งานได้',
    'inactive' => 'ระงับใช้งาน',
    'lost' => 'สูญหาย'
];
?>

<!-- Page Actions -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-3">
        <input type="text" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary" id="searchInput" placeholder="ค้นหา Fleet Card..." onkeyup="filterCards()">
    </div>
    <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg text-sm font-medium transition-colors" onclick="openCardModal()">
        <i class="ri-add-line"></i> เพิ่ม Fleet Card
    </button>
</div>

<!-- Fleet Cards Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-bank-card-line text-primary"></i>
            Fleet Card
        </h3>
        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">ทั้งหมด <?= count($fleetCards) ?> ใบ</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full" id="cardsTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมายเลขบัตร</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนก</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วงเงิน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ยอดคงเหลือ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมายเหตุ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($fleetCards)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                            <i class="ri-bank-card-line text-3xl mb-2 block"></i>
                            <p>ยังไม่มี Fleet Card ในระบบ</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($fleetCards as $card): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($card['card_number']) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($card['department'] ?? '-') ?></td>
                            <td class="px-4 py-3 text-gray-600">฿<?= number_format($card['credit_limit'] ?? 0, 2) ?></td>
                            <td class="px-4 py-3 text-gray-600">฿<?= number_format($card['current_balance'] ?? 0, 2) ?></td>
                            <td class="px-4 py-3 text-gray-400 text-sm"><?= htmlspecialchars($card['notes'] ?? '-') ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium <?= $statusBadges[$card['status'] ?? 'active'] ?? 'bg-gray-100 text-gray-600' ?>">
                                    <?= $statusLabels[$card['status'] ?? 'active'] ?? $card['status'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button class="p-1.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition-colors" onclick='editCard(<?= json_encode($card) ?>)' title="แก้ไข">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button class="p-1.5 bg-red-100 hover:bg-red-200 text-red-600 rounded transition-colors" onclick='deleteCard(<?= $card['id'] ?>)' title="ลบ">
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

<!-- Fleet Card Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-[1000] p-5 opacity-0 invisible transition-all" id="cardModal">
    <div class="bg-white rounded-xl w-full max-w-lg shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900" id="cardModalTitle">เพิ่ม Fleet Card</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeCardModal()">&times;</button>
        </div>
        <div class="p-6">
            <form id="cardForm" class="space-y-4">
                <input type="hidden" name="id" id="cardId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หมายเลขบัตร <span class="text-red-500">*</span></label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="card_number" id="cardNumber" placeholder="กรอกหมายเลขบัตร" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">แผนก</label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="department" id="cardDepartment" placeholder="แผนกที่รับผิดชอบ">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วงเงิน (Credit Limit)</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="credit_limit" id="cardCreditLimit" value="0" step="0.01">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">ยอดคงเหลือ</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="current_balance" id="cardCurrentBalance" value="0" step="0.01">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="status" id="cardStatus">
                        <option value="active">ใช้งานได้</option>
                        <option value="inactive">ระงับใช้งาน</option>
                        <option value="lost">สูญหาย</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ</label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="notes" id="cardNotes" rows="2" placeholder="รายละเอียดเพิ่มเติม..."></textarea>
                </div>
            </form>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeCardModal()">ยกเลิก</button>
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="saveCard()">
                <i class="ri-save-line"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<style>
    #cardModal.active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let editingCardId = null;

    function filterCards() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('#cardsTable tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
    }

    function openCardModal() {
        editingCardId = null;
        document.getElementById('cardModalTitle').textContent = 'เพิ่ม Fleet Card';
        document.getElementById('cardForm').reset();
        document.getElementById('cardId').value = '';
        document.getElementById('cardCreditLimit').value = '0';
        document.getElementById('cardCurrentBalance').value = '0';
        document.getElementById('cardModal').classList.add('active');
    }

    function closeCardModal() {
        document.getElementById('cardModal').classList.remove('active');
    }

    function editCard(card) {
        editingCardId = card.id;
        document.getElementById('cardModalTitle').textContent = 'แก้ไข Fleet Card';
        document.getElementById('cardId').value = card.id;
        document.getElementById('cardNumber').value = card.card_number || '';
        document.getElementById('cardDepartment').value = card.department || '';
        document.getElementById('cardCreditLimit').value = card.credit_limit || 0;
        document.getElementById('cardCurrentBalance').value = card.current_balance || 0;
        document.getElementById('cardStatus').value = card.status || 'active';
        document.getElementById('cardNotes').value = card.notes || '';
        document.getElementById('cardModal').classList.add('active');
    }

    async function saveCard() {
        const data = Object.fromEntries(new FormData(document.getElementById('cardForm')));
        if (!data.card_number) {
            showToast('กรุณาระบุหมายเลขบัตร', 'error');
            return;
        }

        try {
            const response = await fetch(`${API_BASE}?controller=fleetcards&action=${editingCardId ? 'update' : 'create'}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (response.ok) {
                showToast(editingCardId ? 'แก้ไขสำเร็จ' : 'เพิ่มสำเร็จ', 'success');
                closeCardModal();
                setTimeout(() => location.reload(), 1000);
            } else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }

    async function deleteCard(cardId) {
        const confirmed = await showConfirm('ต้องการลบ Fleet Card นี้หรือไม่?', 'ยืนยันการลบ');
        if (!confirmed) return;

        try {
            const response = await fetch(`${API_BASE}?controller=fleetcards&action=delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: cardId
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
        if (e.key === 'Escape') closeCardModal();
    });
</script>