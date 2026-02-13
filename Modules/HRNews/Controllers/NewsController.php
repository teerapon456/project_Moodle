<?php

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

class NewsController
{
    private $newsModel;

    public function __construct()
    {
        require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }

        require_once __DIR__ . '/../Models/NewsModel.php';
        $db = new Database();
        $this->newsModel = new NewsModel($db->getConnection());

        $this->user = $_SESSION['user'] ?? null;
        $this->roleId = $this->user['role_id'] ?? null;
        $this->roleActive = $this->user['role_active'] ?? 1;

        // Use UrlHelper for dynamic path resolution
        $this->basePath = UrlHelper::getBasePath();
        // Store relative paths without base path for Docker compatibility
        // The frontend will prepend the base path when displaying
        $this->publicBase = '/public';

        $this->hydratePermissions();
    }

    private function hydratePermissions(): void
    {
        $this->canView = false;
        $this->canEdit = false;
        $this->canManage = false;

        if (!$this->roleId) {
            return;
        }

        $perm = $this->newsModel->getPermissions($this->roleId);
        if ($perm) {
            $this->canView = (bool)$perm['can_view'];
            $this->canEdit = (bool)($perm['can_edit'] || $perm['can_manage']);
            $this->canManage = (bool)$perm['can_manage'];
        }
    }

    private function requireAuth(bool $needManage = false): bool
    {
        if (!$this->user || !$this->roleId) {
            http_response_code(401);
            echo json_encode(['message' => 'Not authenticated']);
            return false;
        }

        if (!$this->roleActive) {
            http_response_code(403);
            echo json_encode(['message' => 'Role นี้ถูกปิดใช้งาน']);
            return false;
        }

        if (!$this->canView) {
            http_response_code(403);
            echo json_encode(['message' => 'ไม่มีสิทธิ์เข้าถึง HR_NEWS']);
            return false;
        }

        if ($needManage && !$this->canEdit) {
            http_response_code(403);
            echo json_encode(['message' => 'ไม่มีสิทธิ์จัดการ HR_NEWS']);
            return false;
        }

        return true;
    }

    public function processRequest(): void
    {
        header("Content-Type: application/json; charset=UTF-8");

        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';

        if ($method === 'GET') {
            switch ($action) {
                case 'published':
                    $this->listPublished();
                    break;
                case 'list':
                default:
                    $this->listNews();
                    break;
            }
        } elseif ($method === 'POST') {
            switch ($action) {
                case 'save_news':
                    $this->saveNews();
                    break;
                case 'delete_news':
                    $this->deleteNews();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['message' => 'Invalid action']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['message' => 'Method not allowed']);
        }
    }

    private function listNews(): void
    {
        if (!$this->requireAuth(false)) {
            return;
        }

        $statusFilter = $_GET['status'] ?? null;
        $showAll = $this->canEdit;

        $rows = $this->newsModel->getAll($statusFilter, $showAll);

        if (!empty($rows)) {
            $attachments = $this->newsModel->getAttachmentsByNewsIds(array_column($rows, 'id'));
            foreach ($rows as &$row) {
                $row['attachments'] = $attachments[$row['id']] ?? [];
            }
        }

        echo json_encode($rows);
    }

    private function listPublished(): void
    {
        // Public-facing: only published and within schedule
        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 6;
        $rows = $this->newsModel->getPublished($limit);

        if (!empty($rows)) {
            $attachments = $this->newsModel->getAttachmentsByNewsIds(array_column($rows, 'id'));
            foreach ($rows as &$row) {
                $row['attachments'] = $attachments[$row['id']] ?? [];
            }
        }

        echo json_encode($rows);
    }

    private function saveNews(): void
    {
        if (!$this->requireAuth(true)) {
            return;
        }

        // Expect multipart/form-data to support file upload
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $titleTranslations = $_POST['title_translations'] ?? null;
        $summaryTranslations = $_POST['summary_translations'] ?? null;
        $contentTranslations = $_POST['content_translations'] ?? null;
        $status = $_POST['status'] ?? 'draft';
        $isPinned = !empty($_POST['is_pinned']) ? 1 : 0;
        $publishAt = $_POST['publish_at'] ?? null;
        $expireAt = $_POST['expire_at'] ?? null;
        $heroImage = trim($_POST['hero_image'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $attachmentLinks = [];
        if (!empty($_POST['attachment_links'])) {
            $decoded = json_decode($_POST['attachment_links'], true);
            if (is_array($decoded)) {
                $attachmentLinks = $decoded;
            }
        }
        $attachmentsToDelete = [];
        if (!empty($_POST['attachments_to_delete'])) {
            $decoded = json_decode($_POST['attachments_to_delete'], true);
            if (is_array($decoded)) {
                $attachmentsToDelete = array_map('intval', $decoded);
            }
        }

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['message' => 'กรอกหัวข้อข่าว']);
            return;
        }

        if (!in_array($status, ['draft', 'scheduled', 'published', 'archived'])) {
            $status = 'draft';
        }

        // Fetch current hero for cleanup if updating
        $currentHeroImage = null;
        if ($id > 0) {
            $currentHeroImage = $this->newsModel->getHeroImage($id);
        }

        // If uploaded hero image exists, upload and override $heroImage
        $uploadedHero = $this->handleHeroUpload($currentHeroImage);
        if ($uploadedHero) {
            $heroImage = $uploadedHero;
        }

        $newsData = [
            'title' => $title,
            'summary' => $summary,
            'content' => $content,
            'title_translations' => $titleTranslations,
            'summary_translations' => $summaryTranslations,
            'content_translations' => $contentTranslations,
            'status' => $status,
            'is_pinned' => $isPinned,
            'publish_at' => $publishAt,
            'expire_at' => $expireAt,
            'hero_image' => $heroImage,
            'link_url' => $linkUrl,
            'uid' => $this->user['id'] ?? null
        ];

        // Insert/update news
        if ($id > 0) {
            if (!$this->newsModel->update($id, $newsData)) {
                http_response_code(500);
                echo json_encode(['message' => 'บันทึกข่าวไม่สำเร็จ']);
                return;
            }
        } else {
            $id = $this->newsModel->create($newsData);
            if (!$id) {
                http_response_code(500);
                echo json_encode(['message' => 'บันทึกข่าวไม่สำเร็จ']);
                return;
            }
        }

        $newsId = intval($id);

        // Handle attachments to delete
        if (!empty($attachmentsToDelete)) {
            $this->deleteAttachmentsByIds($newsId, $attachmentsToDelete);
        }

        // Handle new file uploads
        $this->handleFileUploads($newsId);

        // Handle link attachments - only replace if links were submitted
        // If attachment_links is empty or not submitted, keep existing links
        if (!empty($attachmentLinks)) {
            $this->deleteLinkAttachments($newsId);
            foreach ($attachmentLinks as $link) {
                if (!is_array($link)) continue;
                $label = trim($link['label'] ?? '');
                $url = trim($link['url'] ?? '');
                if ($url === '') continue;
                $this->insertAttachment($newsId, [
                    'file_name' => $label ?: $url,
                    'file_path' => null,
                    'file_url' => $url,
                    'mime_type' => 'link',
                    'file_size' => null,
                    'attachment_type' => 'link'
                ]);
            }
        }

        echo json_encode(['message' => 'บันทึกสำเร็จ', 'id' => $newsId]);
    }

    private function handleFileUploads(int $newsId): void
    {
        $uploadBaseUrl = '/Modules/HRNews/uploads';
        // Regular attachments
        $this->handleUploadField($newsId, 'attachments', $uploadBaseUrl . '/attachments', __DIR__ . '/../uploads/attachments', 'file');
        // Body images (flagged as body_image mime to separate from ไฟล์แนบ)
        $this->handleUploadField($newsId, 'body_image_file', $uploadBaseUrl . '/body', __DIR__ . '/../uploads/body', 'body_image');
    }

    private function handleUploadField(int $newsId, string $field, string $publicBase, string $dirBase, ?string $attachmentType = null): void
    {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
            return;
        }

        $uploadDir = realpath($dirBase);
        if ($uploadDir === false) {
            if (!is_dir($dirBase)) {
                mkdir($dirBase, 0775, true);
            }
            $uploadDir = realpath($dirBase);
        }
        $names = $_FILES[$field]['name'];
        $tmpNames = $_FILES[$field]['tmp_name'];
        $sizes = $_FILES[$field]['size'];
        $types = $_FILES[$field]['type'];
        $errors = $_FILES[$field]['error'];

        foreach ($names as $idx => $originalName) {
            if ($errors[$idx] !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmpPath = $tmpNames[$idx];
            $mime = $types[$idx] ?? '';
            $size = intval($sizes[$idx] ?? 0);
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
            $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
            $destPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
            if (move_uploaded_file($tmpPath, $destPath)) {
                $relativePath = $publicBase . '/' . $uniqueName;
                $this->insertAttachment($newsId, [
                    'file_name' => $originalName,
                    'file_path' => $destPath,
                    'file_url' => $relativePath,
                    'mime_type' => $mime,
                    'file_size' => $size,
                    'attachment_type' => $attachmentType ?: 'file'
                ]);
            }
        }
    }

    private function insertAttachment(int $newsId, array $payload): void
    {
        $this->newsModel->insertAttachment($newsId, $payload);
    }

    private function handleHeroUpload(?string $oldPath = null): ?string
    {
        if (empty($_FILES['hero_image_file']) || !is_array($_FILES['hero_image_file'])) {
            return null;
        }
        $file = $_FILES['hero_image_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!$tmp || !is_uploaded_file($tmp)) {
            return null;
        }
        $mime = $file['type'] ?? '';
        if (strpos($mime, 'image/') !== 0) {
            return null;
        }

        $uploadDir = realpath(__DIR__ . '/../uploads/hero');
        if ($uploadDir === false) {
            $baseDir = __DIR__ . '/../uploads/hero';
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0775, true);
            }
            $uploadDir = realpath($baseDir);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name'] ?? 'hero');
        $uniqueName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safeName;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;
        if (move_uploaded_file($tmp, $destPath)) {
            // Delete old hero if it lived in our upload dir
            if ($oldPath && strpos($oldPath, '/Modules/HRNews/uploads/hero/') !== false) {
                $fullOld = realpath(__DIR__ . '/../uploads/hero/' . basename($oldPath));
                if ($fullOld && file_exists($fullOld)) {
                    @unlink($fullOld);
                }
            }
            // Return relative path - frontend will handle base path
            return '/Modules/HRNews/uploads/hero/' . $uniqueName;
        }
        return null;
    }

    private function deleteLinkAttachments(int $newsId): void
    {
        $linkIds = $this->newsModel->getLinkAttachmentIds($newsId);
        if (!empty($linkIds)) {
            $this->deleteAttachmentsByIds($newsId, $linkIds);
        }
    }

    private function deleteAttachmentsByIds(int $newsId, array $ids): void
    {
        if (empty($ids)) return;

        // 1. Get file paths to unlink
        $rows = $this->newsModel->getAttachmentsByIds($newsId, $ids);
        foreach ($rows as $row) {
            if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                @unlink($row['file_path']);
            }
        }

        // 2. Delete from DB
        $this->newsModel->deleteAttachmentsByIds($newsId, $ids);
    }

    private function deleteNews(): void
    {
        if (!$this->requireAuth(true)) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'id required']);
            return;
        }

        // Delete attachments first
        $attIds = $this->newsModel->getAttachmentsByNewsId($id);
        if (!empty($attIds)) {
            $this->deleteAttachmentsByIds($id, $attIds);
        }

        if ($this->newsModel->delete($id)) {
            echo json_encode(['message' => 'ลบสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'ลบข่าวไม่สำเร็จ']);
        }
    }
}
