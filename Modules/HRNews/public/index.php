<?php
// Use optimized session configuration (fixes Antivirus slowdown)
// Use optimized session configuration (fixes Antivirus slowdown)
require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
// startOptimizedSession(); // Moved to AuthMiddleware

require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Security/AuthMiddleware.php';

// Base paths
$basePath = rtrim(Env::get('APP_BASE_PATH', ''), '/');
if ($basePath === '') {
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $basePath = preg_replace('#/Modules/HRNews/public$#i', '', $scriptDir);
}
if ($basePath === '') {
    $basePath = '/';
}
$baseRoot = rtrim($basePath, '/');
$linkBase = ($baseRoot ? $baseRoot . '/' : '/');

$user = AuthMiddleware::checkLogin($linkBase);

// Determine asset base: check if DocumentRoot points to public/ folder (Docker) or htdocs (XAMPP)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
if ($docRoot && is_dir($docRoot . '/assets')) {
    // Docker: DocumentRoot is public/, assets are at /assets/
    $assetBase = ($baseRoot ? $baseRoot : '') . '/';
} else {
    // XAMPP: DocumentRoot is htdocs, assets are at /public/assets/
    $assetBase = ($baseRoot ? $baseRoot : '') . '/public/';
}

require_once __DIR__ . '/../../../core/Helpers/PermissionHelper.php';

// $user check handled by Middleware


if ((isset($user['user_active']) && !$user['user_active']) || (isset($user['role_active']) && !$user['role_active'])) {
    session_destroy();
    header('Location: ' . $redirectPath . '?error=role_inactive');
    exit;
}

$newsPerm = userHasModuleAccess('HR_NEWS', (int)$user['role_id']);
if (empty($newsPerm['can_view'])) {
    session_destroy();
    header('Location: ' . $redirectPath . '?error=no_permission');
    exit;
}

$userPerms = userHasModuleAccess('HR_SERVICES', (int)$user['role_id']);
$hrNewsPerm = $newsPerm;
$activityPerm = userHasModuleAccess('ACTIVITY_DASHBOARD', (int)$user['role_id']);
$emailLogPerm = userHasModuleAccess('EMAIL_LOGS', (int)$user['role_id']);
$scheduledPerm = userHasModuleAccess('SCHEDULED_REPORTS', (int)$user['role_id']);
require_once __DIR__ . '/../../../core/Config/Env.php';
function getPermissionModuleCode()
{
    try {
        $db = new Database();
        $conn = $db->getConnection();
        if ($conn) {
            $sql = "SELECT code FROM core_modules 
                    WHERE path LIKE '%modules/manage.php%' 
                       OR name LIKE '%permission%' 
                       OR code LIKE 'PERMISSION%' 
                    ORDER BY id ASC LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $code = $stmt->fetchColumn();
            if ($code) return $code;
        }
    } catch (Exception $e) {
    }
    return Env::get('PERMISSION_MODULE_CODE', 'PERMISSION_MANAGEMENT');
}
$permModuleCode = getPermissionModuleCode();
$permManage = userHasModuleAccess($permModuleCode, (int)$user['role_id']);
$canManageNews = !empty($newsPerm['can_manage']) || !empty($newsPerm['can_edit']);
$profilePic = $user['profile_picture'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR News | Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= $assetBase ?>assets/images/brand/inteqc-logo.png">

    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="<?= $assetBase ?>assets/css/tailwind.css">
    <script>
        window.APP_BASE_PATH = <?= json_encode($basePath) ?>;
    </script>
    <script src="<?= $assetBase ?>assets/js/i18n.js"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
        }

        .modal-overlay {
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .status-chip {
            transition: all 0.15s ease;
        }

        .status-chip:hover {
            transform: translateY(-1px);
        }

        .status-chip[data-active="true"] {
            box-shadow: 0 2px 8px rgba(162, 29, 33, 0.3);
        }

        .news-card {
            transition: all 0.2s ease;
        }

        .news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .gallery-slide {
            display: none;
        }

        .gallery-slide.active {
            display: block;
        }

        /* Image thumbnail grid */
        .thumb-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .thumb-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }

        .thumb-item:hover {
            transform: scale(1.05);
            border-color: #A21D21;
        }

        .thumb-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .thumb-item .thumb-delete {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .thumb-item:hover .thumb-delete {
            opacity: 1;
        }

        /* Image lightbox */
        .lightbox {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .lightbox.show {
            display: flex;
        }

        .lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include __DIR__ . '/../../../public/includes/header.php'; ?>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-6 py-8">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900" data-i18n="hrnews.page_title">HR News</h1>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $canManageNews ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                        <?= $canManageNews ? 'Manage' : 'View Only' ?>
                    </span>
                </div>
                <p class="text-gray-500 text-sm mt-1" data-i18n="hrnews.page_description">สร้าง/แก้ไข/ปักหมุดข่าว จัดคิวเผยแพร่ และแนบไฟล์หรือ Link</p>
            </div>
            <div class="flex items-center gap-3">
                <button id="refresh-btn" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <i class="ri-refresh-line"></i> <span data-i18n="hrnews.btn_refresh">รีเฟรช</span>
                </button>
                <button id="add-btn" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors shadow-sm" <?= $canManageNews ? '' : 'disabled' ?>>
                    <i class="ri-add-line"></i> <span data-i18n="hrnews.btn_create">สร้างข่าว</span>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
            <div class="p-4 flex flex-wrap items-center gap-3">
                <span class="text-sm font-semibold text-gray-600" data-i18n="hrnews.filter_status">สถานะ:</span>
                <button type="button" class="status-chip px-4 py-1.5 rounded-full text-sm font-medium bg-primary text-white" data-status="" data-active="true" data-i18n="hrnews.filter_all">ทั้งหมด</button>
                <button type="button" class="status-chip px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-emerald-100 hover:text-emerald-700" data-status="published" data-active="false" data-i18n="hrnews.status_published">Published</button>
                <button type="button" class="status-chip px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-700" data-status="scheduled" data-active="false" data-i18n="hrnews.status_scheduled">Scheduled</button>
                <button type="button" class="status-chip px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-yellow-100 hover:text-yellow-700" data-status="draft" data-active="false" data-i18n="hrnews.status_draft">Draft</button>
                <button type="button" class="status-chip px-4 py-1.5 rounded-full text-sm font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 hover:text-gray-800" data-status="archived" data-active="false" data-i18n="hrnews.status_archived">Archived</button>
            </div>
        </div>

        <!-- News List -->
        <div id="news-list" class="grid gap-4"></div>
        <div id="news-empty" class="hidden bg-white rounded-xl border border-gray-200 p-12 text-center">
            <i class="ri-newspaper-line text-5xl text-gray-300 mb-4 block"></i>
            <p class="text-gray-500" data-i18n="hrnews.no_news">ยังไม่มีข่าว</p>
        </div>
        <div id="news-error" class="hidden bg-red-50 border border-red-200 rounded-xl p-12 text-center">
            <i class="ri-error-warning-line text-5xl text-red-300 mb-4 block"></i>
            <p class="text-red-600" data-i18n="hrnews.load_error">โหลดข้อมูลไม่สำเร็จ</p>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" id="news-modal">
        <div class="bg-white rounded-2xl w-[70vw] max-h-[90vh] flex flex-col shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-primary to-primary-light rounded-t-2xl">
                <div class="text-white">
                    <h2 id="modal-title" class="text-lg font-semibold flex items-center gap-2"><i class="ri-newspaper-line"></i> สร้างข่าว</h2>
                    <p class="text-sm text-white/80">กรอกข้อมูลข่าวที่จะเผยแพร่</p>
                </div>
                <button class="w-10 h-10 flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors text-2xl" id="close-modal">&times;</button>
            </div>
            <form id="news-form" class="flex-1 overflow-y-auto">
                <input type="hidden" name="id" id="news-id">
                <input type="hidden" id="attachments-to-delete" name="attachments_to_delete">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-0">
                    <!-- Left Column: Main Content -->
                    <div class="lg:col-span-2 p-6 space-y-5 border-r border-gray-100">
                        <!-- Language Tabs -->
                        <div class="border-b border-gray-200 mb-4">
                            <div class="flex gap-4">
                                <button type="button" class="lang-tab active px-4 py-2 text-sm font-medium text-primary border-b-2 border-primary" data-tab="th">
                                    <span class="mr-1">🇹🇭</span> ไทย (Default)
                                </button>
                                <button type="button" class="lang-tab px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300" data-tab="en">
                                    <span class="mr-1">🇬🇧</span> English
                                </button>
                                <button type="button" class="lang-tab px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent hover:border-gray-300" data-tab="mm">
                                    <span class="mr-1">🇲🇲</span> မြန်မာ
                                </button>
                            </div>
                        </div>

                        <!-- Thai Fields (Default) -->
                        <div class="lang-panel space-y-5" data-lang="th">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-text text-primary"></i> <span data-i18n="hrnews.label_title">หัวข้อข่าว</span> (TH) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="title_th" name="title_th" required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-base"
                                    placeholder="ใส่หัวข้อข่าวภาษาไทย">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-file-text-line text-blue-500"></i> <span data-i18n="hrnews.label_summary">สรุปสั้น</span> (TH)
                                </label>
                                <input type="text" id="summary_th" name="summary_th"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="สรุปใจความสำคัญภาษาไทย">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-article-line text-emerald-500"></i> <span data-i18n="hrnews.label_content">เนื้อหาข่าว</span> (TH)
                                </label>
                                <textarea id="content_th" name="content_th" rows="8"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y"
                                    placeholder="รายละเอียดข่าวภาษาไทย..."></textarea>
                            </div>
                        </div>

                        <!-- English Fields -->
                        <div class="lang-panel hidden space-y-5" data-lang="en">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-text text-primary"></i> <span data-i18n="hrnews.label_title">Title</span> (EN)
                                </label>
                                <input type="text" id="title_en" name="title_en"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-base"
                                    placeholder="News Headline in English">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-file-text-line text-blue-500"></i> <span data-i18n="hrnews.label_summary">Summary</span> (EN)
                                </label>
                                <input type="text" id="summary_en" name="summary_en"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="Brief summary in English">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-article-line text-emerald-500"></i> <span data-i18n="hrnews.label_content">Content</span> (EN)
                                </label>
                                <textarea id="content_en" name="content_en" rows="8"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y"
                                    placeholder="Full details in English..."></textarea>
                            </div>
                        </div>

                        <!-- Myanmar Fields -->
                        <div class="lang-panel hidden space-y-5" data-lang="mm">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-text text-primary"></i> <span data-i18n="hrnews.label_title">Title</span> (MM)
                                </label>
                                <input type="text" id="title_mm" name="title_mm"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary text-base"
                                    placeholder="သတင်းခေါင်းစဉ်">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-file-text-line text-blue-500"></i> <span data-i18n="hrnews.label_summary">Summary</span> (MM)
                                </label>
                                <input type="text" id="summary_mm" name="summary_mm"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                    placeholder="အကျဉ်းချုပ်">
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-article-line text-emerald-500"></i> <span data-i18n="hrnews.label_content">Content</span> (MM)
                                </label>
                                <textarea id="content_mm" name="content_mm" rows="8"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary resize-y"
                                    placeholder="သတင်းအသေးစိတ်..."></textarea>
                            </div>
                        </div>

                        <!-- Link URL -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-link text-purple-500"></i> ลิงก์หลัก (ถ้ามี)
                            </label>
                            <input type="text" id="link_url" name="link_url"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary"
                                placeholder="https://...เชื่อมไปยังหน้า Form / Policy">
                        </div>
                    </div>

                    <!-- Right Column: Settings & Media -->
                    <div class="p-6 space-y-5 bg-gray-50/50">
                        <!-- Status -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-flag-line text-amber-500"></i> สถานะ
                            </label>
                            <select id="status" name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white">
                                <option value="draft">📝 Draft - แบบร่าง</option>
                                <option value="scheduled">⏰ Scheduled - ตั้งเวลา</option>
                                <option value="published">✅ Published - เผยแพร่แล้ว</option>
                                <option value="archived">📦 Archived - เก็บถาวร</option>
                            </select>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-calendar-check-line text-emerald-500"></i> เริ่มแสดง
                                </label>
                                <input type="datetime-local" id="publish_at" name="publish_at"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white text-sm">
                                <p class="text-xs text-gray-400 mt-1">ว่างไว้ = แสดงทันที</p>
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                    <i class="ri-calendar-close-line text-red-500"></i> สิ้นสุดการแสดง
                                </label>
                                <input type="datetime-local" id="expire_at" name="expire_at"
                                    class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary bg-white text-sm">
                                <p class="text-xs text-gray-400 mt-1">ว่างไว้ = แสดงตลอด</p>
                            </div>
                        </div>

                        <!-- Pin -->
                        <label class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl cursor-pointer hover:bg-amber-100 transition-colors">
                            <input type="checkbox" id="is_pinned" name="is_pinned" value="1" class="w-5 h-5 text-amber-500 rounded border-gray-300 focus:ring-amber-500">
                            <div>
                                <span class="text-sm font-medium text-amber-800">📌 ปักหมุดข่าวนี้</span>
                                <p class="text-xs text-amber-600">แสดงบนสุดของรายการ</p>
                            </div>
                        </label>

                        <!-- Hero Image -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-image-line text-pink-500"></i> ภาพปก (Hero)
                            </label>
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-3 hover:border-primary transition-colors">
                                <input type="file" id="hero_image_file" name="hero_image_file" accept="image/*"
                                    class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-primary file:text-white hover:file:bg-primary-dark cursor-pointer">
                                <input type="text" id="hero_image" name="hero_image" placeholder="หรือวาง URL รูปภาพ"
                                    class="w-full mt-2 px-3 py-2 border border-gray-200 rounded-lg text-sm">
                                <div id="hero-preview" class="hidden mt-2 w-full h-24 rounded-lg bg-cover bg-center"></div>
                            </div>
                        </div>

                        <!-- Body Images -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-gallery-line text-cyan-500"></i> ภาพประกอบ
                            </label>
                            <input type="file" id="body_image_file" name="body_image_file[]" accept="image/*" multiple
                                class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                            <div id="existing-body-images" class="flex flex-wrap gap-1 mt-2"></div>
                        </div>

                        <!-- Attachments -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-attachment-line text-gray-500"></i> ไฟล์แนบ
                            </label>
                            <input type="file" id="attachments" name="attachments[]" multiple
                                class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                            <div id="existing-attachments" class="flex flex-wrap gap-1 mt-2"></div>
                        </div>

                        <!-- Links -->
                        <div>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2">
                                <i class="ri-links-line text-indigo-500"></i> ลิงก์แนบ
                            </label>
                            <div id="link-list" class="space-y-2"></div>
                            <button type="button" class="mt-2 w-full py-2 text-sm text-primary border border-primary/30 rounded-lg hover:bg-red-50 transition-colors" id="add-link-btn">
                                <i class="ri-add-line"></i> เพิ่มลิงก์
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="flex flex-wrap items-center gap-3 px-6 py-4 bg-white border-t border-gray-100 rounded-b-2xl">
                <button type="button" class="px-5 py-2.5 bg-primary text-white rounded-xl font-medium hover:bg-primary-dark transition-colors shadow-sm flex items-center gap-2" id="save-news" <?= $canManageNews ? '' : 'disabled' ?>>
                    <i class="ri-save-line"></i> บันทึก
                </button>
                <button type="button" class="px-5 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition-colors flex items-center gap-2" id="preview-news">
                    <i class="ri-eye-line"></i> Preview
                </button>
                <button type="button" class="px-5 py-2.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50 rounded-xl font-medium transition-colors" id="cancel-news">ยกเลิก</button>
                <button type="button" class="ml-auto px-4 py-2.5 text-danger hover:bg-red-50 rounded-xl font-medium transition-colors flex items-center gap-2" id="delete-news" <?= $canManageNews ? '' : 'disabled' ?>>
                    <i class="ri-delete-bin-line"></i> ลบ
                </button>
            </div>
            <div id="form-status" class="hidden px-6 py-2 text-center font-medium"></div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" id="confirm-modal">
        <div class="bg-white rounded-xl w-full max-w-sm shadow-2xl">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 text-danger flex items-center justify-center mx-auto mb-4">
                    <i class="ri-error-warning-line text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">ยืนยันการลบ</h3>
                <p id="confirm-text" class="text-gray-500">ต้องการดำเนินการหรือไม่?</p>
            </div>
            <div class="flex gap-3 px-6 py-4 bg-gray-50 rounded-b-xl">
                <button type="button" class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors" id="confirm-cancel">ยกเลิก</button>
                <button type="button" class="flex-1 px-4 py-2 bg-danger text-white rounded-lg font-medium hover:bg-red-600 transition-colors" id="confirm-ok">ลบ</button>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal-overlay fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" id="preview-modal">
        <div class="bg-white rounded-2xl w-full max-w-3xl max-h-[90vh] flex flex-col shadow-2xl">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">ตัวอย่างข่าว</h3>
                <button type="button" class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors text-xl" id="preview-close">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                <div class="relative rounded-xl overflow-hidden bg-gray-100 min-h-[200px]" id="preview-gallery" style="display:none;">
                    <div class="gallery-track" id="pg-track"></div>
                    <button class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/50 text-white rounded-full hover:bg-black/70" id="pg-prev">&#9664;</button>
                    <button class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/50 text-white rounded-full hover:bg-black/70" id="pg-next">&#9654;</button>
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2" id="pg-dots"></div>
                </div>
                <div>
                    <h2 id="preview-title" class="text-xl font-bold text-gray-900"></h2>
                    <p id="preview-summary" class="text-gray-600 mt-1"></p>
                    <p id="preview-meta" class="text-sm text-gray-400 mt-1"></p>
                </div>
                <div id="preview-content" class="text-gray-700 whitespace-pre-wrap"></div>
                <a id="preview-main-link" class="hidden inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark" target="_blank" rel="noopener" href="#">
                    <i class="ri-external-link-line"></i> เปิดลิงก์หลัก
                </a>
                <div id="preview-body-images" class="hidden flex-wrap gap-2"></div>
                <div id="preview-files" class="hidden flex-wrap gap-2"></div>
                <div id="preview-links" class="hidden flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-[100] space-y-3"></div>

    <!-- Image Lightbox -->
    <div class="lightbox" id="image-lightbox">
        <button class="lightbox-close" id="lightbox-close">&times;</button>
        <img src="" alt="Preview" id="lightbox-img">
    </div>

    <script>
        const BASE_PATH = (window.APP_BASE_PATH || '').replace(/\/$/, '');
        const API_BASE = BASE_PATH + '/routes.php/hrnews';
        const CAN_MANAGE = <?= $canManageNews ? 'true' : 'false' ?>;
        let NEWS_CACHE = [];
        let currentAttachmentsDelete = [];
        let currentStatus = '';

        // Initialize i18n
        (async function initI18n() {
            await I18n.init();
            I18n.apply();

            // Update language button display
            const langBtn = document.getElementById('hr-lang-current');
            if (langBtn) {
                langBtn.textContent = I18n.getLocale().toUpperCase();
            }
        })();

        // Note: Language switching is handled by header.php's language selector

        // Toast notification
        function showToast(message, type = 'info') {
            const container = document.getElementById('toast-container');
            const colors = {
                success: 'bg-emerald-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-amber-500'
            };
            const icons = {
                success: 'ri-check-line',
                error: 'ri-error-warning-line',
                info: 'ri-information-line',
                warning: 'ri-alert-line'
            };
            const toast = document.createElement('div');
            toast.className = `flex items-center gap-3 px-4 py-3 ${colors[type] || colors.info} text-white rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
            toast.innerHTML = `<i class="${icons[type] || icons.info}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-x-full'), 100);
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Status pill classes
        const getStatusClass = (status) => {
            switch (status) {
                case 'published':
                    return 'bg-emerald-100 text-emerald-700';
                case 'scheduled':
                    return 'bg-blue-100 text-blue-700';
                case 'archived':
                    return 'bg-gray-200 text-gray-600';
                default:
                    return 'bg-yellow-100 text-yellow-700';
            }
        };

        const formatDate = (val) => {
            if (!val) return '-';
            return new Date(val).toLocaleString('th-TH', {
                dateStyle: 'short',
                timeStyle: 'short'
            });
        };

        // Render news list
        const renderList = (items) => {
            const list = document.getElementById('news-list');
            const empty = document.getElementById('news-empty');
            const error = document.getElementById('news-error');

            error.classList.add('hidden');
            empty.classList.toggle('hidden', items.length > 0);

            if (items.length === 0) {
                list.innerHTML = '';
                return;
            }

            const currentLocale = localStorage.getItem('i18n_locale') || 'th';

            list.innerHTML = items.map(item => {
                const fileAtts = (item.attachments || []).filter(a =>
                    a.attachment_type === 'file'
                );
                const attHtml = fileAtts.slice(0, 3).map(a =>
                    `<span class="inline-flex items-center px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs">📎 ${a.file_name}</span>`
                ).join('');

                // Determine display content based on locale
                let displayTitle = item.title;
                let displaySummary = item.summary;

                if (currentLocale !== 'th') {
                    try {
                        const titleTrans = typeof item.title_translations === 'string' ? JSON.parse(item.title_translations) : (item.title_translations || {});
                        if (titleTrans && titleTrans[currentLocale]) displayTitle = titleTrans[currentLocale];

                        const summaryTrans = typeof item.summary_translations === 'string' ? JSON.parse(item.summary_translations) : (item.summary_translations || {});
                        if (summaryTrans && summaryTrans[currentLocale]) displaySummary = summaryTrans[currentLocale];
                    } catch (e) {}
                }

                return `
    <article class="news-card bg-white border border-gray-200 rounded-xl p-5 cursor-pointer" data-id="${item.id}">
        <div class="flex items-start justify-between mb-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium ${getStatusClass(item.status)}">${item.status}</span>
                ${item.is_pinned ? '<span class="px-2.5 py-1 rounded-full text-xs font-medium bg-primary text-white"><i class="ri-pushpin-fill"></i> Pinned</span>' : ''}
            </div>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">${displayTitle}</h3>
        <p class="text-gray-500 text-sm mb-3 line-clamp-2">${displaySummary || ''}</p>
        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-400 mb-3">
            <span><i class="ri-time-line"></i> เริ่ม: ${formatDate(item.publish_at)}</span>
            <span><i class="ri-timer-line"></i> จบ: ${formatDate(item.expire_at)}</span>
            ${item.link_url ? `<a href="${item.link_url}" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline"><i class="ri-external-link-line"></i> Link</a>` : ''}
        </div>
        ${attHtml ? `<div class="flex flex-wrap gap-2 mb-3">${attHtml}</div>` : ''}
        <div class="flex items-center gap-1 pt-3 border-t border-gray-100">
            <button class="news-preview w-9 h-9 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-700 rounded-lg transition-colors" data-id="${item.id}" title="พรีวิว">
                <i class="ri-eye-line text-lg"></i>
            </button>
            <button class="news-edit w-9 h-9 flex items-center justify-center text-blue-500 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors" data-id="${item.id}" ${CAN_MANAGE ? '' : 'disabled' } title="แก้ไข">
                <i class="ri-edit-line text-lg"></i>
            </button>
            <button class="news-delete w-9 h-9 flex items-center justify-center text-red-400 hover:bg-red-50 hover:text-red-600 rounded-lg transition-colors ml-auto" data-id="${item.id}" ${CAN_MANAGE ? '' : 'disabled' } title="ลบ">
                <i class="ri-delete-bin-line text-lg"></i>
            </button>
        </div>
    </article>
    `;
            }).join('');

            document.querySelectorAll('.news-edit').forEach(btn => btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openModal(btn.dataset.id);
            }));
            document.querySelectorAll('.news-delete').forEach(btn => btn.addEventListener('click', (e) => {
                e.stopPropagation();
                handleDelete(btn.dataset.id);
            }));
            document.querySelectorAll('.news-preview').forEach(btn => btn.addEventListener('click', (e) => {
                e.stopPropagation();
                openPreviewById(btn.dataset.id);
            }));
        };

        // Load news
        async function loadNews(status = '') {
            try {
                const res = await fetch(`${API_BASE}/list${status ? '?status=' + status : ''}`, {
                    credentials: 'include'
                });
                if (!res.ok) throw new Error('load failed');
                const data = await res.json();
                NEWS_CACHE = data;
                renderList(data);
            } catch (err) {
                document.getElementById('news-error').classList.remove('hidden');
            }
        }

        // Filter chips - use data-active attribute to track state
        const statusColors = {
            '': {
                bg: 'bg-primary',
                text: 'text-white',
                hoverBg: '',
                hoverText: ''
            },
            'published': {
                bg: 'bg-emerald-100',
                text: 'text-emerald-700',
                hoverBg: 'hover:bg-emerald-100',
                hoverText: 'hover:text-emerald-700'
            },
            'scheduled': {
                bg: 'bg-blue-100',
                text: 'text-blue-700',
                hoverBg: 'hover:bg-blue-100',
                hoverText: 'hover:text-blue-700'
            },
            'draft': {
                bg: 'bg-yellow-100',
                text: 'text-yellow-700',
                hoverBg: 'hover:bg-yellow-100',
                hoverText: 'hover:text-yellow-700'
            },
            'archived': {
                bg: 'bg-gray-200',
                text: 'text-gray-700',
                hoverBg: 'hover:bg-gray-200',
                hoverText: 'hover:text-gray-800'
            }
        };

        document.querySelectorAll('.status-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                // Reset all chips to inactive gray state
                document.querySelectorAll('.status-chip').forEach(c => {
                    c.dataset.active = 'false';
                    // Remove all possible active colors
                    c.classList.remove('bg-primary', 'text-white', 'bg-emerald-100', 'text-emerald-700', 'bg-blue-100', 'text-blue-700', 'bg-yellow-100', 'text-yellow-700', 'bg-gray-200', 'text-gray-700');
                    // Add inactive gray
                    c.classList.add('bg-gray-100', 'text-gray-600');
                });

                // Set clicked chip to active with its status color
                const status = chip.dataset.status;
                const colors = statusColors[status] || statusColors[''];
                chip.dataset.active = 'true';
                chip.classList.remove('bg-gray-100', 'text-gray-600');
                chip.classList.add(colors.bg, colors.text);

                currentStatus = status;
                loadNews(currentStatus);
            });
        });

        // Language Tab Logic
        document.querySelectorAll('.lang-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                document.querySelectorAll('.lang-tab').forEach(t => {
                    t.classList.remove('active', 'text-primary', 'border-primary');
                    t.classList.add('text-gray-500', 'border-transparent');
                });
                tab.classList.add('active', 'text-primary', 'border-primary');
                tab.classList.remove('text-gray-500', 'border-transparent');

                // Show active panel
                const lang = tab.dataset.tab;
                document.querySelectorAll('.lang-panel').forEach(panel => {
                    panel.classList.toggle('hidden', panel.dataset.lang !== lang);
                });
            });
        });

        // Modal functions
        const newsModal = document.getElementById('news-modal');
        const confirmModal = document.getElementById('confirm-modal');
        const previewModal = document.getElementById('preview-modal');

        function openModal(id) {
            currentAttachmentsDelete = [];
            document.getElementById('attachments-to-delete').value = '';

            // Reset file inputs to prevent re-uploading files when not modified
            document.getElementById('hero_image_file').value = '';
            document.getElementById('body_image_file').value = '';
            document.getElementById('attachments').value = '';

            // Reset tabs to TH
            document.querySelector('.lang-tab[data-tab="th"]').click();

            if (id) {
                const item = NEWS_CACHE.find(n => String(n.id) === String(id));
                if (!item) return;
                document.getElementById('modal-title').textContent = 'แก้ไขข่าว';
                document.getElementById('news-id').value = item.id;

                // Parse translations
                let titleTrans = {},
                    summaryTrans = {},
                    contentTrans = {};
                try {
                    titleTrans = typeof item.title_translations === 'string' ? JSON.parse(item.title_translations) : (item.title_translations || {});
                } catch (e) {}
                try {
                    summaryTrans = typeof item.summary_translations === 'string' ? JSON.parse(item.summary_translations) : (item.summary_translations || {});
                } catch (e) {}
                try {
                    contentTrans = typeof item.content_translations === 'string' ? JSON.parse(item.content_translations) : (item.content_translations || {});
                } catch (e) {}

                // Populate TH (Default)
                document.getElementById('title_th').value = item.title || '';
                document.getElementById('summary_th').value = item.summary || '';
                document.getElementById('content_th').value = item.content || '';

                // Populate EN
                document.getElementById('title_en').value = titleTrans.en || '';
                document.getElementById('summary_en').value = summaryTrans.en || '';
                document.getElementById('content_en').value = contentTrans.en || '';

                // Populate MM
                document.getElementById('title_mm').value = titleTrans.mm || '';
                document.getElementById('summary_mm').value = summaryTrans.mm || '';
                document.getElementById('content_mm').value = contentTrans.mm || '';

                document.getElementById('status').value = item.status || 'draft';
                document.getElementById('publish_at').value = item.publish_at ? item.publish_at.slice(0, 16) : '';
                document.getElementById('expire_at').value = item.expire_at ? item.expire_at.slice(0, 16) : '';
                document.getElementById('link_url').value = item.link_url || '';
                document.getElementById('is_pinned').checked = !!item.is_pinned;
                document.getElementById('hero_image').value = item.hero_image || '';

                // Hero preview
                const heroPreview = document.getElementById('hero-preview');
                if (item.hero_image) {
                    const heroUrl = item.hero_image.startsWith('http') ? item.hero_image : (BASE_PATH + item.hero_image);
                    heroPreview.style.backgroundImage = `url('${heroUrl}')`;
                    heroPreview.classList.remove('hidden');
                } else {
                    heroPreview.classList.add('hidden');
                }

                // Existing attachments
                renderExistingAttachments(item.attachments || []);
                document.getElementById('delete-news').style.display = 'block';
            } else {
                document.getElementById('modal-title').textContent = 'สร้างข่าว';
                document.getElementById('news-form').reset();
                document.getElementById('news-id').value = '';
                document.getElementById('hero-preview').classList.add('hidden');
                document.getElementById('existing-attachments').innerHTML = '';
                document.getElementById('existing-body-images').innerHTML = '';
                document.getElementById('link-list').innerHTML = '';
                document.getElementById('delete-news').style.display = 'none';
            }
            newsModal.classList.add('show');
        }

        function closeNewsModal() {
            newsModal.classList.remove('show');
        }

        function renderExistingAttachments(attachments) {
            const filesWrap = document.getElementById('existing-attachments');
            const bodyImgWrap = document.getElementById('existing-body-images');
            const linkList = document.getElementById('link-list');

            filesWrap.innerHTML = '';
            bodyImgWrap.innerHTML = '';
            linkList.innerHTML = '';

            // Add thumb-grid class to image containers
            bodyImgWrap.className = 'thumb-grid mt-2';
            filesWrap.className = 'thumb-grid mt-2';

            attachments.forEach(att => {
                if (att.attachment_type === 'link') {
                    const row = document.createElement('div');
                    row.className = 'link-row flex gap-2 items-center';
                    row.innerHTML = `
    <input type="text" value="${att.file_name || ''}" placeholder="ชื่อ" class="flex-1 min-w-0 px-3 py-2 border border-gray-200 rounded-lg text-sm">
    <input type="text" value="${att.file_url || ''}" placeholder="URL" class="flex-[2] min-w-0 px-3 py-2 border border-gray-200 rounded-lg text-sm">
    <button type="button" class="remove-link w-8 h-8 flex-none flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg">&times;</button>
    `;
                    linkList.appendChild(row);
                } else if (att.attachment_type === 'body_image') {
                    // Image thumbnail - prepend BASE_PATH to relative URLs
                    const imgUrl = att.file_url.startsWith('http') ? att.file_url : (BASE_PATH + att.file_url);
                    const thumb = document.createElement('div');
                    thumb.className = 'thumb-item';
                    thumb.dataset.id = att.id;
                    thumb.dataset.url = imgUrl;
                    thumb.innerHTML = `
    <img src="${imgUrl}" alt="${att.file_name}" loading="lazy">
    <button type="button" class="thumb-delete" title="ลบรูป">&times;</button>
    `;
                    // Click to view
                    thumb.querySelector('img').addEventListener('click', () => openLightbox(imgUrl));
                    // Delete button
                    thumb.querySelector('.thumb-delete').addEventListener('click', (e) => {
                        e.stopPropagation();
                        console.log('Delete body image - att.id:', att.id, 'attachment_type:', att.attachment_type);
                        if (att.id) currentAttachmentsDelete.push(att.id);
                        console.log('currentAttachmentsDelete:', JSON.stringify(currentAttachmentsDelete));
                        document.getElementById('attachments-to-delete').value = JSON.stringify(currentAttachmentsDelete);
                        thumb.remove();
                    });
                    bodyImgWrap.appendChild(thumb);
                } else {
                    // File attachment - prepend BASE_PATH
                    const fileUrl = att.file_url.startsWith('http') ? att.file_url : (BASE_PATH + att.file_url);
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-lg text-sm';
                    fileItem.dataset.id = att.id;
                    fileItem.innerHTML = `
    <i class="ri-file-line text-gray-500"></i>
    <a href="${fileUrl}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline flex-1 truncate">${att.file_name}</a>
    <button type="button" class="remove-file text-red-500 hover:bg-red-50 w-6 h-6 rounded flex items-center justify-center">&times;</button>
    `;
                    fileItem.querySelector('.remove-file').addEventListener('click', () => {
                        if (att.id) currentAttachmentsDelete.push(att.id);
                        document.getElementById('attachments-to-delete').value = JSON.stringify(currentAttachmentsDelete);
                        fileItem.remove();
                    });
                    filesWrap.appendChild(fileItem);
                }
            });

            document.querySelectorAll('.remove-link').forEach(btn => {
                btn.addEventListener('click', () => btn.closest('.link-row').remove());
            });
        }

        // Lightbox functions
        const lightbox = document.getElementById('image-lightbox');
        const lightboxImg = document.getElementById('lightbox-img');

        function openLightbox(url) {
            lightboxImg.src = url;
            lightbox.classList.add('show');
        }

        function closeLightbox() {
            lightbox.classList.remove('show');
            lightboxImg.src = '';
        }

        document.getElementById('lightbox-close').addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });

        // File input preview for new uploads
        document.getElementById('body_image_file').addEventListener('change', (e) => {
            const files = e.target.files;
            if (!files.length) return;
            const previewWrap = document.getElementById('existing-body-images');
            previewWrap.className = 'thumb-grid mt-2';

            Array.from(files).forEach(file => {
                const url = URL.createObjectURL(file);
                const thumb = document.createElement('div');
                thumb.className = 'thumb-item new-upload';
                thumb.innerHTML = `
    <img src="${url}" alt="${file.name}" loading="lazy">
    <span class="absolute bottom-0 left-0 right-0 bg-emerald-500 text-white text-[10px] text-center">ใหม่</span>
    `;
                thumb.querySelector('img').addEventListener('click', () => openLightbox(url));
                previewWrap.appendChild(thumb);
            });
        });

        // Event listeners
        document.getElementById('add-btn').addEventListener('click', () => openModal());
        document.getElementById('close-modal').addEventListener('click', closeNewsModal);
        document.getElementById('cancel-news').addEventListener('click', closeNewsModal);
        document.getElementById('refresh-btn').addEventListener('click', () => loadNews(currentStatus));

        document.getElementById('add-link-btn').addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'link-row flex gap-2 items-center';
            row.innerHTML = `
    <input type="text" placeholder="ชื่อลิงก์" class="flex-1 min-w-0 px-3 py-2 border border-gray-200 rounded-lg text-sm">
    <input type="text" placeholder="URL" class="flex-[2] min-w-0 px-3 py-2 border border-gray-200 rounded-lg text-sm">
    <button type="button" class="remove-link w-8 h-8 flex-none flex items-center justify-center text-red-500 hover:bg-red-50 rounded-lg">&times;</button>
    `;
            row.querySelector('.remove-link').addEventListener('click', () => row.remove());
            document.getElementById('link-list').appendChild(row);
        });

        // Hero image preview
        document.getElementById('hero_image_file').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const preview = document.getElementById('hero-preview');
                preview.style.backgroundImage = `url('${URL.createObjectURL(file)}')`;
                preview.classList.remove('hidden');
            }
        });

        document.getElementById('hero_image').addEventListener('input', (e) => {
            const url = e.target.value.trim();
            const preview = document.getElementById('hero-preview');
            if (url) {
                preview.style.backgroundImage = `url('${url}')`;
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        });

        // Save news
        document.getElementById('save-news').addEventListener('click', async () => {
            const form = document.getElementById('news-form');
            const formData = new FormData(form);

            // Map TH fields to main fields
            formData.set('title', document.getElementById('title_th').value);
            formData.set('summary', document.getElementById('summary_th').value);
            formData.set('content', document.getElementById('content_th').value);

            // Build Translation JSONs
            const titleTrans = {
                en: document.getElementById('title_en').value,
                mm: document.getElementById('title_mm').value
            };
            const summaryTrans = {
                en: document.getElementById('summary_en').value,
                mm: document.getElementById('summary_mm').value
            };
            const contentTrans = {
                en: document.getElementById('content_en').value,
                mm: document.getElementById('content_mm').value
            };

            formData.append('title_translations', JSON.stringify(titleTrans));
            formData.append('summary_translations', JSON.stringify(summaryTrans));
            formData.append('content_translations', JSON.stringify(contentTrans));

            // Collect links (attachment_links)
            const links = [];
            document.querySelectorAll('#link-list .link-row').forEach(row => {
                const inputs = row.querySelectorAll('input');
                const label = (inputs[0]?.value || '').trim();
                const url = (inputs[1]?.value || '').trim();
                if (url) links.push({
                    label,
                    url
                });
            });
            formData.append('attachment_links', JSON.stringify(links));

            try {
                // Debug: Log what we're sending
                console.log('Saving news - attachments_to_delete:', formData.get('attachments_to_delete'));

                // Backend NewsController expects ?action=save_news
                const res = await fetch(`${API_BASE}?action=save_news`, {
                    method: 'POST',
                    credentials: 'include',
                    body: formData
                });
                const result = await res.json();
                if (!res.ok || result.error) throw new Error(result.message || result.error || 'บันทึกไม่สำเร็จ');
                showToast('บันทึกสำเร็จ', 'success');
                closeNewsModal();
                loadNews(currentStatus);
            } catch (err) {
                showToast(err.message, 'error');
            }
        });

        // Delete news
        let confirmResolver = null;

        function showConfirm(text) {
            document.getElementById('confirm-text').textContent = text;
            confirmModal.classList.add('show');
            return new Promise(resolve => {
                confirmResolver = resolve;
            });
        }

        function closeConfirm() {
            confirmModal.classList.remove('show');
        }

        document.getElementById('confirm-cancel').addEventListener('click', () => {
            closeConfirm();
            if (confirmResolver) confirmResolver(false);
        });
        document.getElementById('confirm-ok').addEventListener('click', () => {
            if (confirmResolver) confirmResolver(true);
            closeConfirm();
        });
        document.getElementById('confirm-modal').addEventListener('click', (e) => {
            if (e.target.id === 'confirm-modal') {
                closeConfirm();
                if (confirmResolver) confirmResolver(false);
            }
        });

        async function handleDelete(id) {
            const item = NEWS_CACHE.find(n => String(n.id) === String(id));
            const title = item ? item.title : `ID ${id}`;
            const ok = await showConfirm(`ลบข่าว "${title}" ?`);
            if (!ok) return;

            try {
                // Backend NewsController expects ?action=delete_news
                const res = await fetch(`${API_BASE}?action=delete_news`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        id
                    })
                });
                if (!res.ok) throw new Error('ลบไม่สำเร็จ');
                showToast('ลบข่าวสำเร็จ', 'success');
                closeNewsModal();
                loadNews(currentStatus);
            } catch (err) {
                showToast(err.message, 'error');
            }
        }

        document.getElementById('delete-news').addEventListener('click', () => {
            const id = document.getElementById('news-id').value;
            if (id) handleDelete(id);
        });

        // Preview
        document.getElementById('preview-news').addEventListener('click', () => showPreview());
        document.getElementById('preview-close').addEventListener('click', () => previewModal.classList.remove('show'));
        previewModal.addEventListener('click', (e) => {
            if (e.target === previewModal) previewModal.classList.remove('show');
        });

        function openPreviewById(id) {
            const item = NEWS_CACHE.find(n => String(n.id) === String(id));
            if (item) showPreview(item);
        }

        function showPreview(dataFromCache = null) {
            const title = dataFromCache ? dataFromCache.title : document.getElementById('title_th').value;
            const summary = dataFromCache ? dataFromCache.summary : document.getElementById('summary_th').value;
            const content = dataFromCache ? dataFromCache.content : document.getElementById('content_th').value;
            const status = dataFromCache ? dataFromCache.status : document.getElementById('status').value;
            const publishAt = dataFromCache ? dataFromCache.publish_at : document.getElementById('publish_at').value;
            const expireAt = dataFromCache ? dataFromCache.expire_at : document.getElementById('expire_at').value;
            const mainLink = dataFromCache ? dataFromCache.link_url : document.getElementById('link_url').value;

            document.getElementById('preview-title').textContent = title;
            document.getElementById('preview-summary').textContent = summary;
            document.getElementById('preview-content').textContent = content;
            document.getElementById('preview-meta').textContent = `สถานะ: ${status} • เริ่ม: ${publishAt || 'ทันที'} • สิ้นสุด: ${expireAt || 'แสดงตลอด'}`;

            const previewMainLink = document.getElementById('preview-main-link');
            if (mainLink) {
                previewMainLink.classList.remove('hidden');
                previewMainLink.href = mainLink;
            } else {
                previewMainLink.classList.add('hidden');
            }

            // Hero Image - intentionally excluded from gallery to match Login Page behavior

            const bodyList = [];

            // Body images
            if (dataFromCache) {
                const attachs = (dataFromCache.attachments || []).filter(a => a.attachment_type === 'body_image').map(a => ({
                    name: a.file_name,
                    url: a.file_url.startsWith('http') ? a.file_url : (BASE_PATH + a.file_url)
                }));
                bodyList.push(...attachs);
            } else {
                // From Form DOM (Preview Mode) - Scrape visible thumbnails
                document.querySelectorAll('#existing-body-images .thumb-item img').forEach(img => {
                    bodyList.push({
                        name: 'Preview', // Name not available on thumb scan easily, generic name
                        url: img.src
                    });
                });
            }

            renderPreviewGallery(bodyList);

            // Files
            const fileWrap = document.getElementById('preview-files');
            const fileList = dataFromCache ?
                (dataFromCache.attachments || []).filter(a => a.attachment_type === 'file').map(a => ({
                    name: a.file_name,
                    url: a.file_url
                })) : [];

            if (fileList.length) {
                fileWrap.classList.remove('hidden');
                fileWrap.classList.add('flex');
                fileWrap.innerHTML = fileList.map(f => `<a class="px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm" href="${f.url}" target="_blank" rel="noopener noreferrer">📎 ${f.name}</a>`).join('');
            } else {
                fileWrap.classList.add('hidden');
            }

            // Links
            const linksWrap = document.getElementById('preview-links');
            const links = dataFromCache ?
                (dataFromCache.attachments || []).filter(a => a.attachment_type === 'link').map(a => ({
                    label: a.file_name,
                    url: a.file_url
                })) : [];

            if (links.length) {
                linksWrap.classList.remove('hidden');
                linksWrap.classList.add('flex');
                linksWrap.innerHTML = links.map(l => `<a class="px-3 py-1.5 bg-yellow-50 text-yellow-700 rounded-lg text-sm" href="${l.url}" target="_blank" rel="noopener noreferrer">🔗 ${l.label || l.url}</a>`).join('');
            } else {
                linksWrap.classList.add('hidden');
            }

            previewModal.classList.add('show');
        }

        function renderPreviewGallery(bodyList) {
            const gallery = document.getElementById('preview-gallery');
            const track = document.getElementById('pg-track');
            const dots = document.getElementById('pg-dots');
            const prev = document.getElementById('pg-prev');
            const next = document.getElementById('pg-next');

            if (!bodyList || !bodyList.length) {
                gallery.style.display = 'none';
                return;
            }

            gallery.style.display = 'block';
            track.innerHTML = bodyList.map((img, idx) => `<div class="gallery-slide ${idx === 0 ? 'active' : ''}" style="width:100%;height:300px;background-size:contain;background-repeat:no-repeat;background-position:center;background-color:#f5f5f5;background-image:url('${img.url || ''}');"></div>`).join('');
            dots.innerHTML = bodyList.map((_, idx) => `<span class="w-2 h-2 rounded-full ${idx === 0 ? 'bg-primary' : 'bg-white/50'} cursor-pointer" data-idx="${idx}"></span>`).join('');

            let current = 0;
            const slides = track.querySelectorAll('.gallery-slide');
            const dotEls = dots.querySelectorAll('span');

            const show = (i) => {
                current = (i + slides.length) % slides.length;
                slides.forEach((s, idx) => s.classList.toggle('active', idx === current));
                dotEls.forEach((d, idx) => {
                    d.classList.toggle('bg-primary', idx === current);
                    d.classList.toggle('bg-white/50', idx !== current);
                });
            };

            prev.onclick = () => show(current - 1);
            next.onclick = () => show(current + 1);
            dotEls.forEach(d => d.addEventListener('click', () => show(parseInt(d.dataset.idx, 10))));
        }

        // Close modals on escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeConfirm();
                if (confirmResolver) confirmResolver(false);
            }
        });

        // Listen for language changes
        window.addEventListener('language-changed', () => {
            loadNews(currentStatus);
        });

        // Click outside to close
        newsModal.addEventListener('click', (e) => {
            if (e.target === newsModal) closeNewsModal();
        });

        // Initial load
        loadNews();
    </script>
</body>

</html>