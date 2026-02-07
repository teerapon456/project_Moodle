<?php
// Applicant Login View
?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">
        <div>
            <div class="mx-auto h-16 w-16 bg-primary/10 rounded-full flex items-center justify-center text-primary mb-4">
                <i class="ri-survey-line text-3xl"></i>
            </div>
            <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
                เข้าสู่ระบบประเมิน
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                สำหรับผู้สมัครภายนอก (Applicants)
            </p>
        </div>

        <?php if (isset($_GET['registered'])): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-lg flex items-center gap-2 text-sm">
                <i class="ri-checkbox-circle-line text-lg"></i>
                ลงทะเบียนสำเร็จ กรุณาเข้าสู่ระบบ
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg flex items-center gap-2 text-sm">
                <i class="ri-error-warning-line text-lg"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="index.php?controller=auth&action=loginPost" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">อีเมล</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none rounded-t-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="อีเมล">
                </div>
                <div>
                    <label for="password" class="sr-only">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none rounded-none rounded-b-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="รหัสผ่าน">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        จดจำฉัน
                    </label>
                </div>

                <div class="text-sm">
                    <a href="#" class="font-medium text-primary hover:text-primary-dark">
                        ลืมรหัสผ่าน?
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary shadow-lg shadow-primary/30 transition-all">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3 text-primary-light group-hover:text-white transition-colors">
                        <i class="ri-lock-2-line"></i>
                    </span>
                    เข้าสู่ระบบ
                </button>
            </div>
        </form>

        <div class="text-center text-sm">
            <span class="text-gray-500">ยังไม่มีบัญชี?</span>
            <a href="index.php?controller=auth&action=register" class="font-medium text-primary hover:text-primary-dark ml-1">
                ลงทะเบียนใหม่
            </a>
        </div>

        <div class="mt-6 border-t border-gray-100 pt-6 text-center">
            <a href="../../Modules/HRServices/public/index.php" class="text-xs text-gray-400 hover:text-gray-600 flex items-center justify-center gap-1">
                <i class="ri-building-line"></i> สำหรับพนักงานภายใน (Internal Login)
            </a>
        </div>
    </div>
</div>