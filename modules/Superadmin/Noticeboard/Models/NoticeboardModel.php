<?php

declare(strict_types=1);

namespace App\Modules\Superadmin\Noticeboard\Models;

use App\Core\Database;
use PDO;

class NoticeboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllNotices(): array
    {
        $stmt = $this->db->query("SELECT * FROM public.noticeboard ORDER BY priority DESC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveNotices(): array
    {
        $stmt = $this->db->query("SELECT * FROM public.noticeboard WHERE is_active = TRUE ORDER BY priority DESC, created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM public.noticeboard WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO public.noticeboard (title, content, badge_type, priority, is_active, created_by)
            VALUES (:title, :content, :badge_type, :priority, :is_active, :created_by)
        ");
        return $stmt->execute([
            'title' => $data['title'],
            'content' => $data['content'],
            'badge_type' => $data['badge_type'] ?? 'Announcement',
            'priority' => (int)($data['priority'] ?? 1),
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE public.noticeboard
            SET title = :title,
                content = :content,
                badge_type = :badge_type,
                priority = :priority,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'content' => $data['content'],
            'badge_type' => $data['badge_type'] ?? 'Announcement',
            'priority' => (int)($data['priority'] ?? 1),
            'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM public.noticeboard WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
