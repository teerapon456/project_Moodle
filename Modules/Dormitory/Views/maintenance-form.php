<?php
// maintenance-form.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;
?>
<!-- Maintenance Form View - Migrated to Tailwind -->
<div class="max-w-3xl mx-auto px-0">
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 px-4 md:px-6 py-3 md:py-4 border-b border-gray-100">
            <i class="ri-tools-fill text-xl text-primary"></i>
            <h3 class="text-base md:text-lg font-semibold text-gray-900">แบบฟอร์มแจ้งซ่อม</h3>
        </div>

        <form id="maintenanceForm" class="p-4 md:p-6" onsubmit="handleSubmit(event)">
            <!-- ข้อมูลผู้แจ้ง -->
            <div class="mb-6 pb-6 border-b border-gray-100">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-primary mb-4">
                    <i class="ri-user-line"></i>
                    ข้อมูลผู้แจ้ง
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ชื่อ-นามสกุล *</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="requester_name"
                            value="<?= htmlspecialchars($user['fullname'] ?? $user['name'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">อีเมล</label>
                        <input type="email" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="requester_email"
                            value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                </div>
                <div class="mt-4 max-w-[250px]">
                    <label class="block mb-2 text-sm font-medium text-gray-700">เบอร์โทร</label>
                    <input type="tel" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="requester_phone" placeholder="0xx-xxx-xxxx">
                </div>
            </div>

            <!-- ข้อมูลสถานที่ -->
            <div class="mb-6 pb-6 border-b border-gray-100">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-primary mb-4">
                    <i class="ri-map-pin-line"></i>
                    สถานที่
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ห้องพัก</label>
                        <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="room_id" id="roomSelect">
                            <option value="">-- เลือกห้อง (ถ้ามี) --</option>
                        </select>
                        <small class="text-gray-500 text-xs mt-1 block">หากเป็นพื้นที่ส่วนกลาง ไม่ต้องเลือกห้อง</small>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">รายละเอียดตำแหน่ง</label>
                        <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="location_detail"
                            placeholder="เช่น ห้องน้ำ, ระเบียง, โถงบันได">
                    </div>
                </div>
            </div>

            <!-- ข้อมูลปัญหา -->
            <div class="mb-6">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-primary mb-4">
                    <i class="ri-error-warning-line"></i>
                    รายละเอียดปัญหา
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">หมวดหมู่ *</label>
                        <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="category_id" id="categorySelect" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">ความเร่งด่วน *</label>
                        <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="priority" required>
                            <option value="low">🟢 ไม่ด่วน - แก้ไขได้ตามสะดวก</option>
                            <option value="medium" selected>🟡 ปกติ - ควรแก้ไขภายใน 3 วัน</option>
                            <option value="high">🟠 ด่วน - ควรแก้ไขภายในวันนี้</option>
                            <option value="critical">🔴 ด่วนมาก - กระทบความปลอดภัย</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">หัวข้อ *</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="title"
                        placeholder="อธิบายปัญหาสั้นๆ เช่น แอร์ไม่เย็น, ท่อน้ำรั่ว" required maxlength="255">
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">รายละเอียด *</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="description" rows="4"
                        placeholder="อธิบายรายละเอียดของปัญหา อาการที่พบ ตั้งแต่เมื่อไหร่" required></textarea>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-5 md:pt-6 border-t border-gray-100">
                <a href="?page=my-room" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors order-2 sm:order-1">
                    <i class="ri-arrow-left-line"></i>
                    ยกเลิก
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm order-1 sm:order-2 w-full sm:w-auto" id="submitBtn">
                    <i class="ri-send-plane-fill"></i>
                    ส่งแจ้งซ่อม
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="successModal">
    <div class="bg-white rounded-xl w-full max-w-md shadow-2xl">
        <div class="text-center p-10">
            <div class="w-[72px] h-[72px] mx-auto bg-emerald-50 rounded-full flex items-center justify-center mb-5">
                <i class="ri-check-line text-4xl text-success"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">ส่งแจ้งซ่อมสำเร็จ!</h3>
            <p class="text-gray-500 mb-1">หมายเลขติดตาม</p>
            <p class="text-xl font-bold text-primary mb-4" id="ticketNumber"></p>
            <p class="text-sm text-gray-400 mb-6">เจ้าหน้าที่จะดำเนินการตรวจสอบและติดต่อกลับ</p>
            <a href="?page=maintenance" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors">
                <i class="ri-list-check-2"></i>
                ดูรายการแจ้งซ่อม
            </a>
        </div>
    </div>
</div>

<style>
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', async () => {
        await loadCategories();
        await loadRooms();
    });

    async function loadCategories() {
        try {
            const result = await apiCall('maintenance', 'getCategories');
            const select = document.getElementById('categorySelect');
            result.categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    async function loadRooms() {
        try {
            const select = document.getElementById('roomSelect');
            // Check if user is admin - permissions could be string, array, or undefined
            const perms = USER.permissions || [];
            const isAdminUser = USER.role === 'admin' ||
                (Array.isArray(perms) && (perms.includes('admin') || perms.includes('manage'))) ||
                (typeof perms === 'string' && (perms.includes('admin') || perms.includes('manage')));

            if (isAdminUser) {
                const result = await apiCall('rooms', 'list', {
                    status: 'occupied'
                });
                result.rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.building_code}${room.room_number} - ${room.occupant_name || 'ว่าง'}`;
                    select.appendChild(option);
                });
            } else {
                const result = await apiCall('rooms', 'getMyRoom', {
                    email: USER.email
                });
                if (result.room) {
                    const room = result.room;
                    const option = document.createElement('option');
                    option.value = room.id;
                    option.textContent = `${room.building_code}${room.room_number}`;
                    option.selected = true;
                    select.appendChild(option);
                }
            }
        } catch (error) {
            console.error('Failed to load rooms:', error);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = document.getElementById('submitBtn');

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังส่ง...';

        try {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            Object.keys(data).forEach(key => {
                if (data[key] === '') delete data[key];
            });

            const result = await apiCall('maintenance', 'create', data, 'POST');
            document.getElementById('ticketNumber').textContent = result.ticket_number;
            document.getElementById('successModal').classList.add('active');
        } catch (error) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-line"></i> ส่งแจ้งซ่อม';
        }
    }
</script>