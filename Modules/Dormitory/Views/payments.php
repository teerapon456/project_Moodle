<?php
// payments.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;
?>
<!-- Payments View - Migrated to Tailwind -->
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <div class="flex flex-wrap gap-3">
        <input type="month" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterMonth">
        <select class="min-w-[150px] px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterMethod">
            <option value="">ทุกวิธีชำระ</option>
            <option value="transfer">โอนเงิน</option>
            <option value="cash">เงินสด</option>
            <option value="payroll_deduct">หักเงินเดือน</option>
        </select>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6" id="statsGrid">
    <div class="bg-white border-l-4 border-success rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-success text-2xl mb-3">
            <i class="ri-money-dollar-circle-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="totalPayments">฿0</div>
        <div class="text-gray-500 text-sm">รับชำระเดือนนี้</div>
    </div>
    <div class="bg-white border-l-4 border-info rounded-xl p-5 shadow-sm">
        <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-2xl mb-3">
            <i class="ri-file-list-3-line"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900" id="totalTransactions">0</div>
        <div class="text-gray-500 text-sm">รายการทั้งหมด</div>
    </div>
</div>

<!-- Payments Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
        <i class="ri-exchange-dollar-line text-xl text-primary"></i>
        <h3 class="text-lg font-semibold text-gray-900">ประวัติการชำระเงิน</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เลขที่บิล</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ห้อง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้พักอาศัย</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวนเงิน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">วิธีชำระ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">เลขอ้างอิง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้บันทึก</th>
                </tr>
            </thead>
            <tbody id="paymentsTable" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    let payments = [];

    document.addEventListener('DOMContentLoaded', async () => {
        document.getElementById('filterMonth').value = new Date().toISOString().slice(0, 7);

        // Check permissions safely (could be string, array, or undefined)
        const perms = USER.permissions || [];
        const isAdminUser = USER.role === 'admin' ||
            (Array.isArray(perms) && (perms.includes('admin') || perms.includes('manage'))) ||
            (typeof perms === 'string' && (perms.includes('admin') || perms.includes('manage')));
        if (!isAdminUser) {
            document.getElementById('statsGrid').style.display = 'none';
        }

        await loadPayments();

        document.getElementById('filterMonth').addEventListener('change', loadPayments);
        document.getElementById('filterMethod').addEventListener('change', loadPayments);
    });

    const methodColors = {
        'transfer': 'bg-blue-50 text-blue-700',
        'cash': 'bg-emerald-50 text-emerald-700',
        'payroll_deduct': 'bg-amber-50 text-amber-700'
    };

    async function loadPayments() {
        try {
            const month = document.getElementById('filterMonth').value;
            const result = await apiCall('billing', 'summary', {
                month
            });
            const invoicesResult = await apiCall('billing', 'listInvoices', {
                month,
                status: 'paid'
            });
            payments = invoicesResult.invoices || [];
            renderPayments();
            updateSummary(result.summary);
        } catch (error) {
            console.error('Failed to load payments:', error);
        }
    }

    function renderPayments() {
        const tbody = document.getElementById('paymentsTable');

        if (payments.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">
                    <i class="ri-exchange-dollar-line text-3xl mb-2 block"></i>
                    <p>ไม่พบรายการชำระเงิน</p>
                </td>
            </tr>`;
            return;
        }

        tbody.innerHTML = payments.map(p => `
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-600">${formatDate(p.updated_at)}</td>
            <td class="px-4 py-3"><span class="font-semibold text-primary">${p.invoice_number}</span></td>
            <td class="px-4 py-3 text-gray-600">${p.building_code}${p.room_number}</td>
            <td class="px-4 py-3 text-gray-600">${escapeHtml(p.employee_name)}</td>
            <td class="px-4 py-3 font-semibold text-gray-900">${formatCurrency(p.paid_amount)}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium ${methodColors[p.payment_method] || 'bg-gray-100 text-gray-600'}">
                    <i class="ri-${getMethodIcon(p.payment_method)}"></i>
                    ${getMethodText(p.payment_method)}
                </span>
            </td>
            <td class="px-4 py-3 font-mono text-sm text-gray-500">${p.reference_number || '-'}</td>
            <td class="px-4 py-3 text-gray-600">${escapeHtml(p.recorded_by) || '-'}</td>
        </tr>
    `).join('');
    }

    function updateSummary(summary) {
        if (!summary) return;
        document.getElementById('totalPayments').textContent = formatCurrency(summary.paid_amount || 0);
        document.getElementById('totalTransactions').textContent = payments.length;
    }

    function getMethodText(method) {
        const map = {
            'transfer': 'โอนเงิน',
            'cash': 'เงินสด',
            'payroll_deduct': 'หักเงินเดือน'
        };
        return map[method] || 'โอนเงิน';
    }

    function getMethodIcon(method) {
        const map = {
            'transfer': 'bank-line',
            'cash': 'money-dollar-circle-line',
            'payroll_deduct': 'user-received-line'
        };
        return map[method] || 'bank-line';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>