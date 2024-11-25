<?php
require_once '../database/Database.php';

class BorrowingController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createBorrowing($user_id, $resource_id, $due_date) {
        try {
            $this->conn->beginTransaction();

            // Check if resource is available
            $query = "SELECT status FROM library_resources WHERE resource_id = :resource_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            $stmt->execute();
            $resource = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resource['status'] !== 'available') {
                throw new Exception("Resource is not available");
            }

            // Create borrowing record
            $query = "INSERT INTO borrowings (user_id, resource_id, due_date) 
                     VALUES (:user_id, :resource_id, :due_date)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":resource_id", $resource_id);
            $stmt->bindParam(":due_date", $due_date);
            $stmt->execute();

            // Update resource status
            $query = "UPDATE library_resources 
                     SET status = 'borrowed' 
                     WHERE resource_id = :resource_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resource_id);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function returnResource($borrowing_id) {
        try {
            $this->conn->beginTransaction();

            // Get resource_id and check if overdue
            $query = "SELECT resource_id, due_date FROM borrowings WHERE borrowing_id = :borrowing_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":borrowing_id", $borrowing_id);
            $stmt->execute();
            $borrowing = $stmt->fetch(PDO::FETCH_ASSOC);

            $fine_amount = 0;
            if (strtotime($borrowing['due_date']) < time()) {
                $days_overdue = floor((time() - strtotime($borrowing['due_date'])) / (60 * 60 * 24));
                $fine_amount = $days_overdue * 1.00; // $1 per day
            }

            // Update borrowing record
            $query = "UPDATE borrowings 
                     SET return_date = NOW(), 
                         fine_amount = :fine_amount,
                         status = 'returned' 
                     WHERE borrowing_id = :borrowing_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":fine_amount", $fine_amount);
            $stmt->bindParam(":borrowing_id", $borrowing_id);
            $stmt->execute();

            // Update resource status
            $query = "UPDATE library_resources 
                     SET status = 'available' 
                     WHERE resource_id = :resource_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $borrowing['resource_id']);
            $stmt->execute();

            $this->conn->commit();
            return $fine_amount;
        } catch(Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getBorrowings($status = null) {
        try {
            $query = "SELECT b.*, u.username, u.first_name, u.last_name, 
                            lr.title, lr.accession_number
                     FROM borrowings b
                     JOIN users u ON b.user_id = u.user_id
                     JOIN library_resources lr ON b.resource_id = lr.resource_id";
            
            if ($status) {
                $query .= " WHERE b.status = :status";
            }
            
            $query .= " ORDER BY b.borrowing_id DESC";
            
            $stmt = $this->conn->prepare($query);
            if ($status) {
                $stmt->bindParam(":status", $status);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
}