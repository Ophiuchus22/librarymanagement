<?php
require_once '../controller/BookController.php';
require_once '../controller/Session.php';

Session::start();
Session::requireAdmin();

$bookController = new BookController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['delete_book'])) {
        $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
        if ($bookController->deleteBook($resourceId)) {
            Session::setFlash('success', 'Book deleted successfully');
            header("Location: books.php");
            exit();
        } else {
            Session::setFlash('error', 'Error deleting book');
            header("Location: books.php");
            exit();
        }
    }
    // Handle Create/Update
    else {
        // Sanitize and validate input
        $bookData = [
            'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
            'author' => filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING),
            'isbn' => filter_input(INPUT_POST, 'isbn', FILTER_SANITIZE_STRING),
            'publisher' => filter_input(INPUT_POST, 'publisher', FILTER_SANITIZE_STRING),
            'edition' => filter_input(INPUT_POST, 'edition', FILTER_SANITIZE_STRING),
            'publication_date' => filter_input(INPUT_POST, 'publication_date', FILTER_SANITIZE_STRING),
            'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
        ];

        // Generate Accession Number if not provided
        $bookData['accession_number'] = filter_input(INPUT_POST, 'accession_number', FILTER_SANITIZE_STRING);
        if (empty($bookData['accession_number'])) {
            $bookData['accession_number'] = $bookController->generateAccessionNumber();
        }

        // Update or Create Book
        if (isset($_POST['resource_id']) && !empty($_POST['resource_id'])) {
            $resourceId = filter_input(INPUT_POST, 'resource_id', FILTER_SANITIZE_NUMBER_INT);
            if ($bookController->updateBook($resourceId, $bookData)) {
                Session::setFlash('success', 'Book updated successfully');
                header("Location: books.php");
                exit();
            } else {
                Session::setFlash('error', 'Error updating book');
                header("Location: books.php");
                exit();
            }
        } else {
            if ($bookController->createBook($bookData)) {
                Session::setFlash('success', 'Book created successfully');
                header("Location: books.php");
                exit();
            } else {
                Session::setFlash('error', 'Error creating book');
                header("Location: books.php");
                exit();
            }
        }
    }
}

// Get books for display
$books = $bookController->getBooks();

// Get flash messages
$success_message = Session::getFlash('success');
$error_message = Session::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management - Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.7.2/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebarModal.php'; ?>
        
        <div class="main-content flex-grow-1 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Book Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bookModal">
                    <i class="bi bi-plus-lg"></i> Add New Book
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
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['accession_number']); ?></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><?php echo htmlspecialchars($book['category']); ?></td>
                                    <td><?php echo htmlspecialchars($book['status']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-book" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#bookModal"
                                                data-book='<?php echo htmlspecialchars(json_encode($book)); ?>'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                            <input type="hidden" name="resource_id" value="<?php echo $book['resource_id']; ?>">
                                            <input type="hidden" name="delete_book" value="1">
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

            <!-- Book Modal (Create/Edit) -->
            <div class="modal fade" id="bookModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Book Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="resource_id" id="resource_id">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Title</label>
                                        <input type="text" class="form-control" name="title" id="title" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Author</label>
                                        <input type="text" class="form-control" name="author" id="author" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" class="form-control" name="isbn" id="isbn">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Accession Number</label>
                                        <input type="text" class="form-control" name="accession_number" id="accession_number">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publisher</label>
                                        <input type="text" class="form-control" name="publisher" id="publisher">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Edition</label>
                                        <input type="text" class="form-control" name="edition" id="edition">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Publication Date</label>
                                        <input type="date" class="form-control" name="publication_date" id="publication_date">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <input type="text" class="form-control" name="category" id="category" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Book</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookModal = document.getElementById('bookModal');
            const editBookButtons = document.querySelectorAll('.edit-book');

            editBookButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const book = JSON.parse(this.getAttribute('data-book'));
                    
                    // Populate modal fields
                    document.getElementById('resource_id').value = book.resource_id;
                    document.getElementById('title').value = book.title;
                    document.getElementById('author').value = book.author;
                    document.getElementById('isbn').value = book.isbn;
                    document.getElementById('publisher').value = book.publisher;
                    document.getElementById('edition').value = book.edition;
                    document.getElementById('publication_date').value = book.publication_date;
                    document.getElementById('category').value = book.category;
                    document.getElementById('accession_number').value = book.accession_number;
                });
            });
        });
    </script>
</body>
</html>