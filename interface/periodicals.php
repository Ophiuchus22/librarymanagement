<?php
require_once '../controller/PeriodicalController.php';
require_once '../controller/Session.php';

Session::start();
Session::requireAdmin();

$periodicalController = new PeriodicalController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['delete_periodical'])) {
        $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
        if ($periodicalController->deletePeriodical($resourceId)) {
            Session::setFlash('success', 'Periodical deleted successfully');
            header("Location: periodicals.php");
            exit();
        } else {
            Session::setFlash('error', 'Error deleting periodical');
            header("Location: periodicals.php");
            exit();
        }
    }
    // Handle Create/Update
    else {
        // Sanitize and validate input
        $periodicalData = [
            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
            'issn' => filter_input(INPUT_POST, 'issn', FILTER_SANITIZE_STRING),
            'volume' => filter_input(INPUT_POST, 'volume', FILTER_SANITIZE_STRING),
            'issue' => filter_input(INPUT_POST, 'issue', FILTER_SANITIZE_STRING),
            'publication_date' => filter_input(INPUT_POST, 'publication_date', FILTER_SANITIZE_STRING),
            'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
        ];

        // Generate Accession Number if not provided
        $periodicalData['accession_number'] = filter_input(INPUT_POST, 'accession_number', FILTER_SANITIZE_STRING);
        if (empty($periodicalData['accession_number'])) {
            $periodicalData['accession_number'] = $periodicalController->generateAccessionNumber();
        }

        // Update or Create Periodical
        if (isset($_POST['resource_id']) && !empty($_POST['resource_id'])) {
            $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
            if ($periodicalController->updatePeriodical($resourceId, $periodicalData)) {
                Session::setFlash('success', 'Periodical updated successfully');
                header("Location: periodicals.php");
                exit();
            } else {
                Session::setFlash('error', 'Error updating periodical');
                header("Location: periodicals.php");
                exit();
            }
        } else {
            if ($periodicalController->createPeriodical($periodicalData)) {
                Session::setFlash('success', 'Periodical created successfully');
                header("Location: periodicals.php");
                exit();
            } else {
                Session::setFlash('error', 'Error creating periodical');
                header("Location: periodicals.php");
                exit();
            }
        }
    }
}

// Get periodicals for display
$periodicals = $periodicalController->getPeriodicals();

// Get flash messages
$success_message = Session::getFlash('success');
$error_message = Session::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodicals Management - Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.7.2/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebarModal.php'; ?>
        
        <div class="main-content flex-grow-1 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Periodicals Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#periodicalModal">
                    <i class="bi bi-plus-lg"></i> Add New Periodical
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Accession Number</th>
                                    <th>Title</th>
                                    <th>ISSN</th>
                                    <th>Volume</th>
                                    <th>Issue</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periodicals as $periodical): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($periodical['accession_number']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['title']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['issn']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['volume']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['issue']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['category']); ?></td>
                                    <td><?php echo htmlspecialchars($periodical['status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-periodical" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#periodicalModal"
                                                data-periodical='<?php echo htmlspecialchars(json_encode($periodical)); ?>'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this periodical?');">
                                            <input type="hidden" name="resource_id" value="<?php echo $periodical['resource_id']; ?>">
                                            <input type="hidden" name="delete_periodical" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="periodicalModal" tabindex="-1" aria-labelledby="periodicalModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="periodicalModalLabel">Add/Edit Periodical</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="periodicalForm" method="POST">
                                <input type="hidden" name="resource_id" id="resourceId">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="issn" class="form-label">ISSN</label>
                                    <input type="text" class="form-control" id="issn" name="issn" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="volume" class="form-label">Volume</label>
                                        <input type="text" class="form-control" id="volume" name="volume">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="issue" class="form-label">Issue</label>
                                        <input type="text" class="form-control" id="issue" name="issue">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="publication_date" class="form-label">Publication Date</label>
                                    <input type="date" class="form-control" id="publication_date" name="publication_date">
                                </div>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Academic Journal">Academic Journal</option>
                                        <option value="Magazine">Magazine</option>
                                        <option value="Newsletter">Newsletter</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="accession_number" class="form-label">Accession Number</label>
                                    <input type="text" class="form-control" id="accession_number" name="accession_number" readonly>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Periodical</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodicalModal = document.getElementById('periodicalModal');
            const periodicalForm = document.getElementById('periodicalForm');
            const editButtons = document.querySelectorAll('.edit-periodical');

            // Reset form when modal opens
            periodicalModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const periodicalData = button.getAttribute('data-periodical');

                if (periodicalData) {
                    const periodical = JSON.parse(periodicalData);
                    document.getElementById('resourceId').value = periodical.resource_id;
                    document.getElementById('title').value = periodical.title;
                    document.getElementById('issn').value = periodical.issn;
                    document.getElementById('volume').value = periodical.volume;
                    document.getElementById('issue').value = periodical.issue;
                    document.getElementById('publication_date').value = periodical.publication_date;
                    document.getElementById('category').value = periodical.category;
                    document.getElementById('accession_number').value = periodical.accession_number;
                } else {
                    periodicalForm.reset();
                    document.getElementById('resourceId').value = '';
                }
            });
        });
    </script>
</body>
</html>