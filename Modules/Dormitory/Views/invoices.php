<?php
// invoices.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;
?>
<!-- Invoices View - Migrated to Tailwind -->
<!-- Filters -->
<div class="flex flex-col md:flex-row flex-wrap items-start md:items-center justify-between gap-3 mb-5" id="filterContainer">
    <div class="flex flex-wrap gap-2 w-full md:w-auto">
        <select class="flex-1 md:flex-none md:min-w-[140px] px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterBuilding">
            <option value="">ทุกอาคาร</option>
        </select>
        <input type="month" class="flex-1 md:flex-none px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterMonth">
        <select class="flex-1 md:flex-none md:min-w-[120px] px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" id="filterStatus">
            <option value="">ทุกสถานะ</option>
            <option value="pending">รอชำระ</option>
            <option value="paid">ชำระแล้ว</option>
            <option value="partial">ชำระบางส่วน</option>
            <option value="overdue">เกินกำหนด</option>
            <option value="cancelled">ยกเลิก</option>
        </select>
    </div>
    <!-- Container for pay button (for residents) -->
    <div id="payBtnContainer" class="hidden"></div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5 mb-5">
    <div class="bg-white border-l-4 border-primary rounded-xl p-3 md:p-5 shadow-sm">
        <div class="flex items-center gap-2 md:gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-50 flex items-center justify-center text-primary text-lg md:text-2xl">
                <i class="ri-file-list-3-fill"></i>
            </div>
            <div>
                <div class="text-xl md:text-3xl font-bold text-gray-900" id="totalInvoices">0</div>
                <div class="text-gray-500 text-xs md:text-sm">บิลทั้งหมด</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-info rounded-xl p-3 md:p-5 shadow-sm">
        <div class="flex items-center gap-2 md:gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-blue-50 flex items-center justify-center text-info text-lg md:text-2xl">
                <i class="ri-money-dollar-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-3xl font-bold text-gray-900 truncate" id="totalAmount">฿0</div>
                <div class="text-gray-500 text-xs md:text-sm">ยอดรวม</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-success rounded-xl p-3 md:p-5 shadow-sm">
        <div class="flex items-center gap-2 md:gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-emerald-50 flex items-center justify-center text-success text-lg md:text-2xl">
                <i class="ri-checkbox-circle-fill"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-3xl font-bold text-gray-900 truncate" id="paidAmount">฿0</div>
                <div class="text-gray-500 text-xs md:text-sm">ชำระแล้ว</div>
            </div>
        </div>
    </div>
    <div class="bg-white border-l-4 border-danger rounded-xl p-3 md:p-5 shadow-sm">
        <div class="flex items-center gap-2 md:gap-3">
            <div class="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-red-50 flex items-center justify-center text-danger text-lg md:text-2xl">
                <i class="ri-error-warning-fill"></i>
            </div>
            <div class="min-w-0">
                <div class="text-lg md:text-3xl font-bold text-gray-900 truncate" id="outstandingAmount">฿0</div>
                <div class="text-gray-500 text-xs md:text-sm">ค้างชำระ</div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Table -->
<div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
    <!-- Mobile Card View -->
    <div class="md:hidden divide-y divide-gray-100" id="invoiceCards">
        <div class="flex items-center justify-center py-12">
            <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin"></div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full" id="invoiceTableWrap">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เลขที่บิล</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ห้อง</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้พักอาศัย</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เดือน</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าไฟ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ค่าน้ำ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รวม</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody id="invoiceTable" class="divide-y divide-gray-100">
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center">
                        <div class="w-8 h-8 border-4 border-gray-200 border-t-primary rounded-full animate-spin mx-auto"></div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="paymentModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">บันทึกการชำระเงิน</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form id="paymentForm" onsubmit="handlePayment(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" id="paymentInvoiceId" name="invoice_id">

                <div id="invoiceSummary" class="p-4 bg-gray-50 rounded-lg space-y-2"></div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">จำนวนเงิน *</label>
                    <input type="number" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="amount" step="0.01" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">วันที่ชำระ *</label>
                    <input type="date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="payment_date" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">วิธีชำระ</label>
                    <select class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="payment_method">
                        <option value="transfer">โอนเงิน</option>
                        <option value="cash">เงินสด</option>
                        <option value="payroll_deduct">หักเงินเดือน</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">เลขอ้างอิง</label>
                    <input type="text" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="reference_number" placeholder="เลขที่อ้างอิง/สลิป">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">แนบสลิป (ไม่บังคับ)</label>
                    <input type="file" name="proof_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark cursor-pointer">
                    <p class="mt-1 text-xs text-gray-500">รองรับไฟล์ภาพ JPG, PNG หรือเอกสาร PDF ขนาดไม่เกิน 5MB</p>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">หมายเหตุ</label>
                    <textarea class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-y" name="notes" rows="2"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('paymentModal')">ยกเลิก</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors shadow-sm">
                    <i class="ri-check-line"></i>
                    บันทึกการชำระ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Invoice Detail Modal -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="invoiceDetailModal">
    <div class="bg-white rounded-xl w-full max-w-xl max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">รายละเอียดใบแจ้งหนี้</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('invoiceDetailModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="invoiceDetailContent">
            <!-- Content injected via JS -->
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
            <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('invoiceDetailModal')">ปิด</button>
        </div>
    </div>
</div>

<!-- Slip Payment Modal (Resident) -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="slipPaymentModal">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">แจ้งชำระเงิน</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('slipPaymentModal')">&times;</button>
        </div>
        <form id="slipPaymentForm" onsubmit="handleSlipPayment(event)">
            <div class="p-6 overflow-y-auto flex-1 space-y-4">
                <div id="slipPaymentSummary"></div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">วันที่โอน *</label>
                    <input type="date" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="transfer_date" required id="transferDateInput">
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">เวลาที่โอน *</label>
                    <input type="time" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="transfer_time" required>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700">หลักฐานการโอน (Slip) *</label>
                    <input type="file" class="w-full px-3 py-2.5 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary" name="proof_file" accept="image/*,application/pdf" required>
                    <small class="text-gray-500 mt-1 block">รองรับไฟล์ภาพ (JPG, PNG) หรือ PDF</small>
                </div>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <button type="button" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors" onclick="closeModal('slipPaymentModal')">ยกเลิก</button>
                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors shadow-sm">
                    <i class="ri-send-plane-fill"></i>
                    ยืนยันแจ้งชำระ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Verify Payment Modal (Admin) -->
<div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 opacity-0 invisible transition-all duration-200 p-5" id="verifyPaymentModal">
    <div class="bg-white rounded-xl w-full max-w-xl max-h-[calc(100vh-40px)] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">ตรวจสอบการชำระเงิน</h3>
            <button class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" onclick="closeModal('verifyPaymentModal')">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <input type="hidden" id="verifyPaymentId">

            <div class="text-center mb-5 p-3 bg-gray-50 rounded-lg">
                <img id="verifyProofImage" src="" alt="Payment Slip" class="max-w-full max-h-[400px] hidden rounded mx-auto">
                <a id="verifyProofLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden mt-3 inline-flex items-center gap-2 px-4 py-2 bg-info hover:bg-blue-600 text-white rounded-lg text-sm font-medium">
                    <i class="ri-download-line"></i> ดาวน์โหลดไฟล์
                </a>
            </div>

            <div id="verifyDetails" class="p-4 bg-gray-50 rounded-lg space-y-2">
                <!-- Details injected via JS -->
            </div>
        </div>
        <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-danger hover:bg-red-600 text-white rounded-lg font-medium transition-colors" onclick="confirmReject()">
                <i class="ri-close-circle-line"></i> ปฏิเสธ
            </button>
            <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-success hover:bg-emerald-600 text-white rounded-lg font-medium transition-colors" onclick="confirmApprove()">
                <i class="ri-checkbox-circle-line"></i> อนุมัติ
            </button>
        </div>
    </div>
</div>

<style>
    /* Modal active state */
    .fixed.opacity-0.invisible[id$="Modal"].active {
        opacity: 1;
        visibility: visible;
    }
</style>

<script>
    let invoices = [];
    let selectedInvoices = new Set();

    // Helper to fix proof file paths (support both old full paths and new filename-only format)
    function fixProofUrl(url) {
        if (!url) return '';

        // New format: just "2025/12/slip_xxx.png" - build full URL
        if (url.match(/^\d{4}\/\d{2}\/slip_/)) {
            return (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/Modules/Dormitory/public/uploads/slips/' + url;
        }

        // Old format with full path - replace old base with current
        const oldBases = ['/myhr_services', '/MyHR Portal', '/MyHR%20Portal'];
        for (const oldBase of oldBases) {
            if (url.includes(oldBase)) {
                return url.replace(oldBase, (typeof BASE_URL !== 'undefined' ? BASE_URL : ''));
            }
        }

        // Relative path starting with /Modules
        if (url.startsWith('/Modules/')) {
            return (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + url;
        }

        return url;
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const monthInput = document.getElementById('filterMonth');
        monthInput.value = new Date().toISOString().slice(0, 7);

        if (!isAdmin) {
            document.getElementById('filterBuilding').style.display = 'none';
        } else {
            await loadBuildings();
        }

        await loadInvoices();

        if (isAdmin) {
            document.getElementById('filterBuilding').addEventListener('change', loadInvoices);
        }
        document.getElementById('filterMonth').addEventListener('change', loadInvoices);
        document.getElementById('filterStatus').addEventListener('change', loadInvoices);

        // Add pay button for residents
        if (!isAdmin) {
            const container = document.getElementById('payBtnContainer');
            if (container) {
                container.classList.remove('hidden');
                container.innerHTML = `
                    <button id="paySelectedBtn" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg font-medium transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" disabled onclick="openSlipPaymentModal()">
                        <i class="ri-secure-payment-fill"></i> ชำระเงิน (0)
                    </button>
                `;
            }
        }
    });

    async function loadBuildings() {
        try {
            const result = await apiCall('buildings', 'list');
            const select = document.getElementById('filterBuilding');
            result.buildings.forEach(b => {
                const option = document.createElement('option');
                option.value = b.id;
                option.textContent = `${b.code} - ${b.name}`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load buildings:', error);
        }
    }

    async function loadInvoices() {
        try {
            const params = {};
            const month = document.getElementById('filterMonth').value;
            const building = document.getElementById('filterBuilding').value;
            const status = document.getElementById('filterStatus').value;

            if (month) params.month = month;
            if (building) params.building_id = building;
            if (status) params.status = status;

            const result = await apiCall('billing', 'listInvoices', params);
            invoices = result.invoices || [];
            selectedInvoices.clear();
            updatePayButton();
            renderInvoices();
            updateSummary();
        } catch (error) {
            console.error('Failed to load invoices:', error);
        }
    }

    function renderInvoices() {
        const tbody = document.getElementById('invoiceTable');
        const cardsContainer = document.getElementById('invoiceCards');

        if (invoices.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                    <i class="ri-file-list-3-line text-3xl mb-2 block"></i>
                    <p>ไม่พบบิล</p>
                </td>
            </tr>`;
            cardsContainer.innerHTML = `
                <div class="py-12 text-center text-gray-400">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-file-list-3-line text-3xl"></i>
                    </div>
                    <p class="font-medium">ไม่พบบิล</p>
                </div>`;
            return;
        }

        const statusColors = {
            'pending': 'bg-amber-100 text-amber-800',
            'pending_verification': 'bg-blue-100 text-blue-800',
            'paid': 'bg-emerald-100 text-emerald-800',
            'partial': 'bg-blue-100 text-blue-800',
            'overdue': 'bg-red-100 text-red-800',
            'cancelled': 'bg-gray-100 text-gray-600'
        };

        const borderColors = {
            'pending': 'border-l-amber-400',
            'pending_verification': 'border-l-blue-400',
            'paid': 'border-l-emerald-500',
            'partial': 'border-l-blue-400',
            'overdue': 'border-l-red-500',
            'cancelled': 'border-l-gray-300'
        };

        // Mobile Cards
        cardsContainer.innerHTML = invoices.map(inv => {
            const canPay = (inv.status === 'pending' || inv.status === 'partial' || inv.status === 'overdue') && !isAdmin;
            return `
            <div class="p-4 ${borderColors[inv.status] || 'border-l-gray-300'} border-l-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="font-bold text-primary">${inv.invoice_number}</span>
                        <span class="text-gray-500 text-sm ml-2">${inv.month_cycle}</span>
                    </div>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[inv.status] || 'bg-gray-100 text-gray-600'}">
                        ${getStatusText(inv.status)}
                    </span>
                </div>
                <div class="text-sm text-gray-600 mb-2">
                    <span class="flex items-center gap-1">
                        <i class="ri-home-4-fill text-gray-400"></i> ${inv.building_code}${inv.room_number}
                        <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${inv.room_type_name || '-'}</span>
                    </span>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 mb-3">
                    <span><i class="ri-flashlight-fill text-amber-500"></i> ค่าไฟ ${formatCurrency(inv.electricity_amount)}</span>
                    <span><i class="ri-drop-fill text-blue-500"></i> ค่าน้ำ ${formatCurrency(inv.water_amount)}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="text-lg font-bold text-gray-900">${formatCurrency(inv.total_amount)}</div>
                    <div class="flex items-center gap-2">
                        ${canPay ? `<label class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 rounded-lg text-sm cursor-pointer">
                            <input type="checkbox" class="inv-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" value="${inv.id}" onchange="updateSelection()">
                            เลือก
                        </label>` : ''}
                        <button class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg" onclick="viewInvoice(${inv.id})">
                            <i class="ri-eye-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');

        // Desktop Table
        tbody.innerHTML = invoices.map(inv => {
            const canPay = (inv.status === 'pending' || inv.status === 'partial' || inv.status === 'overdue') && !isAdmin;
            const checkboxHtml = !isAdmin ? `
                <td class="px-4 py-3">
                    ${canPay ? `<input type="checkbox" class="inv-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" value="${inv.id}" onchange="updateSelection()">` : ''}
                </td>
            ` : '';

            let actionButtons = '';
            if (isAdmin) {
                actionButtons = `
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" onclick="viewInvoice(${inv.id})" title="ดูรายละเอียด">
                        <i class="ri-eye-line"></i>
                    </button>
                `;
                if (inv.status === 'pending_verification') {
                    actionButtons += `
                        <button class="p-2 text-primary hover:text-primary-dark hover:bg-red-50 rounded-lg transition-colors" onclick="openVerifyModal(${inv.id})" title="ตรวจสอบการชำระ">
                            <i class="ri-file-search-line"></i>
                        </button>
                    `;
                } else if (inv.status === 'pending' || inv.status === 'partial' || inv.status === 'overdue') {
                    actionButtons += `
                        <button class="p-2 text-success hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" onclick="openPaymentModal(${inv.id})" title="บันทึกรับชำระเงิน">
                            <i class="ri-money-dollar-circle-line"></i>
                        </button>
                    `;
                }
                if (inv.status !== 'cancelled') {
                    actionButtons += `
                        <button class="p-2 text-danger hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" onclick="cancelInvoice(${inv.id}, '${inv.invoice_number}')" title="ยกเลิกบิล">
                            <i class="ri-close-circle-line"></i>
                        </button>
                    `;
                }
            } else {
                actionButtons = `
                    <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors" onclick="viewInvoice(${inv.id})" title="ดูรายละเอียด">
                        <i class="ri-eye-line"></i>
                    </button>
                `;
                if (inv.status === 'pending_verification') {
                    actionButtons += '<span class="text-xs text-warning ml-2">รอตรวจสอบ</span>';
                }
            }

            return `
            <tr class="hover:bg-gray-50 transition-colors">
                ${checkboxHtml}
                <td class="px-4 py-3"><span class="font-semibold text-primary">${inv.invoice_number}</span></td>
                <td class="px-4 py-3 text-gray-600">
                    ${inv.building_code}${inv.room_number}
                    <span class="block text-xs text-gray-400 mt-0.5">${inv.room_type_name || '-'}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 max-w-[150px] truncate">
                    ${inv.room_occupants ? escapeHtml(inv.room_occupants) : (escapeHtml(inv.bill_owner_name || inv.employee_name || '-'))}
                </td>
                <td class="px-4 py-3 text-gray-600">${inv.month_cycle}</td>
                <td class="px-4 py-3 text-gray-600">${formatCurrency(inv.electricity_amount)}</td>
                <td class="px-4 py-3 text-gray-600">${formatCurrency(inv.water_amount)}</td>
                <td class="px-4 py-3 font-semibold text-gray-900">${formatCurrency(inv.total_amount)}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium ${statusColors[inv.status] || 'bg-gray-100 text-gray-600'}">
                        ${getStatusText(inv.status)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">${actionButtons}</div>
                </td>
            </tr>
        `;
        }).join('');
    }

    function toggleAll(source) {
        document.querySelectorAll('.inv-checkbox').forEach(cb => cb.checked = source.checked);
        updateSelection();
    }

    function updateSelection() {
        selectedInvoices.clear();
        document.querySelectorAll('.inv-checkbox:checked').forEach(cb => selectedInvoices.add(cb.value));
        updatePayButton();
    }

    function updatePayButton() {
        const btn = document.getElementById('paySelectedBtn');
        if (btn) {
            btn.disabled = selectedInvoices.size === 0;
            btn.innerHTML = `<i class="ri-secure-payment-line"></i> ชำระเงิน (${selectedInvoices.size})`;
        }
    }

    function openSlipPaymentModal() {
        if (selectedInvoices.size === 0) return;

        let total = 0;
        let invList = [];
        invoices.forEach(inv => {
            if (selectedInvoices.has(String(inv.id))) {
                total += (parseFloat(inv.total_amount) - parseFloat(inv.paid_amount || 0));
                invList.push(inv);
            }
        });

        document.getElementById('slipPaymentSummary').innerHTML = `
            <div class="p-4 bg-gray-50 rounded-lg mb-4">
                <h4 class="font-medium text-gray-900 mb-3">รายการที่จะชำระ (${invList.length} รายการ)</h4>
                ${invList.map(i => `
                    <div class="flex justify-between py-1.5 text-sm">
                        <span class="text-gray-600">${i.invoice_number} (${i.month_cycle})</span>
                        <span class="font-medium">${formatCurrency(i.total_amount - i.paid_amount)}</span>
                    </div>
                `).join('')}
                <div class="flex justify-between py-2 mt-3 border-t border-gray-200 font-semibold">
                    <span>ยอดรวมทั้งสิ้น:</span>
                    <span class="text-primary text-lg">${formatCurrency(total)}</span>
                </div>
            </div>
        `;

        document.getElementById('slipPaymentForm').reset();
        openModal('slipPaymentModal');
    }

    async function handleSlipPayment(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        const fileInput = form.querySelector('input[name="proof_file"]');
        if (fileInput.files.length > 0 && fileInput.files[0].size > 5 * 1024 * 1024) {
            showToast('ไฟล์มีขนาดใหญ่เกิน 5MB', 'error');
            return;
        }

        const formData = new FormData(form);
        formData.append('invoice_ids', Array.from(selectedInvoices).join(','));

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังอัปโหลด...';

        try {
            await apiCall('billing', 'createPayment', formData, 'POST');
            showToast('แจ้งชำระเงินเรียบร้อย รอการตรวจสอบ', 'success');
            closeModal('slipPaymentModal');
            loadInvoices();
        } catch (error) {
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-fill"></i> ยืนยันแจ้งชำระ';
        }
    }

    async function openPaymentModal(invoiceId) {
        try {
            const result = await apiCall('billing', 'getInvoice', {
                id: invoiceId
            });
            const inv = result.invoice;

            document.getElementById('paymentInvoiceId').value = inv.id;

            const remainingAmt = (parseFloat(inv.total_amount) - parseFloat(inv.paid_amount || 0)).toFixed(2);
            document.querySelector('#paymentForm input[name="amount"]').value = remainingAmt;
            document.querySelector('#paymentForm input[name="payment_date"]').value = new Date().toISOString().split('T')[0];

            document.getElementById('invoiceSummary').innerHTML = `
                <div class="flex justify-between text-sm mb-1"><span class="text-gray-600">บิล:</span><span class="font-medium">${inv.invoice_number}</span></div>
                <div class="flex justify-between text-sm mb-1"><span class="text-gray-600">ยอดรวม:</span><span class="font-medium">${formatCurrency(inv.total_amount)}</span></div>
                <div class="flex justify-between text-sm text-primary font-medium border-t border-gray-200 mt-2 pt-2"><span class="text-gray-900">ยอดค้างชำระ:</span><span>${formatCurrency(remainingAmt)}</span></div>
            `;

            openModal('paymentModal');
        } catch (error) {
            showToast('ไม่สามารถโหลดข้อมูลบิลได้', 'error');
        }
    }

    async function handlePayment(e) {
        e.preventDefault();
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');

        const fileInput = form.querySelector('input[name="proof_file"]');
        if (fileInput && fileInput.files.length > 0 && fileInput.files[0].size > 5 * 1024 * 1024) {
            showToast('ไฟล์แนบมีขนาดใหญ่เกิน 5MB', 'error');
            return;
        }

        const formData = new FormData(form);

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> กำลังบันทึก...';

        try {
            await apiCall('billing', 'recordPayment', formData, 'POST');
            showToast('บันทึกการชำระเงินเรียบร้อย', 'success');
            closeModal('paymentModal');
            form.reset();
            loadInvoices();
        } catch (error) {
            // Error is handled by apiCall
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-check-line"></i> บันทึกการชำระ';
        }
    }

    function updateSummary() {
        const total = invoices.length;
        const totalAmount = invoices.reduce((sum, i) => sum + parseFloat(i.total_amount || 0), 0);
        const paidAmount = invoices.reduce((sum, i) => sum + parseFloat(i.paid_amount || 0), 0);

        document.getElementById('totalInvoices').textContent = total;
        document.getElementById('totalAmount').textContent = formatCurrency(totalAmount);
        document.getElementById('paidAmount').textContent = formatCurrency(paidAmount);
        document.getElementById('outstandingAmount').textContent = formatCurrency(totalAmount - paidAmount);
    }

    function getStatusText(status) {
        const map = {
            'draft': 'ร่าง',
            'pending': 'รอชำระ',
            'pending_verification': 'รอตรวจสอบ',
            'paid': 'ชำระแล้ว',
            'partial': 'ชำระบางส่วน',
            'overdue': 'เกินกำหนด',
            'cancelled': 'ยกเลิก'
        };
        return map[status] || status;
    }

    async function viewInvoice(id) {
        try {
            const result = await apiCall('billing', 'getInvoice', {
                id
            });
            const inv = result.invoice;

            const statusColors = {
                'pending': 'bg-amber-100 text-amber-800',
                'paid': 'bg-emerald-100 text-emerald-800',
                'overdue': 'bg-red-100 text-red-800'
            };

            let html = `
                <div class="flex items-center justify-between mb-5 pb-4 border-b border-gray-100">
                    <h4 class="text-lg font-semibold text-primary">${inv.invoice_number}</h4>
                    <span class="px-3 py-1 rounded-full text-xs font-medium ${statusColors[inv.status] || 'bg-gray-100 text-gray-600'}">${getStatusText(inv.status)}</span>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-lg space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ห้อง:</span>
                        <div class="text-right">
                            <span class="font-medium">${inv.building_code || ''}${inv.room_number || ''}</span>
                            <span class="ml-2 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">${inv.room_type_name || '-'}</span>
                        </div>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-600">ผู้พักอาศัย:</span><span>${escapeHtml(inv.employee_name || '-')}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">รอบบิล:</span><span>${inv.month_cycle}</span></div>
                    <hr class="my-3 border-gray-200">
                    <div class="flex justify-between"><span class="text-gray-600">ค่าห้อง:</span><span>${formatCurrency(inv.rent_amount || 0)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">ค่าไฟฟ้า (${inv.electricity_units || 0} หน่วย):</span><span>${formatCurrency(inv.electricity_amount || 0)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">ค่าน้ำ (${inv.water_units || 0} หน่วย):</span><span>${formatCurrency(inv.water_amount || 0)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">ค่าบริการอื่นๆ:</span><span>${formatCurrency(inv.common_fee || 0)}</span></div>
                    <div class="flex justify-between pt-3 border-t border-gray-200 font-semibold">
                        <span>ยอดรวม:</span><span class="text-primary text-lg">${formatCurrency(inv.total_amount)}</span>
                    </div>
                    <div class="flex justify-between"><span class="text-gray-600">ชำระแล้ว:</span><span class="text-success">${formatCurrency(inv.paid_amount || 0)}</span></div>
                    <div class="flex justify-between"><span class="text-gray-600">คงเหลือ:</span><span>${formatCurrency((inv.total_amount || 0) - (inv.paid_amount || 0))}</span></div>
                </div>
            `;

            if (inv.payment_info && inv.payment_info.proof_file) {
                const pm = inv.payment_info;
                const proofUrl = fixProofUrl(pm.proof_file);

                let combinedInvoicesHtml = '';
                if (pm.invoices && pm.invoices.length > 1) {
                    combinedInvoicesHtml = `
                        <div class="mb-3 p-3 bg-white/50 border border-green-100 rounded">
                            <span class="text-[10px] font-bold text-green-600 uppercase tracking-wider">ชำระรวมกับรายการอื่น:</span>
                            <div class="mt-2 space-y-1.5">
                                ${pm.invoices.map(i => `
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="${i.id == inv.id ? 'font-bold text-green-700' : 'text-gray-600'}">#${i.invoice_number} (ห้อง ${i.room_number})</span>
                                        <span class="font-medium">${formatCurrency(i.total_amount)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }

                html += `
                    <div class="mt-5 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <h5 class="font-medium text-success mb-3 flex items-center gap-2"><i class="ri-money-dollar-circle-line"></i> หลักฐานการชำระเงิน</h5>
                        ${combinedInvoicesHtml}
                        <div class="flex justify-between text-sm mb-2"><span class="text-gray-600">วันที่โอน:</span><span>${pm.payment_date || '-'} ${pm.payment_time || ''}</span></div>
                        <div class="flex justify-between text-base mb-3 pt-2 border-t border-green-100 font-bold text-green-700"><span class="text-gray-600">ยอดรวมในสลิป:</span><span>${formatCurrency(pm.total_payment_amount || pm.total_amount)}</span></div>
                        <div class="text-center mt-4">
                            ${proofUrl.match(/\.(jpeg|jpg|png|gif)$/i) ?
                                `<img src="${proofUrl}" alt="Payment Slip" class="max-w-full max-h-[300px] rounded-lg shadow-md mx-auto cursor-pointer" onclick="window.open('${proofUrl}', '_blank')">` :
                                `<a href="${proofUrl}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-4 py-2 bg-info hover:bg-blue-600 text-white rounded-lg text-sm font-medium"><i class="ri-download-line"></i> ดาวน์โหลดหลักฐาน</a>`
                            }
                        </div>
                    </div>
                `;
            }

            document.getElementById('invoiceDetailContent').innerHTML = html;
            openModal('invoiceDetailModal');
        } catch (error) {
            showToast('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
        }
    }

    async function cancelInvoice(id, invoiceNumber) {
        const reason = await showPrompt(`กรุณาระบุเหตุผลในการยกเลิก`, `ยกเลิกบิล ${invoiceNumber}`);
        if (reason === null) return;

        try {
            await apiCall('billing', 'cancelInvoice', {
                id,
                reason
            }, 'POST');
            showToast('ยกเลิกบิลสำเร็จ', 'success');
            await loadInvoices();
        } catch (error) {}
    }

    async function openVerifyModal(invoiceId) {
        try {
            const result = await apiCall('billing', 'getInvoice', {
                id: invoiceId
            });
            const inv = result.invoice;
            const pm = inv.payment_info;

            if (!pm) {
                showToast('ไม่พบข้อมูลการชำระเงิน', 'error');
                return;
            }

            document.getElementById('verifyPaymentId').value = pm.id;

            const img = document.getElementById('verifyProofImage');
            const link = document.getElementById('verifyProofLink');
            const proofUrl = fixProofUrl(pm.proof_file);

            if (proofUrl.match(/\.(jpeg|jpg|png|gif)$/i)) {
                img.src = proofUrl;
                img.classList.remove('hidden');
                link.classList.add('hidden');
            } else {
                img.classList.add('hidden');
                link.href = proofUrl;
                link.classList.remove('hidden');
            }

            let invoicesHtml = '';
            if (pm.invoices && pm.invoices.length > 0) {
                invoicesHtml = `
                    <div class="mb-3">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">รายการบิลที่ชำระรวมกัน:</span>
                        <div class="mt-2 space-y-2">
                            ${pm.invoices.map(i => `
                                <div class="flex justify-between items-center text-sm p-2 bg-white border border-gray-100 rounded">
                                    <span class="text-gray-700">#${i.invoice_number} (ห้อง ${i.room_number})</span>
                                    <span class="font-medium">${formatCurrency(i.total_amount)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            document.getElementById('verifyDetails').innerHTML = `
                ${invoicesHtml}
                <div class="space-y-2 pt-2 border-t border-gray-200">
                    <div class="flex justify-between text-sm"><span class="text-gray-600">วันที่โอน:</span><span>${pm.payment_date} ${pm.payment_time}</span></div>
                    <div class="flex justify-between text-lg font-bold text-primary"><span>ยอดรวมในสลิป:</span><span>${formatCurrency(pm.total_payment_amount || pm.total_amount)}</span></div>
                </div>
            `;

            openModal('verifyPaymentModal');
        } catch (error) {
            showToast('เกิดข้อผิดพลาดในการโหลดข้อมูล', 'error');
        }
    }

    async function confirmApprove() {
        if (!confirm('ยืนยันอนุมัติการชำระเงิน?')) return;

        const paymentId = document.getElementById('verifyPaymentId').value;
        try {
            await apiCall('billing', 'approvePayment', {
                payment_id: paymentId
            }, 'POST');
            showToast('อนุมัติเรียบร้อย', 'success');
            closeModal('verifyPaymentModal');
            loadInvoices();
        } catch (error) {}
    }

    async function confirmReject() {
        const reason = await showPrompt('ระบุเหตุผลที่ปฏิเสธ', 'ปฏิเสธการชำระเงิน');
        if (reason === null) return;

        const paymentId = document.getElementById('verifyPaymentId').value;
        try {
            await apiCall('billing', 'rejectPayment', {
                payment_id: paymentId,
                reason
            }, 'POST');
            showToast('ปฏิเสธรายการเรียบร้อย', 'success');
            closeModal('verifyPaymentModal');
            loadInvoices();
        } catch (error) {}
    }

    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>