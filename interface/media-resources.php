<?php
require_once '../controller/MediaResourceController.php';
require_once '../controller/Session.php';

Session::start();
Session::requireAdmin();

$mediaController = new MediaResourceController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['delete_media'])) {
        $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
        if ($mediaController->deleteMediaResource($resourceId)) {
            Session::setFlash('success', 'Media resource deleted successfully');
            header("Location: media-resources.php");
            exit();
        } else {
            Session::setFlash('error', 'Error deleting media resource');
            header("Location: media-resources.php");
            exit();
        }
    }
    // Handle Create/Update
    else {
        // Sanitize and validate input
        $mediaData = [
            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
            'format' => filter_input(INPUT_POST, 'format', FILTER_SANITIZE_STRING),
            'runtime' => filter_input(INPUT_POST, 'runtime', FILTER_SANITIZE_NUMBER_INT),
            'media_type' => filter_input(INPUT_POST, 'media_type', FILTER_SANITIZE_STRING),
            'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
        ];

        // Generate Accession Number if not provided
        $mediaData['accession_number'] = filter_input(INPUT_POST, 'accession_number', FILTER_SANITIZE_STRING);
        if (empty($mediaData['accession_number'])) {
            $mediaData['accession_number'] = $mediaController->generateAccessionNumber();
        }

        // Update or Create Media Resource
        if (isset($_POST['resource_id']) && !empty($_POST['resource_id'])) {
            $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
            if ($mediaController->updateMediaResource($resourceId, $mediaData)) {
                Session::setFlash('success', 'Media resource updated successfully');
                header("Location: media-resources.php");
                exit();
            } else {
                Session::setFlash('error', 'Error updating media resource');
                header("Location: media-resources.php");
                exit();
            }
        } else {
            if ($mediaController->createMediaResource($mediaData)) {
                Session::setFlash('success', 'Media resource created successfully');
                header("Location: media-resources.php");
                exit();
            } else {
                Session::setFlash('error', 'Error creating media resource');
                header("Location: media-resources.php");
                exit();
            }
        }
    }
}

// Get media resources for display
$mediaResources = $mediaController->getMediaResources();

// Get flash messages
$success_message = Session::getFlash('success');
$error_message = Session::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Resources Management - Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.7.2/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebarModal.php'; ?>
        
        <div class="main-content flex-grow-1 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Media Resources Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mediaModal">
                    <i class="bi bi-plus-lg"></i> Add New Media Resource
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
                                    <th>Format</th>
                                    <th>Runtime</th>
                                    <th>Media Type</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mediaResources as $media): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($media['accession_number']); ?></td>
                                    <td><?php echo htmlspecialchars($media['title']); ?></td>
                                    <td><?php echo htmlspecialchars($media['format']); ?></td>
                                    <td><?php echo htmlspecialchars($media['runtime']); ?> min</td>
                                    <td><?php echo htmlspecialchars($media['media_type']); ?></td>
                                    <td><?php echo htmlspecialchars($media['category']); ?></td>
                                    <td><?php echo htmlspecialchars($media['status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-media" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#mediaModal"
                                                data-media='<?php echo htmlspecialchars(json_encode($media)); ?>'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this media resource?');">
                                            <input type="hidden" name="resource_id" value="<?php echo $media['resource_id']; ?>">
                                            <input type="hidden" name="delete_media" value="1">
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

            <div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="mediaModalLabel">Add/Edit Media Resource</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="mediaForm" method="POST">
                                <input type="hidden" name="resource_id" id="resourceId">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="format" class="form-label">Format</label>
                                    <select class="form-select" id="format" name="format" required>
                                        <option value="">Select Format</option>
                                        <option value="DVD">DVD</option>
                                        <option value="CD">CD</option>
                                        <option value="Blu-ray">Blu-ray</option>
                                        <option value="Digital">Digital</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="runtime" class="form-label">Runtime (minutes)</label>
                                    <input type="number" class="form-control" id="runtime" name="runtime" required min="1">
                                </div>
                                <div class="mb-3">
                                    <label for="media_type" class="form-label">Media Type</label>
                                    <select class="form-select" id="media_type" name="media_type" required>
                                        <option value="">Select Media Type</option>
                                        <option value="Video">Video</option>
                                        <option value="Audio">Audio</option>
                                        <option value="Interactive">Interactive</option>
                                        <option value="Educational">Educational</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Academic">Academic</option>
                                        <option value="Documentary">Documentary</option>
                                        <option value="Entertainment">Entertainment</option>
                                        <option value="Reference">Reference</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="accession_number" class="form-label">Accession Number</label>
                                    <input type="text" class="form-control" id="accession_number" name="accession_number" placeholder="Leave blank for auto-generation" readonly>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Media Resource</button>
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
            // Handle edit button clicks
            const editButtons = document.querySelectorAll('.edit-media');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const mediaData = JSON.parse(this.dataset.media);
                    
                    // Populate form fields
                    document.getElementById('resourceId').value = mediaData.resource_id;
                    document.getElementById('title').value = mediaData.title;
                    document.getElementById('format').value = mediaData.format;
                    document.getElementById('runtime').value = mediaData.runtime;
                    document.getElementById('media_type').value = mediaData.media_type;
                    document.getElementById('category').value = mediaData.category;
                    document.getElementById('accession_number').value = mediaData.accession_number;
                    
                    // Update modal title
                    document.getElementById('mediaModalLabel').textContent = 'Edit Media Resource';
                });
            });

            // Reset form when modal is closed
            const mediaModal = document.getElementById('mediaModal');
            mediaModal.addEventListener('hidden.bs.modal', function() {
                document.getElementById('mediaForm').reset();
                document.getElementById('resourceId').value = '';
                document.getElementById('mediaModalLabel').textContent = 'Add New Media Resource';
            });
        });
    </script>
</body>
</html>