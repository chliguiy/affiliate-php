<?php
require_once __DIR__ . '/AdminPermissions.php';

class AdminLogger {
    private $pdo;
    private $admin_id;
    private $permissions;

    public function __construct($pdo, $admin_id) {
        $this->pdo = $pdo;
        $this->admin_id = $admin_id;
        $this->permissions = new AdminPermissions($pdo, $admin_id);
    }

    public function log($action, $entity_type, $entity_id = null, $details = []) {
        if (!$this->permissions->hasPermission('canViewLogs')) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_logs (admin_id, action, entity_type, entity_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $details_json = !empty($details) ? json_encode($details) : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;

            return $stmt->execute([
                $this->admin_id,
                $action,
                $entity_type,
                $entity_id,
                $details_json,
                $ip_address
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la journalisation : " . $e->getMessage());
            return false;
        }
    }

    public function getLogs($limit = 100, $offset = 0, $filters = []) {
        if (!$this->permissions->hasPermission('canViewLogs')) {
            return [];
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['admin_id'])) {
                $where[] = "l.admin_id = ?";
                $params[] = $filters['admin_id'];
            }

            if (!empty($filters['action'])) {
                $where[] = "l.action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['entity_type'])) {
                $where[] = "l.entity_type = ?";
                $params[] = $filters['entity_type'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "l.created_at >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "l.created_at <= ?";
                $params[] = $filters['date_to'];
            }

            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $stmt = $this->pdo->prepare("
                SELECT l.*, a.username as admin_username
                FROM admin_logs l
                LEFT JOIN admins a ON l.admin_id = a.id
                {$where_clause}
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?
            ");

            $params[] = $limit;
            $params[] = $offset;

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des logs : " . $e->getMessage());
            return [];
        }
    }

    public function getLogCount($filters = []) {
        if (!$this->permissions->hasPermission('canViewLogs')) {
            return 0;
        }

        try {
            $where = [];
            $params = [];

            if (!empty($filters['admin_id'])) {
                $where[] = "admin_id = ?";
                $params[] = $filters['admin_id'];
            }

            if (!empty($filters['action'])) {
                $where[] = "action = ?";
                $params[] = $filters['action'];
            }

            if (!empty($filters['entity_type'])) {
                $where[] = "entity_type = ?";
                $params[] = $filters['entity_type'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "created_at >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $where[] = "created_at <= ?";
                $params[] = $filters['date_to'];
            }

            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total
                FROM admin_logs
                {$where_clause}
            ");

            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des logs : " . $e->getMessage());
            return 0;
        }
    }
} 