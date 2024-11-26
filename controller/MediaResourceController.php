<?php
require_once '../database/Database.php';
require_once 'Session.php';

class MediaResourceController {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createMediaResource($mediaData) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // First, insert into library_resources
            $resourceQuery = "INSERT INTO library_resources 
                            (title, accession_number, category, status) 
                            VALUES (:title, :accession_number, :category, 'available')";
            $resourceStmt = $this->conn->prepare($resourceQuery);
            $resourceStmt->bindParam(":title", $mediaData['title']);
            $resourceStmt->bindParam(":accession_number", $mediaData['accession_number']);
            $resourceStmt->bindParam(":category", $mediaData['category']);
            $resourceStmt->execute();

            // Get the last inserted resource_id
            $resourceId = $this->conn->lastInsertId();

            // Then, insert into media_resources
            $mediaQuery = "INSERT INTO media_resources 
                          (resource_id, format, runtime, media_type) 
                          VALUES (:resource_id, :format, :runtime, :media_type)";
            $mediaStmt = $this->conn->prepare($mediaQuery);
            $mediaStmt->bindParam(":resource_id", $resourceId);
            $mediaStmt->bindParam(":format", $mediaData['format']);
            $mediaStmt->bindParam(":runtime", $mediaData['runtime']);
            $mediaStmt->bindParam(":media_type", $mediaData['media_type']);
            $mediaStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Create media resource error: " . $e->getMessage());
            return false;
        }
    }

    public function getMediaResources() {
        try {
            $query = "SELECT lr.resource_id, lr.title, lr.accession_number, lr.category, lr.status,
                             mr.format, mr.runtime, mr.media_type
                      FROM library_resources lr
                      JOIN media_resources mr ON lr.resource_id = mr.resource_id
                      ORDER BY lr.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get media resources error: " . $e->getMessage());
            return [];
        }
    }

    public function updateMediaResource($resourceId, $mediaData) {
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
            $resourceStmt->bindParam(":title", $mediaData['title']);
            $resourceStmt->bindParam(":accession_number", $mediaData['accession_number']);
            $resourceStmt->bindParam(":category", $mediaData['category']);
            $resourceStmt->bindParam(":resource_id", $resourceId);
            $resourceStmt->execute();

            // Update media_resources
            $mediaQuery = "UPDATE media_resources 
                          SET format = :format,
                              runtime = :runtime,
                              media_type = :media_type
                          WHERE resource_id = :resource_id";
            $mediaStmt = $this->conn->prepare($mediaQuery);
            $mediaStmt->bindParam(":format", $mediaData['format']);
            $mediaStmt->bindParam(":runtime", $mediaData['runtime']);
            $mediaStmt->bindParam(":media_type", $mediaData['media_type']);
            $mediaStmt->bindParam(":resource_id", $resourceId);
            $mediaStmt->execute();

            // Commit transaction
            $this->conn->commit();

            return true;
        } catch (PDOException $e) {
            // Rollback transaction
            $this->conn->rollBack();
            error_log("Update media resource error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMediaResource($resourceId) {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Delete from media_resources first due to foreign key constraint
            $mediaQuery = "DELETE FROM media_resources WHERE resource_id = :resource_id";
            $mediaStmt = $this->conn->prepare($mediaQuery);
            $mediaStmt->bindParam(":resource_id", $resourceId);
            $mediaStmt->execute();

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
            error_log("Delete media resource error: " . $e->getMessage());
            return false;
        }
    }

    // Generate unique Accession Number
    public function generateAccessionNumber($resourceType = 'R') {
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