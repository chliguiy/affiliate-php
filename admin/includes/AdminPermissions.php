<?php
class AdminPermissions {
    private $pdo;
    private $admin_id;
    private $permissions;

    public function __construct($pdo, $admin_id) {
        $this->pdo = $pdo;
        $this->admin_id = $admin_id;
        $this->loadPermissions();
    }

    private function loadPermissions() {
        try {
            $stmt = $this->pdo->prepare("SELECT role, permissions FROM admins WHERE id = ?");
            $stmt->execute([$this->admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                $permissions = json_decode($admin['permissions'] ?? '[]', true);
                
                if (is_array($permissions) && !empty($permissions)) {
                    $this->permissions = $permissions;
                } else {
                    $this->permissions = $this->getRolePermissions($admin['role']);
                }
            } else {
                $this->permissions = [];
            }
        } catch (PDOException $e) {
            error_log("Erreur lors du chargement des permissions : " . $e->getMessage());
            $this->permissions = [];
        }
    }

    private function getRolePermissions($role) {
        // Définir les permissions par rôle
        $permissions = [
            'super_admin' => [
                'canManageAdmins',
                'canManageUsers',
                'canManageProducts',
                'canManageOrders',
                'canManageCategories',
                'canManageStock',
                'canViewReports',
                'canManageSettings',
                'canViewDashboard',
                'canManageBackups',
                'canViewLogs'
            ],
            'admin' => [
                'canManageAdmins',
                'canManageUsers',
                'canManageProducts',
                'canManageOrders',
                'canManageCategories',
                'canManageStock',
                'canViewReports',
                'canViewDashboard',
                'canViewLogs'
            ],
            'manager' => [
                'canManageProducts',
                'canManageOrders',
                'canManageStock',
                'canViewReports',
                'canViewDashboard'
            ],
            'viewer' => [
                'canViewReports',
                'canViewDashboard'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    public function canManageAdmins() {
        return in_array('canManageAdmins', $this->permissions);
    }

    public function canManageUsers() {
        return in_array('canManageUsers', $this->permissions);
    }

    public function canManageProducts() {
        return in_array('canManageProducts', $this->permissions);
    }

    public function canManageOrders() {
        return in_array('canManageOrders', $this->permissions);
    }

    public function canManageCategories() {
        return in_array('canManageCategories', $this->permissions);
    }

    public function canManageStock() {
        return in_array('canManageStock', $this->permissions);
    }

    public function canViewReports() {
        return in_array('canViewReports', $this->permissions);
    }

    public function canManageSettings() {
        return in_array('canManageSettings', $this->permissions);
    }

    public function canViewDashboard() {
        return in_array('canViewDashboard', $this->permissions);
    }

    public function canManageBackups() {
        return in_array('canManageBackups', $this->permissions);
    }

    public function canViewLogs() {
        return in_array('canViewLogs', $this->permissions);
    }

    public function hasPermission($permission) {
        return in_array($permission, $this->permissions);
    }

    public function getAllPermissions() {
        return $this->permissions;
    }
} 