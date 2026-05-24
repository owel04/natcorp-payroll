<?php
require_once __DIR__ . '/../config.php';

class Auth {
    private $conn;
    private $lastError = '';
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->ensureRememberColumn();
        $this->ensureStatusColumn();
    }
    
    private function ensureRememberColumn() {
        $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'remember_token'");
        if ($result && $result->num_rows === 0) {
            $this->conn->query("ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL AFTER password");
        }
    }

    private function ensureStatusColumn() {
        $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($result && $result->num_rows === 0) {
            $this->conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER role");
        }
    }
    
    public function login($username, $password, $remember = false) {
        $this->lastError = '';
        $sql = "SELECT u.*, e.status AS employee_status, u.status AS user_status FROM users u LEFT JOIN employees e ON u.id = e.user_id WHERE u.username = ? OR e.employee_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (($user['role'] === 'employee' && isset($user['employee_status']) && $user['employee_status'] !== 'active') ||
                ($user['role'] !== 'employee' && isset($user['user_status']) && $user['user_status'] !== 'active')) {
                $this->lastError = 'Your account has been deactivated. Please contact admin.';
                $this->logAudit(null, "Login blocked for inactive account: $username", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                return false;
            }
            
            // Support both plain text and hashed passwords
            if ($password === $user['password'] || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($remember) {
                    $this->rememberMe($user['id']);
                }
                
                // Log audit
                $this->logAudit($user['id'], "Login successful", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                
                return true;
            }
        }
        
        if (!$this->lastError) {
            $this->lastError = 'Invalid username or password';
        }
        
        $this->logAudit(null, "Login failed for username: $username", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return false;
    }

    public function autoLogin() {
        if ($this->isLoggedIn() || empty($_COOKIE['remember_me'])) {
            return false;
        }

        $cookie = $_COOKIE['remember_me'];
        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            return false;
        }

        list($user_id, $token) = $parts;
        if (!ctype_digit($user_id) || empty($token)) {
            return false;
        }

        $sql = "SELECT u.*, e.status AS employee_status, u.status AS user_status FROM users u LEFT JOIN employees e ON u.id = e.user_id WHERE u.id = ? AND u.remember_token = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (($user['role'] === 'employee' && isset($user['employee_status']) && $user['employee_status'] !== 'active') ||
                ($user['role'] !== 'employee' && isset($user['user_status']) && $user['user_status'] !== 'active')) {
                return false;
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $this->logAudit($user['id'], "Auto login successful", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            return true;
        }

        return false;
    }

    public function rememberMe($user_id) {
        $token = bin2hex(random_bytes(24));
        $sql = "UPDATE users SET remember_token = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $token, $user_id);
        $stmt->execute();

        $cookie_path = defined('COOKIE_PATH') ? COOKIE_PATH : '/00';
        setcookie('remember_me', $user_id . ':' . $token, time() + 60 * 60 * 24 * 30, $cookie_path, '', false, true);
    }

    public function clearRememberMe($user_id = null) {
        if (!$user_id && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }

        if ($user_id) {
            $sql = "UPDATE users SET remember_token = NULL WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }

        $cookie_path = defined('COOKIE_PATH') ? COOKIE_PATH : '/00';
        setcookie('remember_me', '', time() - 3600, $cookie_path, '', false, true);
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function verifyPasswordByUserId($user_id, $password) {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // Support both plain text and hashed passwords
            return ($password === $user['password'] || password_verify($password, $user['password']));
        }

        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAudit($_SESSION['user_id'], "Logout", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->clearRememberMe($_SESSION['user_id']);
        }
        session_destroy();
        return true;
    }

    public function logAction($action) {
        $userId = $_SESSION['user_id'] ?? null;
        $this->logAudit($userId, $action, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function isEmployee() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
    }
    
    public function register($username, $email, $password, $role = 'employee') {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($old_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $this->logAudit($user_id, "Password changed", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
                    return true;
                }
            }
        }
        return false;
    }

    public function resetPassword($user_id, $new_password) {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            $this->logAudit($user_id, "Password reset by admin", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            return true;
        }
        return false;
    }
    
    private function logAudit($user_id, $action, $ip_address) {
        $sql = "INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $action, $ip_address);
        $stmt->execute();
    }
}
