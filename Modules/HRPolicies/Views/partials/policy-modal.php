<?php
/**
 * HR Policy Modal Partial
 */
?>
<div id="hr-policy-modal-container"></div>

<script>
    const hrPolicyModal = (() => {
        let modalElement = null;

        const createModal = () => {
            if (modalElement) return modalElement;

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center hidden';
            modal.id = 'hr-policy-modal';
            modal.innerHTML = `
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" onclick="hrPolicyModal.close()"></div>
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl transform transition-all z-10 m-4 overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between bg-white sticky top-0 z-20">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-3" id="hr-policy-modal-header">
                            <i class="ri-article-line text-blue-600 text-2xl"></i> <span id="hr-policy-modal-title">จัดการนโยบาย HR</span>
                        </h3>
                        <button class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400" onclick="hrPolicyModal.close()">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>
                    
                    <div class="p-8 overflow-y-auto custom-scrollbar">
                        <form id="hr-policy-form" class="space-y-6">
                            <input type="hidden" id="hr-policy-id" name="id">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="hr-policy-form-grid">
                                <div class="md:col-span-2 relative drop-shadow-sm">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">หัวข้อนโยบาย <span class="text-red-500">*</span></label>
                                    <input type="text" id="hr-policy-title" name="title" class="form-input w-full py-3 px-4 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all font-semibold" placeholder="เช่น นโยบายการลากิจ 2568" required>
                                </div>
                                
                                <div class="relative drop-shadow-sm">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">หมวดหมู่</label>
                                    <input type="text" id="hr-policy-category" name="category" class="form-input w-full py-3 px-4 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all" placeholder="เช่น การลา, สวัสดิการ, วินัย">
                                </div>
                                
                                <div class="flex items-center mt-8">
                                    <label class="relative inline-flex items-center cursor-pointer group">
                                        <input type="checkbox" id="hr-policy-active" name="is_active" value="1" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">เปิดใช้งาน (AI มองเห็น)</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="relative drop-shadow-sm mb-6" id="hr-policy-form-content">
                                <label class="block text-sm font-bold text-gray-700 mb-2">เนื้อหากฎระเบียบ (AI จะอ่านจากส่วนนี้) <span class="text-red-500">*</span></label>
                                <textarea id="hr-policy-content" name="content" rows="12" class="form-input w-full py-3 px-4 rounded-lg border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all resize-y leading-relaxed" placeholder="พิมพ์รายละเอียดกฎระเบียบที่นี่ เพื่อให้ AI ใช้เป็นฐานข้อมูลในการตอบคำถามพนักงาน..." required></textarea>
                            </div>

                            <div id="hr-policy-history-section" class="hidden mt-8 pt-6 border-t border-gray-100">
                                <h4 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                    <i class="ri-history-line text-blue-500"></i> ประวัติการแก้ไข
                                </h4>
                                <div id="hr-policy-history-list" class="space-y-3">
                                    <!-- History items will be injected here -->
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3 sticky bottom-0 z-20" id="hr-policy-modal-footer">
                        <button type="button" class="btn text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-5 py-2.5 rounded-lg font-medium transition-colors" onclick="hrPolicyModal.close()">ยกเลิก</button>
                        <button type="button" class="btn text-white bg-blue-600 hover:bg-blue-700 px-6 py-2.5 rounded-lg shadow-sm font-medium flex items-center gap-2 transition-all transform hover:-translate-y-0.5 active:translate-y-0 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" onclick="hrPolicyModal.save()" id="hr-policy-save-btn">
                            <i class="ri-save-line text-lg"></i> บันทึกข้อมูล
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modalElement = modal;
            return modal;
        };

        const fetchHistory = async (id) => {
            const historySection = document.getElementById('hr-policy-history-section');
            const historyList = document.getElementById('hr-policy-history-list');
            historySection.classList.add('hidden');
            historyList.innerHTML = '';
            
            try {
                const res = await fetch(hrPolicyApiUrl + '?action=get_history&id=' + id);
                const result = await res.json();
                if (result.success && result.data && result.data.length > 0) {
                    historySection.classList.remove('hidden');
                    historyList.innerHTML = result.data.map((h, i) => `
                        <div class="bg-white border border-gray-100 rounded-lg p-4 hover:border-blue-200 transition-colors shadow-sm relative group">
                            <div class="absolute -left-2 top-4 w-1 flex flex-col items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-400"></div>
                                ${i < result.data.length - 1 ? '<div class="w-0.5 h-full bg-blue-100 absolute top-3"></div>' : ''}
                            </div>
                            <div class="ml-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-xs font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="ri-time-line text-blue-500"></i>
                                        ${new Date(h.updated_at).toLocaleString('th-TH')}
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1">
                                        <i class="ri-user-line"></i> ${h.fullname || 'Unknown User'}
                                    </div>
                                </div>
                                <div class="text-sm font-medium text-gray-900 mb-1">${h.title} <span class="text-xs font-normal text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md ml-2">${h.category || 'General'}</span></div>
                                <div class="text-xs text-gray-600 line-clamp-2 mt-2 bg-gray-50 p-2 rounded border border-gray-100">
                                    ${h.content}
                                </div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error("Failed to load history", e);
            }
        };

        return {
            init: () => createModal(),
            history: async (id) => {
                const modal = createModal();
                const title = document.getElementById('hr-policy-modal-title');
                const form = document.getElementById('hr-policy-form');
                form.reset();
                document.getElementById('hr-policy-history-section').classList.add('hidden');
                
                const p = hrPoliciesCache.find(x => x.id == id);
                if (p) {
                    title.textContent = 'ประวัติการแก้ไข: ' + p.title;
                    document.getElementById('hr-policy-title').value = p.title;
                    document.getElementById('hr-policy-category').value = p.category;
                    document.getElementById('hr-policy-content').value = p.content;
                    document.getElementById('hr-policy-active').checked = p.is_active == 1;

                    // Hide the form fields visually, only show history
                    document.getElementById('hr-policy-form-grid').classList.add('hidden');
                    document.getElementById('hr-policy-form-content').classList.add('hidden');
                    document.getElementById('hr-policy-save-btn').classList.add('hidden');
                    
                    await fetchHistory(id);
                }
                modal.classList.remove('hidden');
            },
            view: async (id) => {
                const modal = createModal();
                const title = document.getElementById('hr-policy-modal-title');
                const form = document.getElementById('hr-policy-form');
                form.reset();
                document.getElementById('hr-policy-history-section').classList.add('hidden');
                
                const p = hrPoliciesCache.find(x => x.id == id);
                if (p) {
                    title.textContent = 'อ่านนโยบาย HR';
                    document.getElementById('hr-policy-title').value = p.title;
                    document.getElementById('hr-policy-category').value = p.category;
                    document.getElementById('hr-policy-content').value = p.content;
                    document.getElementById('hr-policy-active').checked = p.is_active == 1;

                    // Show form elements but disabled
                    document.getElementById('hr-policy-form-grid').classList.remove('hidden');
                    document.getElementById('hr-policy-form-content').classList.remove('hidden');

                    form.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
                    document.getElementById('hr-policy-save-btn').classList.add('hidden');
                    
                    await fetchHistory(id);
                }
                modal.classList.remove('hidden');
            },
            open: async (id = null) => {
                const modal = createModal();
                const title = document.getElementById('hr-policy-modal-title');
                const form = document.getElementById('hr-policy-form');
                form.reset();
                document.getElementById('hr-policy-id').value = '';
                document.getElementById('hr-policy-history-section').classList.add('hidden');
                
                // Show standard form fields
                document.getElementById('hr-policy-form-grid').classList.remove('hidden');
                document.getElementById('hr-policy-form-content').classList.remove('hidden');
                
                form.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
                document.getElementById('hr-policy-save-btn').classList.remove('hidden');
                
                if (id) {
                    title.textContent = 'แก้ไขนโยบาย HR';
                    const p = hrPoliciesCache.find(x => x.id == id);
                    if (p) {
                        document.getElementById('hr-policy-id').value = p.id;
                        document.getElementById('hr-policy-title').value = p.title;
                        document.getElementById('hr-policy-category').value = p.category;
                        document.getElementById('hr-policy-content').value = p.content;
                        document.getElementById('hr-policy-active').checked = p.is_active == 1;

                        await fetchHistory(id);
                    }
                } else {
                    title.textContent = 'เพิ่มนโยบาย HR ใหม่';
                    document.getElementById('hr-policy-active').checked = true;
                }
                modal.classList.remove('hidden');
            },
            close: () => {
                const modal = document.getElementById('hr-policy-modal');
                if (modal) modal.classList.add('hidden');
            },
            save: async () => {
                const form = document.getElementById('hr-policy-form');
                if (!form.reportValidity()) return;
                
                let formData = new FormData(form);
                formData.append('action', 'save');
                if (!document.getElementById('hr-policy-active').checked) {
                    formData.set('is_active', '0');
                }
                
                try {
                    const res = await fetch(hrPolicyApiUrl, { method: 'POST', body: formData });
                    const data = await res.json();
                    if (data.success) {
                        hrPolicyModal.close();
                        Swal.fire({ icon: 'success', title: 'สำเร็จ', text: 'บันทึกนโยบายเรียบร้อยแล้ว', timer: 1500, showConfirmButton: false });
                        loadHRPolicies();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (err) {
                    Swal.fire('ข้อผิดพลาด', err.message, 'error');
                }
            }
        };
    })();

    // Initialize the modal
    document.addEventListener('DOMContentLoaded', () => hrPolicyModal.init());
</script>
