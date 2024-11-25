<?php
require_once '../database/Database.php';

class ResourceController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createResource($data, $type = 'book') {
        try {
            $this->conn->beginTransaction();

            // Insert into library_resources
            $query = "INSERT INTO library_resources (title, accession_number, category, status) 
                     VALUES (:title, :accession_number, :category, :status)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":title", $data['title']);
            $stmt->bindParam(":accession_number", $data['accession_number']);
            $stmt->bindParam(":category", $data['category']);
            $status = 'available';
            $stmt->bindParam(":status", $status);
            $stmt->execute();
            
            $resource_id = $this->conn->lastInsertId();

            // Insert into specific resource type table
            if ($type === 'book') {
                $query = "INSERT INTO books (resource_id, author, isbn, publisher, edition, publication_date) 
                         VALUES (:resource_id, :author, :isbn, :publisher, :edition, :publication_date)";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":resource_id", $resource_id);
                $stmt->bindParam(":author", $data['author']);
                $stmt->bindParam(":isbn", $data['isbn']);
                $stmt->bindParam(":publisher", $data['publisher']);
                $stmt->bindParam(":edition", $data['edition']);
                $stmt->bindParam(":publication_date", $data['publication_date']);
                $stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch(PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getResources($type = 'book') {
        try {
            $query = "SELECT lr.*, b.* 
                     FROM library_resources lr 
                     LEFT JOIN books b ON lr.resource_id = b.resource_id 
                     ORDER BY lr.resource_id DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }

    public function generateAccessionNumber($type = 'B') {
        $year = date('Y');
        $query = "SELECT MAX(accession_number) as max_number 
                 FROM library_resources 
                 WHERE accession_number LIKE :prefix";
        $prefix = $type . "-" . $year . "-";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':prefix', $prefix . '%');
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['max_number'] === null) {
                return $prefix . "001";
            }
            
            $current_number = intval(substr($result['max_number'], -3));
            $next_number = str_pad($current_number + 1, 3, '0', STR_PAD_LEFT);
            
            return $prefix . $next_number;
        } catch(PDOException $e) {
            return false;
        }
    }
}