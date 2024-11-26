<?php
require_once '../database/Database.php';
require_once 'Session.php';

class BookController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createBook($bookData) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // First, insert into library_resources
            $resourceQuery = "INSERT INTO library_resources 
                              (title, accession_number, category, status) 
                              VALUES (:title, :accession_number, :category, 'available')";
            $resourceStmt = $this->conn->prepare($resourceQuery);
            $resourceStmt->bindParam(":title", $bookData['title']);
            $resourceStmt->bindParam(":accession_number", $bookData['accession_number']);
            $resourceStmt->bindParam(":category", $bookData['category']);
            $resourceStmt->execute();

            // Get the last inserted resource_id
            $resourceId = $this->conn->lastInsertId();

            // Then, insert into books
            $bookQuery = "INSERT INTO books 
                          (resource_id, author, isbn, publisher, edition, publication_date) 
                          VALUES (:resource_id, :author, :isbn, :publisher, :edition, :publication_date)";
            $bookStmt = $this->conn->prepare($bookQuery);
            $bookStmt->bindParam(":resource_id", $resourceId);
            $bookStmt->bindParam(":author", $bookData['author']);
            $bookStmt->bindParam(":isbn", $bookData['isbn']);
            $bookStmt->bindParam(":publisher", $bookData['publisher']);
            $bookStmt->bindParam(":edition", $bookData['edition']);
            $bookStmt->bindParam(":publication_date", $bookData['publication_date']);
            $bookStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Create book error: " . $e->getMessage());
            return false;
        }
    }

    public function getBooks() {
        try {
            $query = "SELECT lr.resource_id, lr.title, lr.accession_number, lr.category, lr.status, 
                             b.author, b.isbn, b.publisher, b.edition, b.publication_date
                      FROM library_resources lr
                      JOIN books b ON lr.resource_id = b.resource_id
                      ORDER BY lr.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get books error: " . $e->getMessage());
            return [];
        }
    }

    public function getBookById($resourceId) {
        try {
            $query = "SELECT lr.resource_id, lr.title, lr.accession_number, lr.category, lr.status, 
                             b.author, b.isbn, b.publisher, b.edition, b.publication_date
                      FROM library_resources lr
                      JOIN books b ON lr.resource_id = b.resource_id
                      WHERE lr.resource_id = :resource_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":resource_id", $resourceId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get book by ID error: " . $e->getMessage());
            return null;
        }
    }

    public function updateBook($resourceId, $bookData) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Update library_resources
            $resourceQuery = "UPDATE library_resources 
                              SET title = :title, 
                                  accession_number = :accession_number, 
                                  category = :category 
                              WHERE resource_id = :resource_id";
            $resourceStmt = $this->conn->prepare($resourceQuery);
            $resourceStmt->bindParam(":title", $bookData['title']);
            $resourceStmt->bindParam(":accession_number", $bookData['accession_number']);
            $resourceStmt->bindParam(":category", $bookData['category']);
            $resourceStmt->bindParam(":resource_id", $resourceId);
            $resourceStmt->execute();

            // Update books
            $bookQuery = "UPDATE books 
                          SET author = :author, 
                              isbn = :isbn, 
                              publisher = :publisher, 
                              edition = :edition, 
                              publication_date = :publication_date
                          WHERE resource_id = :resource_id";
            $bookStmt = $this->conn->prepare($bookQuery);
            $bookStmt->bindParam(":author", $bookData['author']);
            $bookStmt->bindParam(":isbn", $bookData['isbn']);
            $bookStmt->bindParam(":publisher", $bookData['publisher']);
            $bookStmt->bindParam(":edition", $bookData['edition']);
            $bookStmt->bindParam(":publication_date", $bookData['publication_date']);
            $bookStmt->bindParam(":resource_id", $resourceId);
            $bookStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Update book error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBook($resourceId) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Delete from books first due to foreign key constraint
            $bookQuery = "DELETE FROM books WHERE resource_id = :resource_id";
            $bookStmt = $this->conn->prepare($bookQuery);
            $bookStmt->bindParam(":resource_id", $resourceId);
            $bookStmt->execute();

            // Then delete from library_resources
            $resourceQuery = "DELETE FROM library_resources WHERE resource_id = :resource_id";
            $resourceStmt = $this->conn->prepare($resourceQuery);
            $resourceStmt->bindParam(":resource_id", $resourceId);
            $resourceStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Delete book error: " . $e->getMessage());
            return false;
        }
    }

    // Generate unique Accession Number
    public function generateAccessionNumber($resourceType = 'B') {
        try {
            $currentYear = date('Y');
            $prefix = $resourceType . '-' . $currentYear . '-';
            
            $query = "SELECT MAX(accession_number) as last_number 
                      FROM library_resources 
                      WHERE accession_number LIKE :prefix";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(":prefix", $prefix . '%');
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['last_number']) {
                // Extract the last sequential number and increment
                $lastNumber = intval(substr($result['last_number'], -3));
                $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '001';
            }
            
            return $prefix . $newNumber;
        } catch (PDOException $e) {
            error_log("Generate Accession Number error: " . $e->getMessage());
            return null;
        }
    }
}