<?php

require_once __DIR__ . '/../../../core/Database/Database.php';
require_once __DIR__ . '/../../../core/Config/Env.php';
require_once __DIR__ . '/../../../core/Helpers/UrlHelper.php';

use Core\Helpers\UrlHelper;

class NewsController
{
    private $conn;
    private $user;
    private $roleId;
    private $roleActive;
    private $canView = false;
    private $canEdit = false;
    private $canManage = false;
    private $basePath;
    private $publicBase;

    public function __construct()
    {
        require_once __DIR__ . '/../../../core/Config/SessionConfig.php';
        if (function_exists('startOptimizedSession')) {
            startOptimizedSession();
        } else {
            if (session_status() === PHP_SESSION_NONE) session_start();
        }

        $db = new Database();
        $this->conn = $db->getConnection();
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

        if (!$this->conn || !$this->roleId) {
            return;
        }

        $sql = "
            SELECT COALESCE(p.can_view, 0) AS can_view,
                   COALESCE(p.can_edit, 0) AS can_edit,
                   COALESCE(p.can_delete, 0) AS can_delete,
                   COALESCE(p.can_manage, 0) AS can_manage
            FROM core_modules cm
            LEFT JOIN core_module_permissions p
              ON p.module_id = cm.id AND p.role_id = :role_id
            WHERE cm.code = 'HR_NEWS'
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':role_id', $this->roleId, PDO::PARAM_INT);
        $stmt->execute();
        $perm = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($perm) {
            $this->canView = (bool)$perm['can_view'];
            $this->canEdit = (bool)($perm['can_edit'] || $perm['can_manage']);
            $this->canManage = (bool)$perm['can_manage'];
        }
    }

    private function requireAuth(bool $needManage = false): bool
    {
        if (!$this->conn || !$this->user || !$this->roleId) {
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

    private function fetchAttachmentsForNews(array $newsIds): array
    {
        if (empty($newsIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($newsIds), '?'));
        $stmt = $this->conn->prepare("SELECT id, news_id, file_name, file_path, file_url, mime_type, file_size, attachment_type FROM hr_news_attachments WHERE news_id IN ($placeholders)");
        foreach ($newsIds as $idx => $nid) {
            $stmt->bindValue($idx + 1, $nid, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['news_id']][] = $row;
        }
        return $grouped;
    }

    private function listNews(): void
    {
        if (!$this->requireAuth(false)) {
            return;
        }

        $statusFilter = $_GET['status'] ?? null;
        $showAll = $this->canEdit;

        $where = [];
        $params = [];

        if ($statusFilter && in_array($statusFilter, ['draft', 'scheduled', 'published', 'archived'])) {
            $where[] = 'n.status = :status';
            $params[':status'] = $statusFilter;
        }

        if (!$showAll) {
            $where[] = '((n.status = "published") OR (n.status = "scheduled" AND n.publish_at IS NOT NULL AND n.publish_at <= NOW()))';
            $where[] = '(n.expire_at IS NULL OR n.expire_at > NOW())';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT n.id, n.title, n.summary, n.content, n.status, n.is_pinned,
                   n.title_translations, n.summary_translations, n.content_translations,
                   n.publish_at, n.expire_at, n.hero_image, n.link_url,
                   n.created_at, n.updated_at
            FROM hr_news n
            $whereSql
            ORDER BY n.is_pinned DESC, COALESCE(n.publish_at, n.created_at) DESC, n.id DESC
        ";
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attachments = $this->fetchAttachmentsForNews(array_column($rows, 'id'));
        foreach ($rows as &$row) {
            $row['attachments'] = $attachments[$row['id']] ?? [];
        }

        echo json_encode($rows);
    }

    private function listPublished(): void
    {
        // Public-facing: only published and within schedule
        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            return;
        }

        $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 6;

        $sql = "
            SELECT n.id, n.title, n.summary, n.content, n.status, n.is_pinned,
                   n.title_translations, n.summary_translations, n.content_translations,
                   n.publish_at, n.expire_at, n.hero_image, n.link_url,
                   n.created_at, n.updated_at
            FROM hr_news n
            WHERE (n.status = 'published'
               OR (n.status = 'scheduled' AND n.publish_at IS NOT NULL AND n.publish_at <= NOW()))
              AND (n.expire_at IS NULL OR n.expire_at > NOW())
            ORDER BY n.is_pinned DESC, COALESCE(n.publish_at, n.created_at) DESC
            LIMIT :lim
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $attachments = $this->fetchAttachmentsForNews(array_column($rows, 'id'));
        foreach ($rows as &$row) {
            $row['attachments'] = $attachments[$row['id']] ?? [];
        }

        echo json_encode($rows);
    }

    private function saveNews(): void
    {
        if (!$this->requireAuth(true)) {
            return;
        }

        if (!$this->conn) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
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
            $stmtHero = $this->conn->prepare("SELECT hero_image FROM hr_news WHERE id = :id LIMIT 1");
            $stmtHero->bindValue(':id', $id, PDO::PARAM_INT);
            $stmtHero->execute();
            $currentHeroImage = $stmtHero->fetchColumn();
        }

        // If uploaded hero image exists, upload and override $heroImage
        $uploadedHero = $this->handleHeroUpload($currentHeroImage);
        if ($uploadedHero) {
            $heroImage = $uploadedHero;
        }

        // Insert/update news
        if ($id > 0) {
            $sql = "
                UPDATE hr_news
                SET title = :title,
                    summary = :summary,
                    content = :content,
                    title_translations = :title_translations,
                    summary_translations = :summary_translations,
                    content_translations = :content_translations,
                    status = :status,
                    is_pinned = :is_pinned,
                    publish_at = :publish_at,
                    expire_at = :expire_at,
                    hero_image = :hero_image,
                    link_url = :link_url,
                    updated_by = :uid
                WHERE id = :id
            ";
        } else {
            $sql = "
                INSERT INTO hr_news (title, summary, content, title_translations, summary_translations, content_translations, status, is_pinned, publish_at, expire_at, hero_image, link_url, created_by, updated_by)
                VALUES (:title, :summary, :content, :title_translations, :summary_translations, :content_translations, :status, :is_pinned, :publish_at, :expire_at, :hero_image, :link_url, :uid, :uid)
            ";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':summary', $summary);
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':title_translations', $titleTranslations);
        $stmt->bindValue(':summary_translations', $summaryTranslations);
        $stmt->bindValue(':content_translations', $contentTranslations);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':is_pinned', $isPinned, PDO::PARAM_INT);
        $stmt->bindValue(':publish_at', $publishAt !== '' ? $publishAt : null);
        $stmt->bindValue(':expire_at', $expireAt !== '' ? $expireAt : null);
        $stmt->bindValue(':hero_image', $heroImage !== '' ? $heroImage : null);
        $stmt->bindValue(':link_url', $linkUrl !== '' ? $linkUrl : null);
        $stmt->bindValue(':uid', $this->user['id'] ?? null, PDO::PARAM_INT);
        if ($id > 0) {
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        }

        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['message' => 'บันทึกข่าวไม่สำเร็จ']);
            return;
        }

        $newsId = $id > 0 ? $id : intval($this->conn->lastInsertId());

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
        $stmt = $this->conn->prepare("
            INSERT INTO hr_news_attachments (news_id, file_name, file_path, file_url, mime_type, file_size, attachment_type)
            VALUES (:news_id, :file_name, :file_path, :file_url, :mime_type, :file_size, :attachment_type)
        ");
        $stmt->bindValue(':news_id', $newsId, PDO::PARAM_INT);
        $stmt->bindValue(':file_name', $payload['file_name']);
        $stmt->bindValue(':file_path', $payload['file_path']);
        $stmt->bindValue(':file_url', $payload['file_url']);
        $stmt->bindValue(':mime_type', $payload['mime_type']);
        $stmt->bindValue(':file_size', $payload['file_size']);
        $stmt->bindValue(':attachment_type', $payload['attachment_type'] ?? 'file');
        $stmt->execute();
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
        $stmt = $this->conn->prepare("SELECT id FROM hr_news_attachments WHERE news_id = :nid AND (mime_type = 'link' OR attachment_type = 'link')");
        $stmt->bindValue(':nid', $newsId, PDO::PARAM_INT);
        $stmt->execute();
        $linkIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        if (!empty($linkIds)) {
            $this->deleteAttachmentsByIds($newsId, $linkIds);
        }
    }

    private function deleteAttachmentsByIds(int $newsId, array $ids): void
    {
        if (empty($ids)) return;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("SELECT id, file_path FROM hr_news_attachments WHERE news_id = ? AND id IN ($placeholders)");
        $stmt->bindValue(1, $newsId, PDO::PARAM_INT);
        foreach ($ids as $idx => $id) {
            $stmt->bindValue($idx + 2, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            if (!empty($row['file_path']) && file_exists($row['file_path'])) {
                @unlink($row['file_path']);
            }
        }
        $del = $this->conn->prepare("DELETE FROM hr_news_attachments WHERE news_id = ? AND id IN ($placeholders)");
        $del->bindValue(1, $newsId, PDO::PARAM_INT);
        foreach ($ids as $idx => $id) {
            $del->bindValue($idx + 2, $id, PDO::PARAM_INT);
        }
        $del->execute();
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
        $stmt = $this->conn->prepare("SELECT id FROM hr_news_attachments WHERE news_id = :nid");
        $stmt->bindValue(':nid', $id, PDO::PARAM_INT);
        $stmt->execute();
        $attIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        if (!empty($attIds)) {
            $this->deleteAttachmentsByIds($id, $attIds);
        }

        $del = $this->conn->prepare("DELETE FROM hr_news WHERE id = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        if ($del->execute()) {
            echo json_encode(['message' => 'ลบสำเร็จ']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'ลบข่าวไม่สำเร็จ']);
        }
    }
}
