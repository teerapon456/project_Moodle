<?php

/**
 * Car Booking - Settings View
 * Migrated to Tailwind CSS
 */

// Manager only
if (!checkManagerPermission($canView, $canManage, 'ระบบจองรถ')) return;

$settings = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE module_id = ?");
    $stmt->execute([2]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
}

$adminEmails = $settings['admin_emails'] ?? '';
$ccEmails = $settings['cc_emails'] ?? '';
?>

<div class="bg-white border border-gray-200 rounded-xl shadow-sm max-w-3xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
            <i class="ri-settings-3-line text-primary"></i>
            ตั้งค่าระบบ
        </h3>
    </div>

    <div class="p-6 space-y-8">
        <!-- Admin Emails Section -->
        <div class="pb-6 border-b border-gray-100">
            <h4 class="flex items-center gap-2 text-base font-semibold text-gray-900 mb-2">
                <i class="ri-admin-line text-primary"></i>
                อีเมลผู้ดูแลระบบ
            </h4>
            <p class="text-sm text-gray-500 mb-4">อีเมลที่จะได้รับแจ้งเตือนทุกครั้งที่มีการขออนุมัติใช้รถ</p>

            <div id="adminEmailTags" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 bg-gray-50 border border-gray-200 rounded-lg"></div>

            <div class="relative">
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="adminEmailSearch" placeholder="ค้นหาชื่อหรืออีเมลพนักงาน..." autocomplete="off" oninput="searchAdminEmail(this.value)">
                <div id="adminEmailResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
            </div>
        </div>

        <!-- CC Emails Section -->
        <div class="pb-6 border-b border-gray-100">
            <h4 class="flex items-center gap-2 text-base font-semibold text-gray-900 mb-2">
                <i class="ri-mail-send-line text-primary"></i>
                อีเมล CC
            </h4>
            <p class="text-sm text-gray-500 mb-4">อีเมลที่จะถูก CC ในทุกการแจ้งเตือน</p>

            <div id="ccEmailTags" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-3 bg-gray-50 border border-gray-200 rounded-lg"></div>

            <div class="relative">
                <input type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" id="ccEmailSearch" placeholder="ค้นหาชื่อหรืออีเมลพนักงาน..." autocomplete="off" oninput="searchCcEmail(this.value)">
                <div id="ccEmailResults" class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto z-50 hidden"></div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end gap-3">
            <button class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors inline-flex items-center gap-2" onclick="resetSettings()">
                <i class="ri-refresh-line"></i> รีเซ็ต
            </button>
            <button class="px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors inline-flex items-center gap-2" onclick="saveSettings()">
                <i class="ri-save-line"></i> บันทึกการตั้งค่า
            </button>
        </div>
    </div>
</div>

<script>
    // Initialize as simple string arrays from PHP, then we will use them as objects
    let initialAdminEmails = <?= json_encode(array_filter(array_map('trim', explode(',', $adminEmails)))) ?>;
    let initialCcEmails = <?= json_encode(array_filter(array_map('trim', explode(',', $ccEmails)))) ?>;

    // Convert to objects for rich display
    let adminEmails = initialAdminEmails.map(email => ({
        email: email,
        name: email,
        type: '?'
    }));
    let ccEmails = initialCcEmails.map(email => ({
        email: email,
        name: email,
        type: '?'
    }));

    let searchTimeout;

    function renderEmailTags() {
        const adminContainer = document.getElementById('adminEmailTags');
        if (adminEmails.length === 0) {
            adminContainer.innerHTML = '<span class="text-gray-400 text-sm">ยังไม่มีอีเมลผู้ดูแล</span>';
        } else {
            adminContainer.innerHTML = adminEmails.map((user, idx) => `
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-full text-sm shadow-sm group">
                    <i class="ri-user-settings-line text-primary"></i>
                    <span class="font-medium text-gray-700">${user.name !== user.email && user.name !== '?' ? user.name : user.email}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded ${user.type === 'MS' ? 'bg-indigo-100 text-indigo-700' : (user.type === 'Local' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600')} font-bold uppercase">${user.type}</span>
                    <button class="text-gray-400 hover:text-red-500 transition-colors ml-1" onclick="removeAdminEmail(${idx})" title="ลบ">&times;</button>
                </span>
            `).join('');
        }

        const ccContainer = document.getElementById('ccEmailTags');
        if (ccEmails.length === 0) {
            ccContainer.innerHTML = '<span class="text-gray-400 text-sm">ยังไม่มีอีเมล CC</span>';
        } else {
            ccContainer.innerHTML = ccEmails.map((user, idx) => `
                <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-full text-sm shadow-sm group">
                    <i class="ri-mail-add-line text-blue-500"></i>
                    <span class="font-medium text-gray-700">${user.name !== user.email && user.name !== '?' ? user.name : user.email}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded ${user.type === 'MS' ? 'bg-indigo-100 text-indigo-700' : (user.type === 'Local' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600')} font-bold uppercase">${user.type}</span>
                    <button class="text-gray-400 hover:text-red-500 transition-colors ml-1" onclick="removeCcEmail(${idx})" title="ลบ">&times;</button>
                </span>
            `).join('');
        }
    }

    async function searchAdminEmail(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('adminEmailResults');

        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                // Fetch from unified search
                const res = await fetch(`${API_BASE}?controller=bookings&action=searchEmail&query=${encodeURIComponent(query)}`);
                const data = await res.json();

                const allUsers = data.success ? data.users : [];

                if (allUsers.length > 0) {
                    const filtered = allUsers.filter(emp => !adminEmails.includes(emp.email));
                    if (filtered.length > 0) {
                        resultsDiv.innerHTML = filtered.map(emp => {
                            const empStr = encodeURIComponent(JSON.stringify(emp));
                            return `
                            <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick="addAdminEmail('${empStr}')">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary to-blue-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                    <div class="text-xs text-gray-500">${emp.email}</div>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded ${emp.type === 'MS' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'} font-bold uppercase">${emp.type || 'Local'}</span>
                            </div>
                            `;
                        }).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">อีเมลทั้งหมดถูกเพิ่มแล้ว</div>';
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

    async function searchCcEmail(query) {
        clearTimeout(searchTimeout);
        const resultsDiv = document.getElementById('ccEmailResults');

        if (!query || query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            try {
                // Fetch from unified search
                const res = await fetch(`${API_BASE}?controller=bookings&action=searchEmail&query=${encodeURIComponent(query)}`);
                const data = await res.json();

                const allUsers = data.success ? data.users : [];

                if (allUsers.length > 0) {
                    const filtered = allUsers.filter(emp => !ccEmails.includes(emp.email));
                    if (filtered.length > 0) {
                        resultsDiv.innerHTML = filtered.map(emp => {
                            const empStr = encodeURIComponent(JSON.stringify(emp));
                            return `
                            <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick="addCcEmail('${empStr}')">
                                <div class="w-8 h-8 bg-gradient-to-br from-primary to-blue-500 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">${emp.name || emp.email}</div>
                                    <div class="text-xs text-gray-500">${emp.email}</div>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded ${emp.type === 'MS' ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700'} font-bold uppercase">${emp.type || 'Local'}</span>
                            </div>
                            `;
                        }).join('');
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-gray-400">อีเมลทั้งหมดถูกเพิ่มแล้ว</div>';
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

    function addAdminEmail(empStr) {
        const emp = JSON.parse(decodeURIComponent(empStr));
        if (adminEmails.some(u => u.email === emp.email)) {
            showToast('อีเมลนี้มีอยู่แล้ว', 'error');
            return;
        }
        adminEmails.push({
            email: emp.email,
            name: emp.name,
            type: emp.type || 'Local'
        });
        document.getElementById('adminEmailSearch').value = '';
        document.getElementById('adminEmailResults').classList.add('hidden');
        renderEmailTags();
        showToast(`เพิ่ม ${emp.name} แล้ว`, 'success');
    }

    function addCcEmail(empStr) {
        const emp = JSON.parse(decodeURIComponent(empStr));
        if (ccEmails.some(u => u.email === emp.email)) {
            showToast('อีเมลนี้มีอยู่แล้ว', 'error');
            return;
        }
        ccEmails.push({
            email: emp.email,
            name: emp.name,
            type: emp.type || 'Local'
        });
        document.getElementById('ccEmailSearch').value = '';
        document.getElementById('ccEmailResults').classList.add('hidden');
        renderEmailTags();
        showToast(`เพิ่ม ${emp.name} แล้ว`, 'success');
    }

    function removeAdminEmail(index) {
        adminEmails.splice(index, 1);
        renderEmailTags();
    }

    function removeCcEmail(index) {
        ccEmails.splice(index, 1);
        renderEmailTags();
    }

    async function saveSettings() {
        try {
            const response = await fetch(`${API_BASE}?controller=settings&action=save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    admin_emails: adminEmails.map(u => u.email).join(','),
                    cc_emails: ccEmails.map(u => u.email).join(',')
                })
            });
            const result = await response.json();
            if (result.success) showToast('บันทึกการตั้งค่าสำเร็จ', 'success');
            else showToast(result.message || 'เกิดข้อผิดพลาด', 'error');
        } catch (error) {
            showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }

    function resetSettings() {
        adminEmails = [];
        ccEmails = [];
        renderEmailTags();
        showToast('รีเซ็ตแล้ว (กดบันทึกเพื่อยืนยัน)', 'info');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.relative')) {
            document.querySelectorAll('[id$="Results"]').forEach(el => el.classList.add('hidden'));
        }
    });

    renderEmailTags();
</script>