<?php

/**
 * Dormitory Sidebar Include
 * Requires: $page, $isAdmin, $canEdit, $user
 */
?>
<!-- Sidebar -->
<aside class="sidebar no-transition fixed top-0 left-0 h-full w-[260px] bg-white border-r border-gray-200 flex flex-col z-40" id="sidebar">
    <!-- Header -->
    <div class="flex items-center justify-between px-5 h-16 border-b border-gray-100">
        <div class="logo flex items-center gap-3 text-primary font-semibold text-xl">
            <i class="ri-building-2-line text-2xl sidebar-text"></i>
            <span class="sidebar-text">ระบบหอพัก</span>
        </div>
        <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 rounded-lg" id="sidebarToggle">
            <i class="ri-menu-line text-lg"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <ul class="space-y-1">
            <?php if ($isAdmin): ?>
                <li>
                    <a href="?page=dashboard" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'dashboard' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-home-4-line text-lg"></i>
                        <span class="sidebar-text">หน้าหลัก</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ผู้พักอาศัย</li>
            <li>
                <a href="?page=my-room" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'my-room' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <i class="ri-user-heart-line text-lg"></i>
                    <span class="sidebar-text">ห้องของฉัน</span>
                </a>
            </li>
            <?php if ($canEdit): ?>
                <li>
                    <a href="?page=booking_form" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'booking_form' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-hotel-bed-line text-lg"></i>
                        <span class="sidebar-text">ขอเข้าพัก/ย้าย</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="?page=request_history" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'request_history' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <i class="ri-history-line text-lg"></i>
                    <span class="sidebar-text">ประวัติคำขอ</span>
                </a>
            </li>

            <?php if ($isAdmin || $canApprove): ?>
                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">จัดการห้องพัก</li>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <li>
                    <a href="?page=buildings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'buildings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-building-line text-lg"></i>
                        <span class="sidebar-text">อาคาร</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($isAdmin || $canApprove): ?>
                <li>
                    <a href="?page=booking_manage" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'booking_manage' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-file-list-3-line text-lg"></i>
                        <span class="sidebar-text">จัดการคำขอ</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a href="?page=rooms" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'rooms' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <i class="ri-door-open-line text-lg"></i>
                    <span class="sidebar-text">ห้องพัก</span>
                </a>
                </li>
                <li>
                    <a href="?page=history" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'history' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-history-line text-lg"></i>
                        <span class="sidebar-text">ประวัติการเข้าพัก</span>
                    </a>
                </li>

                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ระบบบิล</li>
                <li>
                    <a href="?page=meter-reading" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'meter-reading' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-dashboard-3-line text-lg"></i>
                        <span class="sidebar-text">บันทึกมิเตอร์</span>
                    </a>
                </li>
                <li>
                    <a href="?page=invoices" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'invoices' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-file-list-3-line text-lg"></i>
                        <span class="sidebar-text">ใบแจ้งหนี้</span>
                    </a>
                </li>
                <li>
                    <a href="?page=payments" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'payments' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-money-dollar-circle-line text-lg"></i>
                        <span class="sidebar-text">การชำระเงิน</span>
                    </a>
                </li>
            <?php endif; ?>

            <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ระบบแจ้งซ่อม</li>
            <!-- Allow all users to see maintenance form -->
            <li>
                <a href="?page=maintenance-form" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'maintenance-form' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                    <i class="ri-add-circle-line text-lg"></i>
                    <span class="sidebar-text">แจ้งซ่อมใหม่</span>
                </a>
            </li>
            <?php if ($isAdmin): ?>
                <li>
                    <a href="?page=maintenance" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'maintenance' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-tools-line text-lg"></i>
                        <span class="sidebar-text">รายการแจ้งซ่อม</span>
                    </a>
                </li>

                <li class="nav-section pt-4 pb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">ตั้งค่า</li>
                <li>
                    <a href="?page=settings" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'settings' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-settings-3-line text-lg"></i>
                        <span class="sidebar-text">ตั้งค่าระบบ</span>
                    </a>
                </li>
                <li>
                    <a href="?page=audit-log" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors <?= $page === 'audit-log' ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
                        <i class="ri-file-list-2-line text-lg"></i>
                        <span class="sidebar-text">Audit Log</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Footer -->
    <div class="border-t border-gray-100 p-4">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center font-medium">
                <?= mb_substr($user['fullname'] ?? $user['name'] ?? $user['username'] ?? 'U', 0, 1) ?>
            </div>
            <div class="user-details overflow-hidden">
                <div class="font-medium text-sm text-gray-900 truncate"><?= $user['fullname'] ?? $user['name'] ?? $user['username'] ?></div>
                <div class="text-xs text-gray-500 truncate"><?= $user['role_name'] ?? 'User' ?></div>
            </div>
        </div>
        <a href="<?= $linkBase ?>Modules/HRServices/public/index.php" class="flex items-center gap-2 text-gray-500 hover:text-primary text-sm transition-colors justify-center md:justify-start">
            <i class="ri-arrow-left-line"></i>
            <span class="sidebar-text">กลับสู่หน้าหลัก</span>
        </a>
    </div>
</aside>