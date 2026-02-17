<?php

/**
 * Shared Booking Modal - Used across Dashboard, Bookings, Calendar
 * Migrated to Tailwind CSS
 */
?>

<!-- Booking Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-5 opacity-0 invisible transition-all" id="bookingModal">
    <div class="bg-white rounded-xl w-full max-w-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">สร้างคำขอจองรถใหม่</h3>
            <button class="text-gray-400 hover:text-gray-600 text-2xl" onclick="closeBookingModal()">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form id="bookingForm" class="space-y-4">
                <input type="hidden" name="approver_email" id="approverEmail" value="">
                <input type="hidden" name="approver_user_id" id="approverUserId" value="">
                <input type="hidden" name="driver_user_id" id="driverUserId" value="">
                <input type="hidden" name="driver_name" id="driverName" value="">
                <input type="hidden" name="driver_email" id="driverEmail" value="">

                <!-- Date/Time -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันเวลาเริ่มต้น <span class="text-red-500">*</span></label>
                        <input type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="start_time" id="bookingStartTime" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">วันเวลาสิ้นสุด <span class="text-red-500">*</span></label>
                        <input type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="end_time" id="bookingEndTime" required>
                    </div>
                </div>

                <!-- Requester (Current User - readonly) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ผู้ขอ</label>
                    <div class="flex items-center gap-3 px-4 py-3 bg-emerald-50 border border-primary rounded-lg text-primary">
                        <i class="ri-user-line"></i>
                        <span><?= htmlspecialchars($user['fullname'] ?? $user['username'] ?? 'ผู้ใช้') ?></span>
                        <small class="ml-auto text-gray-500"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                    </div>
                </div>

                <!-- Destination & Purpose -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ปลายทาง <span class="text-red-500">*</span></label>
                    <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="destination" id="bookingDestination" placeholder="ระบุสถานที่ปลายทาง" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">วัตถุประสงค์ <span class="text-red-500">*</span></label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" name="purpose" id="bookingPurpose" rows="2" placeholder="ระบุวัตถุประสงค์ในการใช้รถ" required></textarea>
                </div>

                <!-- Supervisor Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">หัวหน้างาน/ผู้อนุมัติ <span class="text-red-500">*</span></label>
                    <div id="selectedSupervisor" class="hidden flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <i class="ri-user-star-line text-primary"></i>
                        <span id="supervisorDisplayName"></span>
                        <button type="button" class="ml-auto text-gray-400 hover:text-red-500" onclick="clearSupervisor()">&times;</button>
                    </div>
                    <div class="relative" id="supervisorSearchContainer">
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="supervisorSearch" placeholder="ค้นหาชื่อหรืออีเมลหัวหน้า..." autocomplete="off" oninput="searchSupervisor(this.value)">
                        <div id="supervisorResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
                    </div>
                </div>

                <!-- Driver Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">คนขับ</label>
                    <div id="selectedDriver" class="hidden flex items-center gap-3 px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg">
                        <i class="ri-steering-2-line text-blue-500"></i>
                        <span id="driverDisplayName"></span>
                        <button type="button" class="ml-auto text-gray-400 hover:text-red-500" onclick="clearDriver()">&times;</button>
                    </div>
                    <div class="relative" id="driverSearchContainer">
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="driverSearch" placeholder="ค้นหาคนขับ (ปล่อยว่างถ้าขับเอง)..." autocomplete="off" oninput="searchDriver(this.value)">
                        <div id="driverResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">ถ้าไม่ระบุ จะใช้ผู้ขอเป็นคนขับ</p>
                </div>

                <!-- Passengers Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ผู้โดยสาร</label>
                    <div id="passengersList" class="space-y-2 mb-2"></div>
                    <div class="relative">
                        <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="passengerSearch" placeholder="ค้นหาเพิ่มผู้โดยสาร..." autocomplete="off" oninput="searchPassenger(this.value)">
                        <div id="passengerResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
            <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeBookingModal()" id="bookingCancelBtn">ยกเลิก</button>
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors" onclick="submitBooking()" id="bookingSubmitBtn">
                <i class="ri-send-plane-line"></i> <span>ส่งคำขอ</span>
            </button>
        </div>
    </div>
</div>

<style>
    #bookingModal.active {
        opacity: 1;
        visibility: visible;
    }

    #selectedSupervisor.active,
    #selectedDriver.active {
        display: flex;
    }
</style>

<script>
    let selectedSupervisor = null;
    let selectedDriver = null;
    let selectedPassengers = [];
    let searchTimeout;

    const defaultSupervisor = <?= json_encode([
                                    'email' => $user['default_supervisor_email'] ?? null,
                                    'name' => $user['default_supervisor_name'] ?? null,
                                    'id' => $user['default_supervisor_id'] ?? null
                                ]) ?>;
    const hasDefaultSupervisor = !!defaultSupervisor.email;

    function openBookingModal(prefillDate = null) {
        document.getElementById('bookingForm').reset();
        selectedSupervisor = null;
        selectedDriver = null;
        selectedPassengers = [];
        updatePassengersDisplay();

        document.getElementById('selectedSupervisor').classList.add('hidden');
        document.getElementById('supervisorSearchContainer').classList.remove('hidden');
        document.getElementById('selectedDriver').classList.add('hidden');
        document.getElementById('driverSearchContainer').classList.remove('hidden');

        if (prefillDate) {
            document.getElementById('bookingStartTime').value = prefillDate + 'T09:00';
            document.getElementById('bookingEndTime').value = prefillDate + 'T17:00';
        }

        if (hasDefaultSupervisor) {
            selectSupervisor({
                email: defaultSupervisor.email,
                name: defaultSupervisor.name || defaultSupervisor.email,
                id: defaultSupervisor.id
            }, false);
        }

        document.getElementById('bookingModal').classList.add('active');
    }

    function closeBookingModal() {
        document.getElementById('bookingModal').classList.remove('active');
    }

    async function searchSupervisor(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('supervisorResults');
        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`${API_BASE}?controller=bookings&action=searchManager&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    resultsDiv.innerHTML = data.users.map(emp => `
                        <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick='selectSupervisor(${JSON.stringify(emp)})'>
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-red-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                <div class="text-xs text-gray-500">${emp.email} • ${emp.department || '-'}</div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                        </div>
                    `).join('');
                    resultsDiv.classList.remove('hidden');
                } else {
                    resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ไม่พบข้อมูล</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function selectSupervisor(emp, saveAsDefault = true) {
        selectedSupervisor = emp;
        document.getElementById('approverEmail').value = emp.email;
        document.getElementById('approverUserId').value = emp.id || '';
        document.getElementById('supervisorDisplayName').textContent = `${emp.name || emp.email}${emp.email ? ' (' + emp.email + ')' : ''}`;
        document.getElementById('selectedSupervisor').classList.remove('hidden');
        document.getElementById('supervisorSearchContainer').classList.add('hidden');
        document.getElementById('supervisorResults').classList.add('hidden');

        if (saveAsDefault) saveDefaultSupervisor(emp);
    }

    async function saveDefaultSupervisor(emp) {
        try {
            await fetch(`${API_BASE}?controller=bookings&action=saveDefaultSupervisor`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    supervisor_email: emp.email,
                    supervisor_name: emp.name,
                    supervisor_id: emp.id || null
                })
            });
        } catch (error) {
            console.error('Failed to save default supervisor:', error);
        }
    }

    function clearSupervisor() {
        selectedSupervisor = null;
        document.getElementById('approverEmail').value = '';
        document.getElementById('approverUserId').value = '';
        document.getElementById('selectedSupervisor').classList.add('hidden');
        document.getElementById('supervisorSearchContainer').classList.remove('hidden');
        document.getElementById('supervisorSearch').value = '';
    }

    async function searchDriver(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('driverResults');
        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`${API_BASE}?controller=bookings&action=searchManager&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    resultsDiv.innerHTML = data.users.map(emp => `
                        <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick='selectDriver(${JSON.stringify(emp)})'>
                            <div class="w-8 h-8 bg-gradient-to-br from-primary to-red-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                <div class="text-xs text-gray-500">${emp.email}</div>
                            </div>
                            <span class="text-xs px-2 py-1 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                        </div>
                    `).join('');
                    resultsDiv.classList.remove('hidden');
                } else {
                    resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ไม่พบข้อมูล</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function selectDriver(emp) {
        selectedDriver = emp;
        document.getElementById('driverUserId').value = emp.id || '';
        document.getElementById('driverName').value = emp.name || '';
        document.getElementById('driverEmail').value = emp.email || '';
        document.getElementById('driverDisplayName').textContent = `${emp.name} (${emp.email})`;
        document.getElementById('selectedDriver').classList.remove('hidden');
        document.getElementById('driverSearchContainer').classList.add('hidden');
        document.getElementById('driverResults').classList.add('hidden');
    }

    function clearDriver() {
        selectedDriver = null;
        document.getElementById('driverUserId').value = '';
        document.getElementById('driverName').value = '';
        document.getElementById('driverEmail').value = '';
        document.getElementById('selectedDriver').classList.add('hidden');
        document.getElementById('driverSearchContainer').classList.remove('hidden');
        document.getElementById('driverSearch').value = '';
    }

    async function searchPassenger(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('passengerResults');
        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`${API_BASE}?controller=bookings&action=searchManager&query=${encodeURIComponent(query)}`);
                const data = await response.json();

                if (data.success && data.users && data.users.length > 0) {
                    const filtered = data.users.filter(emp => !selectedPassengers.some(p => p.email === emp.email));
                    if (filtered.length > 0) {
                        resultsDiv.innerHTML = filtered.map(emp => `
                            <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick='addPassenger(${JSON.stringify(emp)})'>
                                <div class="w-8 h-8 bg-gradient-to-br from-primary to-red-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                    <div class="text-xs text-gray-500">${emp.email}</div>
                                </div>
                                <span class="text-xs px-2 py-1 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                            </div>
                        `).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ผู้โดยสารทั้งหมดถูกเลือกแล้ว</div>';
                        resultsDiv.classList.remove('hidden');
                    }
                } else {
                    resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">ไม่พบข้อมูล</div>';
                    resultsDiv.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    }

    function addPassenger(emp) {
        selectedPassengers.push(emp);
        document.getElementById('passengerSearch').value = '';
        document.getElementById('passengerResults').classList.add('hidden');
        updatePassengersDisplay();
    }

    function removePassenger(idx) {
        selectedPassengers.splice(idx, 1);
        updatePassengersDisplay();
    }

    function updatePassengersDisplay() {
        const container = document.getElementById('passengersList');
        if (selectedPassengers.length === 0) {
            container.innerHTML = '';
            return;
        }
        container.innerHTML = selectedPassengers.map((p, idx) => `
            <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg">
                <i class="ri-user-line text-gray-500"></i>
                <span class="text-sm">${p.name || p.email}</span>
                <button type="button" class="ml-auto text-gray-400 hover:text-red-500" onclick="removePassenger(${idx})">&times;</button>
            </div>
        `).join('');
    }

    let isSubmitting = false;

    async function submitBooking() {
        if (isSubmitting) return;
        const formData = new FormData(document.getElementById('bookingForm'));
        const submitBtn = document.getElementById('bookingSubmitBtn');

        const data = {
            start_time: formData.get('start_time'),
            end_time: formData.get('end_time'),
            destination: formData.get('destination'),
            purpose: formData.get('purpose'),
            approver_email: formData.get('approver_email'),
            approver_user_id: formData.get('approver_user_id') || null,
            driver_user_id: formData.get('driver_user_id') || null,
            driver_name: formData.get('driver_name') || null,
            driver_email: formData.get('driver_email') || null,
            passengers_detail: selectedPassengers.map(p => ({
                user_id: p.id || null,
                name: p.name,
                email: p.email
            }))
        };

        if (!data.start_time || !data.end_time) {
            showToast('กรุณาระบุวันเวลา', 'error');
            return;
        }
        if (!data.destination) {
            showToast('กรุณาระบุปลายทาง', 'error');
            return;
        }
        if (!data.purpose) {
            showToast('กรุณาระบุวัตถุประสงค์', 'error');
            return;
        }
        if (!data.approver_email) {
            showToast('กรุณาเลือกหัวหน้างาน', 'error');
            return;
        }

        isSubmitting = true;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> <span>กำลังส่ง...</span>';

        try {
            const response = await fetch(`${API_BASE}?controller=bookings&action=create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.success) {
                showToast('สร้างคำขอสำเร็จ', 'success');
                closeBookingModal();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
                resetSubmitButton();
            }
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            resetSubmitButton();
        }
    }

    function resetSubmitButton() {
        isSubmitting = false;
        const submitBtn = document.getElementById('bookingSubmitBtn');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ri-send-plane-line"></i> <span>ส่งคำขอ</span>';
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.getElementById('bookingModal').classList.contains('active')) closeBookingModal();
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) document.querySelectorAll('[id$="Results"]').forEach(el => el.classList.add('hidden'));
    });
</script>