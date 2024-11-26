<?php
require_once '../database/Database.php';
require_once 'Session.php';

class PeriodicalController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createPeriodical($periodicalData) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // First, insert into library_resources
            $resourceQuery = "INSERT INTO library_resources 
                              (title, accession_number, category, status) 
                              VALUES (:title, :accession_number, :category, 'available')";
            $resourceStmt = $this->conn->prepare($resourceQuery);
            $resourceStmt->bindParam(":title", $periodicalData['title']);
            $resourceStmt->bindParam(":accession_number", $periodicalData['accession_number']);
            $resourceStmt->bindParam(":category", $periodicalData['category']);
            $resourceStmt->execute();

            // Get the last inserted resource_id
            $resourceId = $this->conn->lastInsertId();

            // Then, insert into periodicals
            $periodicalQuery = "INSERT INTO periodicals 
                                (resource_id, issn, volume, issue, publication_date) 
                                VALUES (:resource_id, :issn, :volume, :issue, :publication_date)";
            $periodicalStmt = $this->conn->prepare($periodicalQuery);
            $periodicalStmt->bindParam(":resource_id", $resourceId);
            $periodicalStmt->bindParam(":issn", $periodicalData['issn']);
            $periodicalStmt->bindParam(":volume", $periodicalData['volume']);
            $periodicalStmt->bindParam(":issue", $periodicalData['issue']);
            $periodicalStmt->bindParam(":publication_date", $periodicalData['publication_date']);
            $periodicalStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Create periodical error: " . $e->getMessage());
            return false;
        }
    }

    public function getPeriodicals() {
        try {
            $query = "SELECT lr.resource_id, lr.title, lr.accession_number, lr.category, lr.status, 
                             p.issn, p.volume, p.issue, p.publication_date
                      FROM library_resources lr
                      JOIN periodicals p ON lr.resource_id = p.resource_id
                      ORDER BY lr.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get periodicals error: " . $e->getMessage());
            return [];
        }
    }

    public function updatePeriodical($resourceId, $periodicalData) {
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
            $resourceStmt->bindParam(":title", $periodicalData['title']);
            $resourceStmt->bindParam(":accession_number", $periodicalData['accession_number']);
            $resourceStmt->bindParam(":category", $periodicalData['category']);
            $resourceStmt->bindParam(":resource_id", $resourceId);
            $resourceStmt->execute();

            // Update periodicals
            $periodicalQuery = "UPDATE periodicals 
                                SET issn = :issn, 
                                    volume = :volume, 
                                    issue = :issue, 
                                    publication_date = :publication_date
                                WHERE resource_id = :resource_id";
            $periodicalStmt = $this->conn->prepare($periodicalQuery);
            $periodicalStmt->bindParam(":issn", $periodicalData['issn']);
            $periodicalStmt->bindParam(":volume", $periodicalData['volume']);
            $periodicalStmt->bindParam(":issue", $periodicalData['issue']);
            $periodicalStmt->bindParam(":publication_date", $periodicalData['publication_date']);
            $periodicalStmt->bindParam(":resource_id", $resourceId);
            $periodicalStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Update periodical error: " . $e->getMessage());
            return false;
        }
    }

    public function deletePeriodical($resourceId) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Delete from periodicals first due to foreign key constraint
            $periodicalQuery = "DELETE FROM periodicals WHERE resource_id = :resource_id";
            $periodicalStmt = $this->conn->prepare($periodicalQuery);
            $periodicalStmt->bindParam(":resource_id", $resourceId);
            $periodicalStmt->execute();

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
            error_log("Delete periodical error: " . $e->getMessage());
            return false;
        }
    }

    // Generate unique Accession Number
    public function generateAccessionNumber($resourceType = 'P') {
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