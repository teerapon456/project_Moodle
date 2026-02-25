<?php
// booking_form.php - View only
if (!checkViewPermission($canView, 'ระบบหอพัก')) return;
if (!$canEdit) {
    echo '<div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
        <i class="ri-lock-line text-4xl text-red-400 mb-4 block"></i>
        <h3 class="text-lg font-semibold text-red-700 mb-2">ไม่มีสิทธิ์เข้าถึง</h3>
        <p class="text-red-600">คุณไม่มีสิทธิ์ในการใช้งานฟอร์มขอเข้าพัก/ย้ายห้อง</p>
    </div>';
    return;
}

require_once __DIR__ . '/../Controllers/BookingController.php';
$controller = new BookingController();
$data = $controller->getBookingFormData();
extract($data);

// Fetch max_relatives setting
require_once __DIR__ . '/../../../core/Database/Database.php';
$db = new Database();
$pdo = $db->getConnection();
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE module_id = 20 AND setting_key = 'max_relatives'");
$stmt->execute();
$maxRelativesResult = $stmt->fetchColumn();
$maxRelatives = $maxRelativesResult ? (int)$maxRelativesResult : 5; // Default to 5
?>

<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Sarabun', sans-serif;
        background-color: #f3f4f6;
    }

    .form-card {
        background-color: #ffffff;
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid #f3f4f6;
    }

    @media (min-width: 768px) {
        .form-card {
            padding: 2.5rem;
        }
    }

    .form-input {
        width: 100%;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.625rem 0.875rem;
        transition: all 0.2s;
        background-color: #f9fafb;
        font-size: 0.95rem;
    }

    .form-input:focus {
        background-color: #ffffff;
        border-color: #b91c1c;
        box-shadow: 0 0 0 4px rgba(185, 28, 28, 0.1);
        outline: none;
    }

    .upload-btn {
        background: #ffffff;
        border: 2px dashed #d1d5db;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }

    .upload-btn:hover {
        border-color: #4f46e5;
        background-color: #f5f3ff;
        color: #4f46e5;
    }

    .tab-btn {
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .active-tab {
        color: #4f46e5;
        background-color: #eef2ff;
        font-weight: 600;
    }

    .active-tab::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 0;
        right: 0;
        height: 2px;
        background-color: #4f46e5;
    }

    .inactive-tab {
        color: #6b7280;
        background-color: transparent;
    }

    .inactive-tab:hover {
        color: #374151;
        background-color: #f3f4f6;
    }

    .section-title {
        display: flex;
        align-items: center;
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .section-title i {
        margin-right: 0.75rem;
        color: #4f46e5;
        font-size: 1.25rem;
    }

    /* Approver Search Results */
    #approver-results {
        position: absolute;
        z-index: 50;
        width: 100%;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        max-height: 250px;
        overflow-y: auto;
        margin-top: 0.25rem;
    }
</style>




<div class="container mx-auto px-3 md:px-4 py-4 md:py-8 max-w-4xl">

    <!-- Header / Tabs -->
    <?php
    $defaultTab = 'move_in';
    $moveInDisabled = false;
    $moveInMessage = '';
    $hasRoomActionsDisabled = true; // For move_out, add_relative, change_room

    if ($currentOccupancy) {
        // User has a room - can't request move_in, but can use other features
        $defaultTab = 'move_out';
        $moveInDisabled = true;
        $hasRoomActionsDisabled = false; // Enable room-related actions
        $moveInMessage = 'คุณมีห้องพักอยู่แล้ว';
    } elseif ($hasPendingMoveIn) {
        // User has pending move_in request - hide entire form
        $moveInDisabled = true;
        $moveInMessage = 'คุณมีคำขอเข้าพักที่รอการอนุมัติอยู่แล้ว';
    }
    // else: No room, no pending -> Move In allowed, others disabled (default)
    ?>

    <?php if ($hasPendingMoveIn): ?>
        <!-- Pending Request Status Card -->
        <div class="form-card text-gray-800">
            <div class="text-center py-8">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-amber-100 flex items-center justify-center">
                    <i class="ri-time-line text-4xl text-amber-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">มีคำขอรอดำเนินการ</h2>
                <p class="text-gray-600 mb-6">คุณมีคำขอเข้าพักที่รอการอนุมัติอยู่แล้ว<br>กรุณารอผลการพิจารณาจากเจ้าหน้าที่</p>

                <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-200 rounded-full text-amber-700 text-sm">
                    <i class="ri-loader-4-line animate-spin"></i>
                    สถานะ: รอการอนุมัติ
                </div>

                <div class="mt-8">
                    <a href="?page=request_history" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition-colors shadow-sm">
                        <i class="ri-history-line"></i>
                        ดูประวัติคำขอ
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Normal Form Tabs -->
        <div class="bg-white rounded-t-xl shadow-sm border border-gray-100 p-1.5 md:p-2 mb-3 md:mb-4 flex space-x-1 md:space-x-2 max-w-4xl mx-auto">
            <button onclick="switchTab('move_in')" id="tab-move_in" class="tab-btn <?= $defaultTab == 'move_in' ? 'active-tab' : 'inactive-tab' ?> flex-1 py-2.5 md:py-3 rounded-lg text-xs md:text-base flex items-center justify-center gap-1 md:gap-2" <?= $moveInDisabled ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="ri-login-box-fill text-base md:text-lg"></i> <span class="hidden sm:inline">ขอ</span>เข้าพัก
            </button>
            <button onclick="switchTab('move_out')" id="tab-move_out" class="tab-btn <?= $defaultTab == 'move_out' ? 'active-tab' : 'inactive-tab' ?> flex-1 py-2.5 md:py-3 rounded-lg text-xs md:text-base flex items-center justify-center gap-1 md:gap-2" <?= $hasRoomActionsDisabled ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="ri-logout-box-fill text-base md:text-lg"></i> <span class="hidden sm:inline">ขอ</span>ย้ายออก
            </button>
            <button onclick="switchTab('add_relative')" id="tab-add_relative" class="tab-btn <?= $defaultTab == 'add_relative' ? 'active-tab' : 'inactive-tab' ?> flex-1 py-2.5 md:py-3 rounded-lg text-xs md:text-base flex items-center justify-center gap-1 md:gap-2" <?= $hasRoomActionsDisabled ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="ri-user-add-fill text-base md:text-lg"></i> <span class="hidden sm:inline">ขอนำ</span>ญาติมา
            </button>
            <button onclick="switchTab('change_room')" id="tab-change_room" class="tab-btn <?= $defaultTab == 'change_room' ? 'active-tab' : 'inactive-tab' ?> flex-1 py-2.5 md:py-3 rounded-lg text-xs md:text-base flex items-center justify-center gap-1 md:gap-2" <?= $hasRoomActionsDisabled ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : '' ?>>
                <i class="ri-exchange-fill text-base md:text-lg"></i> <span class="hidden sm:inline">ขอ</span>ย้ายห้อง
            </button>
        </div>

        <!-- MAIN FORM CARD -->
        <div class="form-card text-gray-800">
            <!-- Alert for Move In Disabled -->
            <?php if ($moveInDisabled): ?>
                <div id="move_in_alert" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="ri-information-line text-yellow-500 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <?= $moveInMessage ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form id="bookingForm" enctype="multipart/form-data">
                <input type="hidden" name="request_type" id="request_type" value="<?= $defaultTab ?>">

                <!-- User Info Section (Read Only) -->
                <!-- User Info Section -->
                <div class="bg-indigo-50/50 rounded-xl p-6 mb-8 border border-indigo-100/50">
                    <div class="section-title border-none mb-4 pb-0 !text-sm !text-indigo-900 uppercase tracking-wider">
                        <i class="ri-user-star-line"></i> ข้อมูลผู้ขอ
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">ชื่อ-นามสกุล</label>
                            <div class="font-medium text-gray-900 mt-1"><?= htmlspecialchars($userData['fullname']) ?></div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">ตำแหน่ง</label>
                            <div class="font-medium text-gray-900 mt-1"><?= htmlspecialchars($userData['position']) ?></div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">แผนก/ฝ่าย</label>
                            <div class="font-medium text-gray-900 mt-1"><?= htmlspecialchars($userData['department']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- MOVE IN SECTION -->
                <div id="section-move_in" class="<?= $defaultTab == 'move_in' ? '' : 'hidden' ?>">
                    <!-- Date Selection -->
                    <div class="mb-6">
                        <label class="text-xs font-semibold text-gray-500 mb-2 block">วันที่ต้องการเข้าพัก</label>
                        <input type="date" name="check_in_date" class="form-input bg-white w-full md:w-64">
                    </div>

                    <!-- Reason -->
                    <div class="mb-6">
                        <div class="section-title text-base border-0 mb-3 pb-0 text-gray-700">
                            <i class="ri-question-answer-line"></i> เหตุผลการเข้าพัก
                        </div>
                        <input type="text" name="reason" class="form-input" placeholder="ระบุเหตุผลความจำเป็น...">
                    </div>

                    <!-- Relatives -->
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 mb-6">
                        <div class="flex items-center mb-0">
                            <input type="checkbox" name="has_relative" id="has_relative" class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 transition duration-150 ease-in-out" onchange="toggleRelativeInputs()">
                            <label for="has_relative" class="ml-3 font-medium text-gray-700 cursor-pointer select-none">
                                <span class="block">ขออนุญาตนำญาติเข้าพัก</span>
                                <span class="text-xs text-gray-500 font-normal">กรณีมีผู้ติดตามหรือครอบครัว (สามารถเพิ่มได้หลายคน)</span>
                            </label>
                        </div>

                        <div id="relative-inputs" class="hidden mt-4 pt-4 border-t border-gray-200">
                            <!-- Container for relative entries -->
                            <div id="relatives-container">
                                <!-- First relative entry (template) -->
                                <div class="relative-entry bg-white rounded-lg p-4 border border-gray-200 mb-3" data-index="0">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-sm font-semibold text-indigo-600">ญาติคนที่ 1</span>
                                        <button type="button" class="remove-relative hidden text-red-500 hover:text-red-700 text-sm flex items-center gap-1" onclick="removeRelative(this)">
                                            <i class="ri-delete-bin-line"></i> ลบ
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">ชื่อ-นามสกุล</label>
                                            <input type="text" class="form-input relative-name" placeholder="ชื่อ-นามสกุล">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">อายุ</label>
                                            <input type="number" class="form-input relative-age" min="0" max="150" placeholder="อายุ">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">ความสัมพันธ์</label>
                                            <input type="text" class="form-input relative-relation" placeholder="เช่น ภรรยา, บุตร">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add relative button -->
                            <button type="button" onclick="addRelative()" class="w-full mt-2 py-2.5 border-2 border-dashed border-indigo-300 rounded-lg text-indigo-600 hover:border-indigo-500 hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                                <i class="ri-user-add-line"></i> เพิ่มญาติอีกคน
                            </button>

                            <!-- Hidden input to store JSON data -->
                            <input type="hidden" name="relatives_json" id="relatives_json" value="[]">
                        </div>
                    </div>

                    <hr class="border-gray-100 my-6">

                    <!-- Stay Information -->
                    <div class="section-title">
                        <i class="ri-hotel-bed-line"></i> ข้อมูลการเข้าพัก
                    </div>

                    <div class="flex flex-wrap items-end gap-6 mb-8">
                        <div class="flex-1 min-w-[250px]">
                            <label class="text-xs font-semibold text-gray-500 mb-2 block">ประเภทห้องพักที่ต้องการ</label>
                            <div class="flex gap-4" id="moveInRoomTypesContainer">
                                <span class="text-sm text-gray-500 flex items-center gap-2">
                                    <i class="ri-loader-4-line animate-spin"></i> กำลังโหลดประเภทห้อง...
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Uploads -->
                    <p class="text-red-600 text-xs md:text-sm mb-4">* แนบเอกสารหลักฐานเพื่อประกอบการพิจารณา (ไฟล์ .pdf .jpg .png)</p>
                    <div class="grid grid-cols-2 gap-3 md:gap-4">
                        <!-- ID Card -->
                        <!-- ID Card -->
                        <label class="upload-btn group">
                            <div class="mb-3 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                <i class="ri-id-card-line text-3xl"></i>
                            </div>
                            <div class="text-sm font-medium mb-1">สำเนาบัตรประชาชน</div>
                            <div class="text-xs text-gray-500 mb-2">หรือทะเบียนบ้าน</div>
                            <input type="file" name="id_card" class="hidden" onchange="showFileName(this)">
                            <span class="inline-block px-3 py-1 bg-gray-100 text-xs rounded-full text-gray-600 group-hover:bg-indigo-100 group-hover:text-indigo-600 file-name truncate max-w-full">เลือกไฟล์</span>
                        </label>
                        <!-- Marriage -->
                        <label class="upload-btn group">
                            <div class="mb-3 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                <i class="ri-heart-line text-3xl"></i>
                            </div>
                            <div class="text-sm font-medium mb-1">สำเนาทะเบียนสมรส</div>
                            <div class="text-xs text-gray-500 mb-2">(ถ้ามี)</div>
                            <input type="file" name="marriage_cert" class="hidden" onchange="showFileName(this)">
                            <span class="inline-block px-3 py-1 bg-gray-100 text-xs rounded-full text-gray-600 group-hover:bg-indigo-100 group-hover:text-indigo-600 file-name truncate max-w-full">เลือกไฟล์</span>
                        </label>
                        <!-- Birth Cert -->
                        <label class="upload-btn group">
                            <div class="mb-3 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                <i class="ri-parent-line text-3xl"></i>
                            </div>
                            <div class="text-sm font-medium mb-1">สำเนาสูติบัตร</div>
                            <div class="text-xs text-gray-500 mb-2">(กรณีมีบุตร)</div>
                            <input type="file" name="birth_cert" class="hidden" onchange="showFileName(this)">
                            <span class="inline-block px-3 py-1 bg-gray-100 text-xs rounded-full text-gray-600 group-hover:bg-indigo-100 group-hover:text-indigo-600 file-name truncate max-w-full">เลือกไฟล์</span>
                        </label>
                        <!-- Passport -->
                        <label class="upload-btn group">
                            <div class="mb-3 text-gray-400 group-hover:text-indigo-500 transition-colors">
                                <i class="ri-passport-line text-3xl"></i>
                            </div>
                            <div class="text-sm font-medium mb-1">สำเนาพาสปอร์ต</div>
                            <div class="text-xs text-gray-500 mb-2">(ต่างชาติ)</div>
                            <input type="file" name="passport" class="hidden" onchange="showFileName(this)">
                            <span class="inline-block px-3 py-1 bg-gray-100 text-xs rounded-full text-gray-600 group-hover:bg-indigo-100 group-hover:text-indigo-600 file-name truncate max-w-full">เลือกไฟล์</span>
                        </label>
                    </div>
                </div>

                <!-- MOVE OUT SECTION -->
                <div id="section-move_out" class="<?= $defaultTab == 'move_out' ? '' : 'hidden' ?>">
                    <div class="section-title">
                        <i class="ri-logout-box-line"></i> แจ้งประสงค์ขอย้ายออก
                    </div>
                    <?php if ($currentOccupancy): ?>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <i class="ri-home-4-line text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-green-600">ห้องพักปัจจุบันของคุณ</div>
                                    <div class="font-semibold text-green-900"><?= $currentOccupancy['building_name'] . ' - ห้อง ' . $currentOccupancy['room_number'] ?></div>
                                </div>
                            </div>
                            <input type="hidden" name="current_room_id" value="<?= $currentOccupancy['id'] ?>">
                        </div>

                        <!-- Move Out Type Selection -->
                        <div class="mb-6">
                            <label class="block font-medium mb-3">ประเภทการย้ายออก</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="move-out-type-option flex items-start gap-3 p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-primary transition-colors" onclick="selectMoveOutType('self')">
                                    <input type="radio" name="move_out_type" value="self" class="mt-1" checked>
                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                                            <i class="ri-user-unfollow-line text-primary"></i>
                                            ย้ายออกเอง
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">ย้ายออกจากห้องพัก (รวมญาติทั้งหมด)</div>
                                    </div>
                                </label>
                                <?php
                                // Check if user has relatives
                                $hasRelatives = false;
                                $relativesList = [];
                                if (!empty($currentOccupancy['accompanying_details'])) {
                                    $relativesList = json_decode($currentOccupancy['accompanying_details'], true) ?: [];
                                    $hasRelatives = count($relativesList) > 0;
                                }
                                ?>
                                <label class="move-out-type-option flex items-start gap-3 p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-purple-500 transition-colors <?= !$hasRelatives ? 'opacity-50 cursor-not-allowed' : '' ?>" onclick="<?= $hasRelatives ? "selectMoveOutType('relative')" : 'return false' ?>">
                                    <input type="radio" name="move_out_type" value="relative" class="mt-1" <?= !$hasRelatives ? 'disabled' : '' ?>>
                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                                            <i class="ri-user-heart-line text-purple-600"></i>
                                            นำญาติออกเท่านั้น
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            <?php if ($hasRelatives): ?>
                                                เลือกญาติที่ต้องการนำออก (คุณยังอยู่ต่อ)
                                            <?php else: ?>
                                                ไม่มีญาติที่ลงทะเบียน
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Relative Selection (shown when type=relative) -->
                        <div id="moveOutRelativesList" class="hidden mb-6">
                            <label class="block font-medium mb-3">เลือกญาติที่ต้องการนำออก</label>
                            <div class="space-y-2">
                                <?php if ($hasRelatives): foreach ($relativesList as $index => $relative): ?>
                                        <label class="flex items-center gap-3 p-3 bg-purple-50 border border-purple-100 rounded-lg cursor-pointer hover:border-purple-300">
                                            <input type="checkbox" name="remove_relatives[]" value="<?= $index ?>" class="w-4 h-4 text-purple-600 border-gray-300 rounded">
                                            <i class="ri-user-heart-line text-purple-500"></i>
                                            <span class="font-medium"><?= htmlspecialchars($relative['name'] ?? 'ญาติ') ?></span>
                                            <?php if (!empty($relative['age'])): ?>
                                                <span class="text-sm text-gray-500">อายุ <?= $relative['age'] ?> ปี</span>
                                            <?php endif; ?>
                                            <?php if (!empty($relative['relation'])): ?>
                                                <span class="text-xs bg-purple-100 text-purple-600 px-2 py-0.5 rounded"><?= htmlspecialchars($relative['relation']) ?></span>
                                            <?php endif; ?>
                                        </label>
                                <?php endforeach;
                                endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="p-4 bg-yellow-100 text-yellow-800 rounded mb-4">
                            ไม่พบข้อมูลการเข้าพักปัจจุบัน กรุณาตรวจสอบกับผู้ดูแล
                        </div>
                    <?php endif; ?>

                    <div class="mb-4" id="moveOutDateField">
                        <label class="block font-medium">วันที่ต้องการย้ายออก</label>
                        <input type="date" name="move_out_date" class="form-input w-full md:w-1/2">
                    </div>
                    <div id="moveOutReasonField">
                        <label class="font-medium">เหตุผลการย้ายออก</label>
                        <input type="text" name="move_out_reason" class="form-input mt-1">
                    </div>
                </div>

                <!-- ADD RELATIVE SECTION -->
                <div id="section-add_relative" class="<?= $defaultTab == 'add_relative' ? '' : 'hidden' ?>">
                    <div class="section-title">
                        <i class="ri-user-add-line"></i> ขอนำญาติมาพักเพิ่ม
                    </div>
                    <?php if ($currentOccupancy): ?>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100 mb-6">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <i class="ri-home-4-line text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-green-600">ห้องพักปัจจุบันของคุณ</div>
                                    <div class="font-semibold text-green-900"><?= $currentOccupancy['building_name'] . ' - ห้อง ' . $currentOccupancy['room_number'] ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Relatives Input (same structure as move_in) -->
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 mb-6">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="ri-group-line text-indigo-600"></i>
                                <span class="font-medium text-gray-700">ข้อมูลญาติที่ต้องการนำมาพักเพิ่ม</span>
                            </div>

                            <!-- Container for add_relative entries -->
                            <div id="add-relatives-container">
                                <!-- First relative entry -->
                                <div class="add-relative-entry bg-white rounded-lg p-4 border border-gray-200 mb-3" data-index="0">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-sm font-semibold text-indigo-600">ญาติคนที่ 1</span>
                                        <button type="button" class="remove-add-relative hidden text-red-500 hover:text-red-700 text-sm flex items-center gap-1" onclick="removeAddRelative(this)">
                                            <i class="ri-delete-bin-line"></i> ลบ
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">ชื่อ-นามสกุล</label>
                                            <input type="text" class="form-input add-relative-name" placeholder="ชื่อ-นามสกุล">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">อายุ</label>
                                            <input type="number" class="form-input add-relative-age" min="0" max="150" placeholder="อายุ">
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold text-gray-500 mb-1 block">ความสัมพันธ์</label>
                                            <input type="text" class="form-input add-relative-relation" placeholder="เช่น ภรรยา, บุตร">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Add relative button -->
                            <button type="button" onclick="addAddRelative()" class="w-full mt-2 py-2.5 border-2 border-dashed border-indigo-300 rounded-lg text-indigo-600 hover:border-indigo-500 hover:bg-indigo-50 transition-all flex items-center justify-center gap-2">
                                <i class="ri-user-add-line"></i> เพิ่มญาติอีกคน
                            </button>

                            <!-- Hidden input to store JSON data -->
                            <input type="hidden" name="add_relatives_json" id="add_relatives_json" value="[]">
                        </div>

                        <div>
                            <label class="font-medium">เหตุผล / หมายเหตุ</label>
                            <input type="text" name="add_relative_reason" class="form-input mt-1" placeholder="เช่น ภรรยาย้ายมาอยู่ด้วย">
                        </div>
                    <?php else: ?>
                        <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-xl text-center">
                            <i class="ri-information-line text-4xl text-yellow-500 mb-3 block"></i>
                            <div class="font-semibold text-yellow-800 mb-1">ไม่สามารถใช้งานได้</div>
                            <div class="text-yellow-700 text-sm">คุณยังไม่มีห้องพัก กรุณาขอเข้าพักก่อน</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CHANGE ROOM SECTION -->
                <div id="section-change_room" class="<?= $defaultTab == 'change_room' ? '' : 'hidden' ?>">
                    <div class="section-title">
                        <i class="ri-exchange-line"></i> ประสงค์ขอย้ายห้องพัก
                    </div>
                    <?php if ($currentOccupancy): ?>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-100 mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <i class="ri-home-4-line text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-green-600">ห้องพักปัจจุบันของคุณ</div>
                                    <div class="font-semibold text-green-900"><?= $currentOccupancy['building_name'] . ' - ห้อง ' . $currentOccupancy['room_number'] ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-wrap items-center gap-6 mb-8 mt-4">
                        <label class="block font-medium w-full">ต้องการย้ายไปห้องประเภท:</label>
                        <div id="changeRoomTypesContainer" class="flex flex-wrap items-center gap-6">
                            <span class="text-sm text-gray-500 flex items-center gap-2">
                                <i class="ri-loader-4-line animate-spin"></i> กำลังโหลดประเภทห้อง...
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block font-medium">วันที่ต้องการย้าย</label>
                        <input type="date" name="change_date" class="form-input w-full md:w-1/2">
                    </div>
                    <div>
                        <label class="font-medium">เหตุผลการขอย้าย</label>
                        <input type="text" name="change_reason" class="form-input mt-1">
                    </div>
                </div>

                <!-- APPROVER SECTION -->
                <div class="bg-blue-50/50 rounded-xl p-6 mb-8 border border-blue-100/50 mt-8">
                    <div class="section-title border-none mb-4 pb-0 !text-sm !text-blue-900 uppercase tracking-wider">
                        <i class="ri-user-follow-line text-blue-600"></i> ผู้อนุมัติ (หัวหน้างาน/ผู้จัดการ)
                    </div>
                    <div class="relative">
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block">ระบุชื่อหรืออีเมลผู้บังคับบัญชาเพื่อขออนุมัติ <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input type="text" id="approver_search" class="form-input bg-white pr-10" placeholder="ระบุชื่อหรืออีเมลเพื่อค้นหา..." autocomplete="off">
                                <div id="approver-loading" class="absolute right-3 top-1/2 -translate-y-1/2 hidden">
                                    <i class="ri-loader-4-line animate-spin text-blue-500"></i>
                                </div>
                                <div id="approver-results" class="hidden"></div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1">แจ้งเตือน: ระบบจะส่งอีเมลขออนุมัติไปยังอีเมลที่คุณเลือก</p>

                        <input type="hidden" name="approver_email" id="approver_email" value="<?= htmlspecialchars($userData['default_supervisor_email'] ?? '') ?>">

                        <!-- Selected Approver Card -->
                        <div id="selected-approver-display" class="<?= empty($userData['default_supervisor_email']) ? 'hidden' : '' ?> mt-3 p-3 bg-white border border-blue-200 rounded-lg flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                    <i class="ri-user-follow-fill"></i>
                                </div>
                                <div>
                                    <div id="approver-name-display" class="font-semibold text-sm text-gray-900"><?= htmlspecialchars($userData['default_supervisor_name'] ?? 'ผู้อนุมัติที่บันทึกไว้') ?></div>
                                    <div id="approver-email-display" class="text-xs text-gray-500"><?= htmlspecialchars($userData['default_supervisor_email'] ?? '') ?></div>
                                </div>
                            </div>
                            <button type="button" onclick="clearSelectedApprover()" class="text-gray-400 hover:text-red-500 transition-colors">
                                <i class="ri-close-circle-line text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div id="submit-container" class="mt-6 md:mt-8 text-center bg-gray-50 p-4 -mb-5 md:-mb-10 -mx-5 md:-mx-10 rounded-b-lg">
                    <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-8 md:px-10 py-3.5 md:py-3 rounded-xl font-semibold shadow-lg shadow-indigo-200 hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2 mx-auto text-base">
                        <i class="ri-send-plane-fill text-lg"></i> ส่งคำขอ
                    </button>
                </div>
            </form>
        </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ========== Validation Helpers ==========
    function showError(input, message) {
        clearError(input);
        input.classList.add('border-red-500', 'bg-red-50');
        input.classList.remove('border-gray-300', 'bg-gray-50');

        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-xs mt-1 error-message flex items-center gap-1';
        errorDiv.innerHTML = '<i class="ri-error-warning-line"></i>' + message;
        input.parentNode.appendChild(errorDiv);
    }

    function clearError(input) {
        input.classList.remove('border-red-500', 'bg-red-50');
        input.classList.add('border-gray-300', 'bg-gray-50');
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) existingError.remove();
    }

    function clearAllErrors() {
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500', 'bg-red-50');
            el.classList.add('border-gray-300', 'bg-gray-50');
        });
    }

    function isValidDate(dateStr) {
        if (!dateStr) return false;
        const selected = new Date(dateStr);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        return selected >= today;
    }

    function validateMoveIn() {
        let isValid = true;

        // Validate reason
        const reason = document.querySelector('input[name="reason"]');
        if (!reason.value.trim()) {
            showError(reason, 'กรุณาระบุเหตุผลการเข้าพัก');
            isValid = false;
        } else {
            clearError(reason);
        }

        // Validate check-in date
        const checkInDate = document.querySelector('input[name="check_in_date"]');
        if (!checkInDate.value) {
            showError(checkInDate, 'กรุณาเลือกวันที่ต้องการเข้าพัก');
            isValid = false;
        } else if (!isValidDate(checkInDate.value)) {
            showError(checkInDate, 'วันที่เข้าพักต้องไม่เป็นวันในอดีต');
            isValid = false;
        } else {
            clearError(checkInDate);
        }

        // Validate relative info if checkbox is checked
        const hasRelative = document.getElementById('has_relative');
        if (hasRelative && hasRelative.checked) {
            const entries = document.querySelectorAll('.relative-entry');
            let hasAtLeastOneRelative = false;

            entries.forEach((entry, index) => {
                const nameInput = entry.querySelector('.relative-name');
                const ageInput = entry.querySelector('.relative-age');
                const relationInput = entry.querySelector('.relative-relation');

                const hasName = nameInput.value.trim();
                const hasAge = ageInput.value;
                const hasRelation = relationInput.value.trim();

                // If any field is filled, validate all fields in this entry
                if (hasName || hasAge || hasRelation) {
                    hasAtLeastOneRelative = true;

                    if (!hasName) {
                        showError(nameInput, 'กรุณาระบุชื่อ-นามสกุล');
                        isValid = false;
                    } else {
                        clearError(nameInput);
                    }

                    if (!hasAge || hasAge < 0 || hasAge > 150) {
                        showError(ageInput, 'กรุณาระบุอายุที่ถูกต้อง');
                        isValid = false;
                    } else {
                        clearError(ageInput);
                    }

                    if (!hasRelation) {
                        showError(relationInput, 'กรุณาระบุความสัมพันธ์');
                        isValid = false;
                    } else {
                        clearError(relationInput);
                    }
                }
            });

            // Must have at least one relative if checkbox is checked
            if (!hasAtLeastOneRelative) {
                const firstNameInput = document.querySelector('.relative-entry .relative-name');
                if (firstNameInput) {
                    showError(firstNameInput, 'กรุณากรอกข้อมูลญาติอย่างน้อย 1 คน');
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    function validateMoveOut() {
        let isValid = true;

        // Check if moving out relative only
        const moveOutTypeChecked = document.querySelector('input[name="move_out_type"]:checked');
        let requestTypeIsMoveOut = true;

        if (moveOutTypeChecked && moveOutTypeChecked.value === 'relative') {
            requestTypeIsMoveOut = false;
            const selectedRelatives = document.querySelectorAll('input[name="remove_relatives[]"]:checked');
            if (selectedRelatives.length === 0) {
                showToast('กรุณาเลือกญาติที่ต้องการย้ายออกอย่างน้อย 1 คน', 'warning');
                isValid = false;
            }
        }

        if (requestTypeIsMoveOut) {
            // Validate move out date for self
            const moveOutDate = document.querySelector('input[name="move_out_date"]');
            if (!moveOutDate.value) {
                showError(moveOutDate, 'กรุณาเลือกวันที่ต้องการย้ายออก');
                isValid = false;
            } else if (!isValidDate(moveOutDate.value)) {
                showError(moveOutDate, 'วันที่ย้ายออกต้องไม่เป็นวันในอดีต');
                isValid = false;
            } else {
                clearError(moveOutDate);
            }
        }

        // Validate reason
        const moveOutReason = document.querySelector('input[name="move_out_reason"]');
        if (!moveOutReason.value.trim()) {
            showError(moveOutReason, 'กรุณาระบุเหตุผลการย้ายออก');
            isValid = false;
        } else {
            clearError(moveOutReason);
        }

        return isValid;
    }

    function validateChangeRoom() {
        let isValid = true;

        // Validate room type selection
        const roomTypeSelected = document.querySelector('input[name="change_room_type"]:checked');
        const roomTypeContainer = document.querySelector('input[name="change_room_type"]').closest('.flex');
        if (!roomTypeSelected) {
            const existingError = roomTypeContainer.parentNode.querySelector('.error-message');
            if (!existingError) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'text-red-500 text-xs mt-1 error-message flex items-center gap-1';
                errorDiv.innerHTML = '<i class="ri-error-warning-line"></i>กรุณาเลือกประเภทห้องที่ต้องการย้าย';
                roomTypeContainer.parentNode.appendChild(errorDiv);
            }
            isValid = false;
        } else {
            const existingError = roomTypeContainer.parentNode.querySelector('.error-message');
            if (existingError) existingError.remove();
        }

        // Validate change date
        const changeDate = document.querySelector('input[name="change_date"]');
        if (!changeDate.value) {
            showError(changeDate, 'กรุณาเลือกวันที่ต้องการย้าย');
            isValid = false;
        } else if (!isValidDate(changeDate.value)) {
            showError(changeDate, 'วันที่ย้ายต้องไม่เป็นวันในอดีต');
            isValid = false;
        } else {
            clearError(changeDate);
        }

        // Validate reason
        const changeReason = document.querySelector('input[name="change_reason"]');
        if (!changeReason.value.trim()) {
            showError(changeReason, 'กรุณาระบุเหตุผลการขอย้าย');
            isValid = false;
        } else {
            clearError(changeReason);
        }

        return isValid;
    }

    // ========== Tab Switching ==========
    function switchTab(tab) {
        // Clear all errors when switching tabs
        clearAllErrors();

        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active-tab');
            b.classList.add('inactive-tab');
        });
        document.getElementById('tab-' + tab).classList.add('active-tab');
        document.getElementById('tab-' + tab).classList.remove('inactive-tab');

        // Hide all sections
        document.getElementById('section-move_in').classList.add('hidden');
        document.getElementById('section-move_out').classList.add('hidden');
        document.getElementById('section-add_relative').classList.add('hidden');
        document.getElementById('section-change_room').classList.add('hidden');

        // Show selected section
        const targetSection = document.getElementById('section-' + tab);
        if (targetSection) {
            targetSection.classList.remove('hidden');
        }

        // Toggle submit button and alert
        const submitBtn = document.getElementById('submit-container');
        const alertBox = document.getElementById('move_in_alert');

        submitBtn.classList.remove('hidden');
        if (alertBox && tab === 'move_in') alertBox.classList.remove('hidden');
        else if (alertBox) alertBox.classList.add('hidden');

        document.getElementById('request_type').value = tab;
    }

    function selectMoveOutType(type) {
        // Update radio buttons
        document.querySelectorAll('input[name="move_out_type"]').forEach(radio => {
            radio.checked = (radio.value === type);
        });

        // Update card styling
        document.querySelectorAll('.move-out-type-option').forEach(label => {
            label.classList.remove('border-primary', 'border-purple-500', 'bg-primary/5', 'bg-purple-50');
            label.classList.add('border-gray-200');
        });

        // Highlight selected
        const selectedRadio = document.querySelector(`input[name="move_out_type"][value="${type}"]`);
        if (selectedRadio) {
            const label = selectedRadio.closest('.move-out-type-option');
            label.classList.remove('border-gray-200');
            if (type === 'self') {
                label.classList.add('border-primary', 'bg-primary/5');
            } else {
                label.classList.add('border-purple-500', 'bg-purple-50');
            }
        }

        // Toggle relatives list visibility
        const relativesList = document.getElementById('moveOutRelativesList');
        if (relativesList) {
            if (type === 'relative') {
                relativesList.classList.remove('hidden');
            } else {
                relativesList.classList.add('hidden');
            }
        }
    }

    // Initialize move out type on page load
    document.addEventListener('DOMContentLoaded', () => {
        const selfRadio = document.querySelector('input[name="move_out_type"][value="self"]');
        if (selfRadio && selfRadio.checked) {
            selectMoveOutType('self');
        }
    });

    function toggleRelativeInputs() {
        const checked = document.getElementById('has_relative').checked;
        const div = document.getElementById('relative-inputs');
        if (checked) {
            div.classList.remove('hidden');
        } else {
            div.classList.add('hidden');
            // Clear all relative entries except the first one
            const container = document.getElementById('relatives-container');
            const entries = container.querySelectorAll('.relative-entry');
            entries.forEach((entry, index) => {
                if (index === 0) {
                    // Clear first entry inputs
                    entry.querySelectorAll('input').forEach(input => {
                        input.value = '';
                        clearError(input);
                    });
                } else {
                    entry.remove();
                }
            });
            updateRelativeNumbers();
        }
    }

    // ========== Multiple Relatives Management ==========
    let relativeCounter = 1;
    const MAX_RELATIVES = <?php echo $maxRelatives; ?>; // From settings

    function addRelative() {
        const container = document.getElementById('relatives-container');
        const currentCount = container.querySelectorAll('.relative-entry').length;

        // Check max limit
        if (currentCount >= MAX_RELATIVES) {
            showToast(`ระบบอนุญาตให้เพิ่มญาติได้สูงสุด ${MAX_RELATIVES} คน`, 'warning');
            return;
        }

        const index = currentCount;

        const newEntry = document.createElement('div');
        newEntry.className = 'relative-entry bg-white rounded-lg p-4 border border-gray-200 mb-3';
        newEntry.dataset.index = index;
        newEntry.innerHTML = `
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-semibold text-indigo-600">ญาติคนที่ ${index + 1}</span>
                    <button type="button" class="remove-relative text-red-500 hover:text-red-700 text-sm flex items-center gap-1" onclick="removeRelative(this)">
                        <i class="ri-delete-bin-line"></i> ลบ
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-input relative-name" placeholder="ชื่อ-นามสกุล">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">อายุ</label>
                        <input type="number" class="form-input relative-age" min="0" max="150" placeholder="อายุ">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">ความสัมพันธ์</label>
                        <input type="text" class="form-input relative-relation" placeholder="เช่น ภรรยา, บุตร">
                    </div>
                </div>
            `;
        container.appendChild(newEntry);
        updateRelativeNumbers();
    }

    function removeRelative(button) {
        const entry = button.closest('.relative-entry');
        entry.remove();
        updateRelativeNumbers();
    }

    function updateRelativeNumbers() {
        const entries = document.querySelectorAll('.relative-entry');
        entries.forEach((entry, index) => {
            const label = entry.querySelector('.text-indigo-600');
            if (label) label.textContent = `ญาติคนที่ ${index + 1}`;

            // Show/hide remove button (hide if only one entry)
            const removeBtn = entry.querySelector('.remove-relative');
            if (removeBtn) {
                if (entries.length === 1) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            }
        });
    }

    function collectRelativesData() {
        const entries = document.querySelectorAll('.relative-entry');
        const relatives = [];

        entries.forEach(entry => {
            const name = entry.querySelector('.relative-name').value.trim();
            const age = entry.querySelector('.relative-age').value;
            const relation = entry.querySelector('.relative-relation').value.trim();

            // Only include if at least name is filled
            if (name) {
                relatives.push({
                    name: name,
                    age: age ? parseInt(age) : null,
                    relation: relation
                });
            }
        });

        document.getElementById('relatives_json').value = JSON.stringify(relatives);
        return relatives;
    }

    // ========== Add Relative Request (for existing residents) ==========
    function addAddRelative() {
        const container = document.getElementById('add-relatives-container');
        const currentCount = container.querySelectorAll('.add-relative-entry').length;

        // Check max limit
        if (currentCount >= MAX_RELATIVES) {
            showToast(`ระบบอนุญาตให้เพิ่มญาติได้สูงสุด ${MAX_RELATIVES} คน`, 'warning');
            return;
        }

        const index = currentCount;
        const newEntry = document.createElement('div');
        newEntry.className = 'add-relative-entry bg-white rounded-lg p-4 border border-gray-200 mb-3';
        newEntry.dataset.index = index;
        newEntry.innerHTML = `
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm font-semibold text-indigo-600">ญาติคนที่ ${index + 1}</span>
                    <button type="button" class="remove-add-relative text-red-500 hover:text-red-700 text-sm flex items-center gap-1" onclick="removeAddRelative(this)">
                        <i class="ri-delete-bin-line"></i> ลบ
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-input add-relative-name" placeholder="ชื่อ-นามสกุล">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">อายุ</label>
                        <input type="number" class="form-input add-relative-age" min="0" max="150" placeholder="อายุ">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 mb-1 block">ความสัมพันธ์</label>
                        <input type="text" class="form-input add-relative-relation" placeholder="เช่น ภรรยา, บุตร">
                    </div>
                </div>
            `;
        container.appendChild(newEntry);
        updateAddRelativeNumbers();
    }

    function removeAddRelative(button) {
        const entry = button.closest('.add-relative-entry');
        entry.remove();
        updateAddRelativeNumbers();
    }

    function updateAddRelativeNumbers() {
        const entries = document.querySelectorAll('.add-relative-entry');
        entries.forEach((entry, index) => {
            const label = entry.querySelector('.text-indigo-600');
            if (label) label.textContent = `ญาติคนที่ ${index + 1}`;

            const removeBtn = entry.querySelector('.remove-add-relative');
            if (removeBtn) {
                if (entries.length === 1) {
                    removeBtn.classList.add('hidden');
                } else {
                    removeBtn.classList.remove('hidden');
                }
            }
        });
    }

    function collectAddRelativesData() {
        const entries = document.querySelectorAll('.add-relative-entry');
        const relatives = [];

        entries.forEach(entry => {
            const name = entry.querySelector('.add-relative-name').value.trim();
            const age = entry.querySelector('.add-relative-age').value;
            const relation = entry.querySelector('.add-relative-relation').value.trim();

            if (name) {
                relatives.push({
                    name: name,
                    age: age ? parseInt(age) : null,
                    relation: relation
                });
            }
        });

        document.getElementById('add_relatives_json').value = JSON.stringify(relatives);
        return relatives;
    }

    function validateAddRelative() {
        let isValid = true;
        const entries = document.querySelectorAll('.add-relative-entry');
        let hasAtLeastOne = false;

        entries.forEach(entry => {
            const nameInput = entry.querySelector('.add-relative-name');
            const ageInput = entry.querySelector('.add-relative-age');
            const relationInput = entry.querySelector('.add-relative-relation');

            const hasName = nameInput.value.trim();
            const hasAge = ageInput.value;
            const hasRelation = relationInput.value.trim();

            if (hasName || hasAge || hasRelation) {
                hasAtLeastOne = true;

                if (!hasName) {
                    showError(nameInput, 'กรุณาระบุชื่อ-นามสกุล');
                    isValid = false;
                } else {
                    clearError(nameInput);
                }

                if (!hasAge || hasAge < 0 || hasAge > 150) {
                    showError(ageInput, 'กรุณาระบุอายุที่ถูกต้อง');
                    isValid = false;
                } else {
                    clearError(ageInput);
                }

                if (!hasRelation) {
                    showError(relationInput, 'กรุณาระบุความสัมพันธ์');
                    isValid = false;
                } else {
                    clearError(relationInput);
                }
            }
        });

        // Must have at least one relative
        if (!hasAtLeastOne) {
            const firstNameInput = document.querySelector('.add-relative-entry .add-relative-name');
            if (firstNameInput) {
                showError(firstNameInput, 'กรุณากรอกข้อมูลญาติอย่างน้อย 1 คน');
                isValid = false;
            }
        }

        return isValid;
    }

    function showFileName(input) {
        if (input.files && input.files[0]) {
            input.nextElementSibling.innerText = input.files[0].name;
        }
    }

    // ========== Form Submission ==========
    $('#bookingForm').on('submit', function(e) {
        e.preventDefault();
        clearAllErrors();

        const requestType = document.getElementById('request_type').value;
        let isValid = false;

        // Validate based on active tab
        switch (requestType) {
            case 'move_in':
                isValid = validateMoveIn();
                break;
            case 'move_out':
                isValid = validateMoveOut();
                break;
            case 'add_relative':
                isValid = validateAddRelative();
                break;
            case 'change_room':
                isValid = validateChangeRoom();
                break;
            default:
                showToast('กรุณาเลือกประเภทคำขอ', 'error');
                return;
        }

        if (!isValid) {
            showToast('กรุณากรอกข้อมูลให้ครบถ้วน', 'warning');
            return;
        }

        // Validate Approver Email
        const approverEmail = document.getElementById('approver_email').value;
        if (!approverEmail) {
            showToast('กรุณาระบุผู้อนุมัติ', 'warning');
            document.getElementById('approver_search').focus();
            return;
        }

        // Collect relatives data before submission
        if (requestType === 'move_in' && document.getElementById('has_relative').checked) {
            collectRelativesData();
        } else if (requestType === 'add_relative') {
            collectAddRelativesData();
        }

        const formData = new FormData(this);

        // Handle relative-only move out
        if (requestType === 'move_out') {
            const moveOutType = document.querySelector('input[name="move_out_type"]:checked');
            if (moveOutType && moveOutType.value === 'relative') {
                // Change request type to remove_relative
                formData.set('request_type', 'remove_relative');

                // Collect selected relative indices
                const selectedRelatives = [];
                document.querySelectorAll('input[name="remove_relatives[]"]:checked').forEach(cb => {
                    selectedRelatives.push(parseInt(cb.value));
                });
                formData.set('remove_relative_indices', JSON.stringify(selectedRelatives));
            }
        }

        const submitBtn = document.querySelector('#submit-container button');
        const originalBtnContent = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin text-lg"></i> กำลังส่งคำขอ...';

        $.ajax({
            url: API_BASE + '?controller=booking&action=store',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                showToast('ส่งคำขอเรียบร้อยแล้ว', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            },
            error: function(err) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnContent;

                let errorMsg = 'ไม่สามารถส่งคำขอได้';
                if (err.responseJSON && err.responseJSON.message) {
                    errorMsg = err.responseJSON.message;
                }
                showToast(errorMsg, 'error');
            }
        });
    });

    // Load room types dynamically
    async function loadRoomTypes() {
        try {
            const result = await apiCall('settings', 'getRoomTypes');
            const roomTypes = result.room_types || [];
            const activeTypes = roomTypes.filter(rt => rt.status === 'active');

            renderRoomTypes(activeTypes, 'moveInRoomTypesContainer', 'room_type_preference');
            renderRoomTypes(activeTypes, 'changeRoomTypesContainer', 'change_room_type');
        } catch (error) {
            console.error('Failed to load room types:', error);
            const errorHtml = '<span class="text-sm text-red-500">ไม่สามารถโหลดข้อมูลประเภทห้องได้</span>';
            const moveInContainer = document.getElementById('moveInRoomTypesContainer');
            const changeContainer = document.getElementById('changeRoomTypesContainer');

            if (moveInContainer) moveInContainer.innerHTML = errorHtml;
            if (changeContainer) changeContainer.innerHTML = errorHtml;
        }
    }

    function renderRoomTypes(types, containerId, inputName) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (types.length === 0) {
            container.innerHTML = '<span class="text-sm text-gray-500">ไม่มีข้อมูลประเภทห้อง</span>';
            return;
        }

        let html = '';
        types.forEach((rt, index) => {
            const isChecked = index === 0 ? 'checked' : '';
            html += `
                    <label class="inline-flex items-center cursor-pointer p-3 border rounded-lg bg-gray-50 flex-1 hover:bg-white hover:border-indigo-300 transition-all min-w-[200px]">
                        <input type="radio" name="${inputName}" value="${rt.id}" class="form-radio h-4 w-4 text-indigo-600" ${isChecked} data-rent="${rt.monthly_rent}">
                        <div class="ml-2">
                            <span class="text-sm font-medium block">${rt.name}</span>
                            <span class="text-xs text-gray-500">${rt.monthly_rent > 0 ? formatCurrency(rt.monthly_rent) + '/เดือน' : 'ฟรี'}</span>
                        </div>
                    </label>
                `;
        });
        container.innerHTML = html;
    }

    // ========== Real-time Validation (on blur) ==========
    document.addEventListener('DOMContentLoaded', function() {
        loadRoomTypes();

        // Add blur event listeners for real-time validation feedback
        const inputsToValidate = [
            'input[name="reason"]',
            'input[name="check_in_date"]',
            'input[name="move_out_date"]',
            'input[name="move_out_reason"]',
            'input[name="change_date"]',
            'input[name="change_reason"]',
            'input[name="relative_name"]',
            'input[name="relative_age"]',
            'input[name="relative_relation"]'
        ];

        inputsToValidate.forEach(selector => {
            const input = document.querySelector(selector);
            if (input) {
                input.addEventListener('blur', function() {
                    // Only validate if the input has been touched (has value or is required)
                    if (this.value.trim()) {
                        clearError(this);
                    }
                });

                input.addEventListener('input', function() {
                    // Clear error on input if valid
                    if (this.value.trim()) {
                        clearError(this);
                    }
                });
            }
        });
    });

    // ========== Approver Search Logic ==========
    let searchTimeout = null;
    const approverSearch = document.getElementById('approver_search');
    const approverResults = document.getElementById('approver-results');
    const approverLoading = document.getElementById('approver-loading');
    const approverEmailInput = document.getElementById('approver_email');
    const selectedApproverDisplay = document.getElementById('selected-approver-display');
    const approverNameDisplay = document.getElementById('approver-name-display');
    const approverEmailDisplay = document.getElementById('approver-email-display');

    approverSearch.addEventListener('input', function() {
        const query = this.value.trim();
        clearTimeout(searchTimeout);

        if (query.length < 2) {
            approverResults.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async () => {
            approverLoading.classList.remove('hidden');
            try {
                const response = await fetch(API_BASE + `?action=searchManager&query=${encodeURIComponent(query)}`);
                const result = await response.json();

                if (result.success && result.users && result.users.length > 0) {
                    let html = '';
                    result.users.forEach(emp => {
                        html += `
                            <div class="flex items-center gap-3 p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0" onclick='selectApprover(${JSON.stringify(emp)})'>
                                <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center text-white text-xs font-semibold">${(emp.name || '?').charAt(0)}</div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 text-sm">${emp.name}</div>
                                    <div class="text-xs text-gray-500">${emp.email} ${emp.department ? ' | ' + emp.department : ''}</div>
                                </div>
                                <span class="text-[10px] px-1.5 py-0.5 rounded ${emp.source === 'microsoft' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'}">${emp.source === 'microsoft' ? 'MS' : 'DB'}</span>
                            </div>
                        `;
                    });
                    approverResults.innerHTML = html;
                    approverResults.classList.remove('hidden');
                } else {
                    approverResults.innerHTML = '<div class="p-4 text-xs text-gray-400 text-center">ไม่พบรายชื่อที่ตรงกัน</div>';
                    approverResults.classList.remove('hidden');
                }
            } catch (err) {
                console.error('Search failed:', err);
            } finally {
                approverLoading.classList.add('hidden');
            }
        }, 300);
    });

    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!approverSearch.contains(e.target) && !approverResults.contains(e.target)) {
            approverResults.classList.add('hidden');
        }
    });

    function selectApprover(emp, saveAsDefault = true) {
        const name = emp.name;
        const email = emp.email;
        const dept = emp.department;

        approverEmailInput.value = email;
        approverNameDisplay.textContent = name;
        approverEmailDisplay.textContent = email + (dept ? ' | ' + dept : '');
        selectedApproverDisplay.classList.remove('hidden');
        approverResults.classList.add('hidden');
        approverSearch.value = '';
        clearError(approverSearch);

        if (saveAsDefault) {
            saveDefaultSupervisor(emp);
        }
    }

    async function saveDefaultSupervisor(emp) {
        try {
            await fetch(`${API_BASE}?controller=booking&action=saveDefaultSupervisor`, {
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

    function clearSelectedApprover() {
        approverEmailInput.value = '';
        selectedApproverDisplay.classList.add('hidden');
        approverSearch.focus();
    }

    // Pre-fill default supervisor if available
    <?php if (!empty($userData['default_supervisor_email'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            selectApprover({
                name: '<?= addslashes($userData['default_supervisor_name'] ?: $userData['default_supervisor_email']) ?>',
                email: '<?= addslashes($userData['default_supervisor_email']) ?>',
                id: '<?= addslashes($userData['default_supervisor_id'] ?: '') ?>',
                department: ''
            }, false);
        });
    <?php endif; ?>
</script>
<?php endif; ?>