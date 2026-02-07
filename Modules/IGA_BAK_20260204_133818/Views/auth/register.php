<?php
// Applicant Register View
?>
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 bg-white p-10 rounded-xl shadow-lg border border-gray-100">
        <div>
            <div class="mx-auto h-16 w-16 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 mb-4">
                <i class="ri-user-add-line text-3xl"></i>
            </div>
            <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
                ลงทะเบียนผู้สมัครใหม่
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                กรอกข้อมูลเพื่อสร้างบัญชีสำหรับทำแบบทดสอบ
            </p>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg flex items-center gap-2 text-sm">
                <i class="ri-error-warning-line text-lg"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" action="index.php?controller=auth&action=registerPost" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                    <input id="full_name" name="full_name" type="text" required class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="เช่น นายสมชาย ใจดี">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล (ใช้เป็นชื่อผู้ใช้)</label>
                    <input id="email" name="email" type="email" autocomplete="email" required class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="example@email.com">
                </div>

                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                        <input id="phone" name="phone" type="tel" class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="08x-xxx-xxxx">
                    </div>
                    <div>
                        <label for="organization" class="block text-sm font-medium text-gray-700 mb-1">บริษัท/หน่วยงาน (ถ้ามี)</label>
                        <input id="organization" name="organization" type="text" class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="ระบุชื่อบริษัท">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="อย่างน้อย 6 ตัวอักษร">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">ยืนยันรหัสผ่าน</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required class="appearance-none rounded-lg relative block w-full px-3 py-2.5 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary sm:text-sm" placeholder="พิมพ์รหัสผ่านอีกครั้ง">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg shadow-blue-600/30 transition-all">
                    ลงทะเบียน
                </button>
            </div>

            <div class="text-center text-sm">
                <span class="text-gray-500">มีบัญชีอยู่แล้ว?</span>
                <a href="index.php?controller=auth&action=login" class="font-medium text-primary hover:text-primary-dark ml-1">
                    เข้าสู่ระบบ
                </a>
            </div>
        </form>
    </div>
</div>