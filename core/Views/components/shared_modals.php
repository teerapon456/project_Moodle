<!-- Shared Modals Component -->
<div id="global-confirm-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm modal-overlay"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden transform transition-all scale-95 opacity-0 duration-300 translate-y-4" id="confirm-modal-content">
        <div class="p-6 text-center">
            <div id="confirm-modal-icon-container" class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                <i id="confirm-modal-icon" class="ri-error-warning-line text-3xl text-red-500"></i>
            </div>
            <h3 id="confirm-modal-title" class="text-xl font-bold text-gray-900 mb-2">ยืนยันการดำเนินการ</h3>
            <p id="confirm-modal-message" class="text-gray-500">คุณแน่ใจหรือไม่ว่าต้องการดำเนินการนี้? การกระทำนี้ไม่สามารถย้อนกลับได้</p>
        </div>
        <div class="p-6 bg-gray-50 flex flex-col sm:flex-row gap-3">
            <button id="confirm-modal-cancel" class="flex-1 px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button id="confirm-modal-submit" class="flex-1 px-4 py-2.5 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200">
                ยืนยัน
            </button>
        </div>
    </div>
</div>

<div id="global-alert-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm modal-overlay"></div>
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform transition-all scale-95 opacity-0 duration-300" id="alert-modal-content">
        <div class="p-6 text-center">
            <div id="alert-modal-icon-container" class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                <i id="alert-modal-icon" class="ri-information-line text-2xl text-blue-500"></i>
            </div>
            <h3 id="alert-modal-title" class="text-lg font-bold text-gray-900 mb-1">ข้อมูล</h3>
            <p id="alert-modal-message" class="text-gray-500 text-sm italic">บันทึกข้อมูลเรียบร้อยแล้ว</p>
        </div>
        <div class="px-6 pb-6 mt-2">
            <button id="alert-modal-close" class="w-full px-4 py-2.5 bg-gray-900 text-white font-medium rounded-xl hover:bg-gray-800 transition-colors shadow-lg">
                รับทราบ
            </button>
        </div>
    </div>
</div>

<style>
    .modal-show { display: flex !important; }
    .modal-animate-in { scale: 1 !important; opacity: 1 !important; transform: translateY(0) !important; }
</style>
