<?php
require_once '../database/Database.php';
require_once 'Session.php';

class BookCatalogController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Retrieve books with their resource details
     * 
     * @param string $search Optional search term to filter books
     * @param string $category Optional category to filter books
     * @return array List of books with their details
     */
    public function getBookCatalog($search = '', $category = '') {
        try {
            // Build the base query joining library_resources and books tables
            $query = "SELECT lr.resource_id, lr.title, lr.accession_number, 
                             lr.category, lr.status, 
                             b.author, b.isbn, b.publisher, b.edition, b.publication_date
                      FROM library_resources lr
                      JOIN books b ON lr.resource_id = b.resource_id
                      WHERE 1=1"; // Always true to allow easy condition adding

            // Add search conditions if search term is provided
            $conditions = [];
            $params = [];

            if (!empty($search)) {
                $searchTerm = "%{$search}%";
                $conditions[] = "(lr.title LIKE :search OR 
                                  b.author LIKE :search OR 
                                  b.isbn LIKE :search OR 
                                  b.publisher LIKE :search)";
                $params[':search'] = $searchTerm;
            }

            // Add category condition if provided
            if (!empty($category)) {
                $conditions[] = "lr.category = :category";
                $params[':category'] = $category;
            }

            // Combine conditions
            if (!empty($conditions)) {
                $query .= " AND " . implode(' AND ', $conditions);
            }

            // Prepare and execute the query
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Book catalog retrieval error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get unique book categories
     * 
     * @return array List of unique book categories
     */
    public function getBookCategories() {
        try {
            $query = "SELECT DISTINCT category FROM library_resources 
                      WHERE category LIKE '%Book%' OR category LIKE '%book%'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Book categories retrieval error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check book availability for borrowing
     * 
     * @param int $resourceId Resource ID of the book
     * @return bool Whether the book is available for borrowing
     */
    public function isBookAvailable($resourceId) {
        try {
            $query = "SELECT status FROM library_resources 
                      WHERE resource_id = :resource_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':resource_id', $resourceId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result && $result['status'] === 'available';
        } catch (PDOException $e) {
            error_log("Book availability check error: " . $e->getMessage());
            return false;
        }
    }
}