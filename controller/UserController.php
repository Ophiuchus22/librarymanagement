<?php
require_once '../database/Database.php';
require_once 'Session.php';

class UserController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password) {
        try {
            $query = "SELECT user_id, username, password, role, first_name, last_name 
                      FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Use password_verify to compare plain-text password with hashed password
                if (password_verify($password, $row['password'])) {
                    Session::start();
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }    

    public function createUser($data) {
        try {
            // Check if username already exists
            $stmt = $this->conn->prepare("SELECT username FROM users WHERE username = :username");
            $stmt->bindParam(":username", $data['username']);
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                error_log("Username already exists: " . $data['username']);
                return false;
            }
    
            // Check if email already exists
            $stmt = $this->conn->prepare("SELECT email FROM users WHERE email = :email");
            $stmt->bindParam(":email", $data['email']);
            $stmt->execute();
    
            if ($stmt->rowCount() > 0) {
                error_log("Email already exists: " . $data['email']);
                return false;
            }
    
            // Insert the new user
            $query = "INSERT INTO users (username, password, first_name, last_name, email, role, max_books) 
                      VALUES (:username, :password, :first_name, :last_name, :email, :role, :max_books)";
            $stmt = $this->conn->prepare($query);
    
            // Bind parameters
            $stmt->bindParam(":username", $data['username']);
            $stmt->bindParam(":password", $data['password']); // Plain text password
            $stmt->bindParam(":first_name", $data['first_name']);
            $stmt->bindParam(":last_name", $data['last_name']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":role", $data['role']);
            $stmt->bindParam(":max_books", $data['max_books']);
    
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Create user error: " . $e->getMessage());
            return false;
        }
    }    

    public function getUsers() {
        try {
            $query = "SELECT user_id, username, first_name, last_name, email, role, max_books 
                     FROM users ORDER BY user_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get users error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserById($userId) {
        try {
            $query = "SELECT user_id, username, first_name, last_name, email, role, max_books 
                     FROM users WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    public function updateUser($userId, $data) {
        try {
            // Check if username already exists for other users
            $stmt = $this->conn->prepare("SELECT username FROM users WHERE username = :username AND user_id != :user_id");
            $stmt->bindParam(":username", $data['username']);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                error_log("Username already exists: " . $data['username']);
                return false;
            }

            // Check if email already exists for other users
            $stmt = $this->conn->prepare("SELECT email FROM users WHERE email = :email AND user_id != :user_id");
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                error_log("Email already exists: " . $data['email']);
                return false;
            }

            // Start building the update query
            $query = "UPDATE users SET 
                     username = :username,
                     first_name = :first_name,
                     last_name = :last_name,
                     email = :email,
                     role = :role,
                     max_books = :max_books";
            
            // Only include password in update if it's provided
            if (!empty($data['password'])) {
                $query .= ", password = :password";
            }
            
            $query .= " WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":username", $data['username']);
            $stmt->bindParam(":first_name", $data['first_name']);
            $stmt->bindParam(":last_name", $data['last_name']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":role", $data['role']);
            $stmt->bindParam(":max_books", $data['max_books']);
            $stmt->bindParam(":user_id", $userId);
            
            if (!empty($data['password'])) {
                $stmt->bindParam(":password", $data['password']); // Password should already be hashed
            }
            
            return $stmt->execute();
        } catch(PDOException $e) {
            error_log("Update user error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            // Check if user exists and get their role
            $stmt = $this->conn->prepare("SELECT role FROM users WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $userId);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("User not found for deletion: " . $userId);
                return false;
            }
    
            // Only check for last admin if the user being deleted is an admin
            if ($user['role'] === 'admin') {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result['admin_count'] <= 1) {
                    error_log("Cannot delete last admin user");
                    return false;
                }
            }
    
            // Delete the user
            $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindParam(":user_id", $userId);
            return $stmt->execute();
            
        } catch(PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
}