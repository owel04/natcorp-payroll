<?php
require_once __DIR__ . '/../config.php';

class Employee {
    private $conn;
    private $has_user_status_column = null;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }

    private function hasUserStatusColumn() {
        if ($this->has_user_status_column === null) {
            $result = $this->conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            $this->has_user_status_column = $result && $result->num_rows > 0;
        }
        return $this->has_user_status_column;
    }

    public function ensureUserStatusColumn() {
        if (!$this->hasUserStatusColumn()) {
            $this->conn->query("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER role");
            $this->has_user_status_column = null;
            return $this->hasUserStatusColumn();
        }
        return true;
    }
    
    public function addEmployee($employee_data) {
        // Check if employee already exists
        $sql = "SELECT id FROM employees WHERE employee_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $employee_data['employee_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Employee ID already exists'];
        }
        
        // Create a unique username from the employee name
        $first = preg_replace('/[^a-z0-9]/', '', strtolower($employee_data['first_name']));
        $last = preg_replace('/[^a-z0-9]/', '', strtolower($employee_data['last_name']));
        $username_base = trim(($first !== '' ? $first : 'employee') . '.' . ($last !== '' ? $last : 'user'), '.');
        $username = $username_base;
        $counter = 1;

        $check_sql = "SELECT id FROM users WHERE username = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        while (true) {
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows === 0) {
                break;
            }
            $username = $username_base . $counter;
            $counter++;
        }

        $password = password_hash('admin123', PASSWORD_BCRYPT);
        
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'employee')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $username, $employee_data['email'], $password);
        
        if ($stmt->execute()) {
            $user_id = $this->conn->insert_id;
            
            // Insert employee
            $sql = "INSERT INTO employees (user_id, employee_id, first_name, last_name, email, department, position, phone, dob, date_of_joining, client_company) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $client_company = $employee_data['client_company'] ?? '';
            $stmt->bind_param("issssssssss", 
                $user_id,
                $employee_data['employee_id'],
                $employee_data['first_name'],
                $employee_data['last_name'],
                $employee_data['email'],
                $employee_data['department'],
                $employee_data['position'],
                $employee_data['phone'],
                $employee_data['dob'],
                $employee_data['date_of_joining'],
                $client_company
            );
            
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Employee added successfully', 'employee_id' => $this->conn->insert_id];
            } else {
                return ['success' => false, 'message' => 'Failed to add employee'];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to create user account'];
    }

    public function addUserAccount($user_data) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $user_data['username'], $user_data['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        $password = password_hash($user_data['password'], PASSWORD_BCRYPT);
        if ($this->hasUserStatusColumn()) {
            $sql = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')";
        } else {
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $user_data['username'], $user_data['email'], $password, $user_data['role']);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User account created successfully'];
        }

        return ['success' => false, 'message' => 'Failed to create user account'];
    }
    
    public function updateEmployee($employee_id, $employee_data) {
        if (!empty($employee_data['employee_id'])) {
            $sql = "SELECT id FROM employees WHERE employee_id = ? AND id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $employee_data['employee_id'], $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                return ['success' => false, 'message' => 'Employee ID already exists'];
            }
        }

        $this->conn->begin_transaction();
        try {
            $sql = "UPDATE employees SET employee_id = ?, first_name = ?, last_name = ?, email = ?, department = ?, position = ?, phone = ?, dob = ?, date_of_joining = ?, client_company = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssssssssi",
                $employee_data['employee_id'],
                $employee_data['first_name'],
                $employee_data['last_name'],
                $employee_data['email'],
                $employee_data['department'],
                $employee_data['position'],
                $employee_data['phone'],
                $employee_data['dob'],
                $employee_data['date_of_joining'],
                $employee_data['client_company'],
                $employee_id
            );
            $stmt->execute();

            $sql = "UPDATE users u JOIN employees e ON u.id = e.user_id SET u.email = ? WHERE e.id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $employee_data['email'], $employee_id);
            $stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Employee updated successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update employee: ' . $e->getMessage()];
        }
    }

    public function updateEmployeeProfile($user_id, $email, $phone) {
        $this->conn->begin_transaction();
        try {
            $sql = "UPDATE users SET email = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();

            $sql = "UPDATE employees SET email = ?, phone = ? WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", $email, $phone, $user_id);
            $stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }
    
    public function getEmployee($employee_id) {
        $sql = "SELECT * FROM employees WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getEmployeeByUserId($user_id) {
        $sql = "SELECT * FROM employees WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAllEmployees($limit = 100, $offset = 0) {
        $sql = "SELECT * FROM employees ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public function searchEmployees($search_term) {
        $search = "%$search_term%";
        $sql = "SELECT * FROM employees WHERE employee_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? ORDER BY first_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $search, $search, $search, $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAdmins() {
        if ($this->hasUserStatusColumn()) {
            $sql = "SELECT id, username, email, status FROM users WHERE role = 'admin' ORDER BY username ASC";
        } else {
            $sql = "SELECT id, username, email, 'active' AS status FROM users WHERE role = 'admin' ORDER BY username ASC";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAdmin($admin_id) {
        if ($this->hasUserStatusColumn()) {
            $sql = "SELECT id, username, email, status FROM users WHERE id = ? AND role = 'admin'";
        } else {
            $sql = "SELECT id, username, email, 'active' AS status FROM users WHERE id = ? AND role = 'admin'";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateAdmin($admin_id, $admin_data) {
        $sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $admin_data['username'], $admin_data['email'], $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }

        $this->conn->begin_transaction();
        try {
            $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssi", $admin_data['username'], $admin_data['email'], $admin_id);
            $stmt->execute();

            if (!empty($admin_data['password'])) {
                $hashed_password = password_hash($admin_data['password'], PASSWORD_BCRYPT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $admin_id);
                $stmt->execute();
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Admin account updated successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Failed to update admin: ' . $e->getMessage()];
        }
    }

    public function deactivateAdmin($admin_id) {
        if (!$this->ensureUserStatusColumn()) {
            return false;
        }
        $sql = "UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        return $stmt->execute();
    }

    public function activateAdmin($admin_id) {
        if (!$this->ensureUserStatusColumn()) {
            return false;
        }
        $sql = "UPDATE users SET status = 'active' WHERE id = ? AND role = 'admin'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $admin_id);
        return $stmt->execute();
    }
    
    public function deactivateEmployee($employee_id) {
        $sql = "UPDATE employees SET status = 'inactive' WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        return $stmt->execute();
    }
    
    public function activateEmployee($employee_id) {
        $sql = "UPDATE employees SET status = 'active' WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        return $stmt->execute();
    }
}
