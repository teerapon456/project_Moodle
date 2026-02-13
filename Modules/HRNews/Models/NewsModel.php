<?php

class NewsModel
{
    private $conn;

    public function __construct($pdo)
    {
        $this->conn = $pdo;
    }

    /**
     * Get user permissions for HR News module
     */
    public function getPermissions($roleId)
    {
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
        $stmt->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch attachments for a list of news IDs
     */
    public function getAttachmentsByNewsIds(array $newsIds)
    {
        if (empty($newsIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($newsIds), '?'));
        $sql = "SELECT id, news_id, file_name, file_path, file_url, mime_type, file_size, attachment_type 
                FROM hr_news_attachments 
                WHERE news_id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
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

    /**
     * Get all news (with filters)
     */
    public function getAll($statusFilter = null, $showAll = false)
    {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get published news with limit
     */
    public function getPublished($limit = 6)
    {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get hero image path
     */
    public function getHeroImage($id)
    {
        $stmt = $this->conn->prepare("SELECT hero_image FROM hr_news WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * Create news
     */
    public function create($data)
    {
        $sql = "
            INSERT INTO hr_news (title, summary, content, title_translations, summary_translations, content_translations, status, is_pinned, publish_at, expire_at, hero_image, link_url, created_by, updated_by)
            VALUES (:title, :summary, :content, :title_translations, :summary_translations, :content_translations, :status, :is_pinned, :publish_at, :expire_at, :hero_image, :link_url, :uid, :uid)
        ";
        $stmt = $this->conn->prepare($sql);
        $this->bindNewsParams($stmt, $data);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Update news
     */
    public function update($id, $data)
    {
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
        $stmt = $this->conn->prepare($sql);
        $this->bindNewsParams($stmt, $data);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Helper to bind params for create/update
     */
    private function bindNewsParams($stmt, $data)
    {
        $stmt->bindValue(':title', $data['title']);
        $stmt->bindValue(':summary', $data['summary']);
        $stmt->bindValue(':content', $data['content']);
        $stmt->bindValue(':title_translations', $data['title_translations']);
        $stmt->bindValue(':summary_translations', $data['summary_translations']);
        $stmt->bindValue(':content_translations', $data['content_translations']);
        $stmt->bindValue(':status', $data['status']);
        $stmt->bindValue(':is_pinned', $data['is_pinned'], PDO::PARAM_INT);
        $stmt->bindValue(':publish_at', $data['publish_at'] !== '' ? $data['publish_at'] : null);
        $stmt->bindValue(':expire_at', $data['expire_at'] !== '' ? $data['expire_at'] : null);
        $stmt->bindValue(':hero_image', $data['hero_image'] !== '' ? $data['hero_image'] : null);
        $stmt->bindValue(':link_url', $data['link_url'] !== '' ? $data['link_url'] : null);
        $stmt->bindValue(':uid', $data['uid'] ?? null, PDO::PARAM_INT);
    }

    /**
     * Insert attachment record
     */
    public function insertAttachment($newsId, $payload)
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
        return $stmt->execute();
    }

    /**
     * Get attachments by IDs
     */
    public function getAttachmentsByIds($newsId, $ids)
    {
        if (empty($ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("SELECT id, file_path FROM hr_news_attachments WHERE news_id = ? AND id IN ($placeholders)");
        $stmt->bindValue(1, $newsId, PDO::PARAM_INT);
        foreach ($ids as $idx => $id) {
            $stmt->bindValue($idx + 2, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete attachments by IDs
     */
    public function deleteAttachmentsByIds($newsId, $ids)
    {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $del = $this->conn->prepare("DELETE FROM hr_news_attachments WHERE news_id = ? AND id IN ($placeholders)");
        $del->bindValue(1, $newsId, PDO::PARAM_INT);
        foreach ($ids as $idx => $id) {
            $del->bindValue($idx + 2, $id, PDO::PARAM_INT);
        }
        return $del->execute();
    }

    /**
     * Get link attachment IDs
     */
    public function getLinkAttachmentIds($newsId)
    {
        $stmt = $this->conn->prepare("SELECT id FROM hr_news_attachments WHERE news_id = :nid AND (mime_type = 'link' OR attachment_type = 'link')");
        $stmt->bindValue(':nid', $newsId, PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

    /**
     * Get all attachments for a specific news ID
     */
    public function getAttachmentsByNewsId($newsId)
    {
        $stmt = $this->conn->prepare("SELECT id FROM hr_news_attachments WHERE news_id = :nid");
        $stmt->bindValue(':nid', $newsId, PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

    /**
     * Delete news by ID
     */
    public function delete($id)
    {
        $del = $this->conn->prepare("DELETE FROM hr_news WHERE id = :id");
        $del->bindValue(':id', $id, PDO::PARAM_INT);
        return $del->execute();
    }
}
