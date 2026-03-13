<?php
/**
 * HR Policy - Dashboard View
 */
?>

<div class="max-w-6xl mx-auto px-6 py-8 flex-grow w-full">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-gray-900">HR Policies</h1>
                <?php if ($canManage): ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">Manage</span>
                <?php else: ?>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">View Only</span>
                <?php endif; ?>
            </div>
            <p class="text-gray-500 text-sm mt-2">ระเบียบและนโยบายบริษัท (เชื่อมต่อกับ AI Copilot)</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Search input -->
            <div class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="policy-search" placeholder="ค้นหานโยบาย..." class="pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none w-64 shadow-sm" onkeyup="filterPolicies()">
            </div>
            <?php if ($canManage): ?>
            <button onclick="hrPolicyModal.open()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors shadow-sm">
                <i class="ri-add-line border-2 rounded-full font-bold"></i> เพิ่มนโยบาย
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Policies Content - List View -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-x-auto">
        <table class="w-full text-left min-w-[800px]" id="hr-policies-table">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-600 w-1/2">หัวข้อและเนื้อหา</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-600">หมวดหมู่</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-center">สถานะ</th>
                    <th class="px-6 py-4 text-sm font-semibold text-gray-600 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody id="hr-policies-tbody" class="divide-y divide-gray-100">
                <tr><td colspan="4" class="text-center py-10 text-gray-500"><div class="animate-pulse">กำลังโหลดข้อมูล...</div></td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const hrPolicyApiUrl = (window.APP_BASE_PATH === '/' ? '' : window.APP_BASE_PATH) + '/Modules/HRPolicies/api.php';
    let hrPoliciesCache = [];
    const canManage = <?= $canManage ? 'true' : 'false' ?>;

    window.loadHRPolicies = async function() {
        const tbody = document.getElementById('hr-policies-tbody');
        if (!tbody) return;

        try {
            const res = await fetch(hrPolicyApiUrl + '?action=list');
            const result = await res.json();
            if (result.success) {
                hrPoliciesCache = result.data || [];
                renderHRPolicies(hrPoliciesCache);
            } else {
                throw new Error(result.message);
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-red-500">โหลดข้อมูลไม่สำเร็จ: ${err.message}</td></tr>`;
        }
    };

    window.filterPolicies = function() {
        const search = document.getElementById('policy-search').value.toLowerCase();
        const filtered = hrPoliciesCache.filter(p => 
            (p.title && p.title.toLowerCase().includes(search)) || 
            (p.content && p.content.toLowerCase().includes(search)) || 
            (p.category && p.category.toLowerCase().includes(search))
        );
        renderHRPolicies(filtered);
    };

    function renderHRPolicies(policies) {
        const tbody = document.getElementById('hr-policies-tbody');
        if (!policies.length) {
            tbody.innerHTML = `<tr><td colspan="4" class="text-center py-10 text-gray-500">ไม่พบข้อมูลนโยบาย</td></tr>`;
            return;
        }

        tbody.innerHTML = policies.map(p => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4">
                    <div class="text-sm font-semibold text-gray-900">${p.title}</div>
                    <div class="text-xs text-gray-500 mt-1 w-full max-w-md line-clamp-2 whitespace-normal">${p.content}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-none rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">${p.category || 'General'}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold leading-none rounded-full ${p.is_active == 1 ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800'}">
                        ${p.is_active == 1 ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    ${canManage ? `
                        <button class="text-indigo-600 hover:text-indigo-900 mx-1 p-1 rounded hover:bg-indigo-50 transition-colors" onclick="hrPolicyModal.history(${p.id})" title="History"><i class="ri-history-line text-lg"></i></button>
                        <button class="text-blue-600 hover:text-blue-900 mx-1 p-1 rounded hover:bg-blue-50 transition-colors" onclick="hrPolicyModal.open(${p.id})" title="Edit"><i class="ri-edit-line text-lg"></i></button>
                        <button class="text-red-600 hover:text-red-900 mx-1 p-1 rounded hover:bg-red-50 transition-colors" onclick="deleteHRPolicy(${p.id})" title="Delete"><i class="ri-delete-bin-line text-lg"></i></button>
                    ` : `
                        <button class="text-indigo-600 hover:text-indigo-900 mx-1 p-1 rounded hover:bg-indigo-50 transition-colors" onclick="hrPolicyModal.history(${p.id})" title="History"><i class="ri-history-line text-lg"></i></button>
                        <button class="text-gray-600 hover:text-gray-900 mx-1 p-1 rounded hover:bg-gray-50 transition-colors" onclick="hrPolicyModal.view(${p.id})" title="View"><i class="ri-eye-line text-lg"></i></button>
                    `}
                </td>
            </tr>
        `).join('');
    }

    window.deleteHRPolicy = function(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "หากลบแล้ว AI จะไม่สามารถเข้าถึงนโยบายนี้ได้อีก คุณแน่ใจหรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    let formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);
                    
                    const res = await fetch(hrPolicyApiUrl, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        Swal.fire('ลบแล้ว!', 'นโยบายถูกลบเรียบร้อยแล้ว', 'success');
                        loadHRPolicies();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (err) {
                    Swal.fire('ข้อผิดพลาด', err.message, 'error');
                }
            }
        });
    };

    document.addEventListener('DOMContentLoaded', loadHRPolicies);
</script>

<?php include __DIR__ . '/partials/policy-modal.php'; ?>
