<?php
require_once '../controller/Session.php';
require_once '../controller/BookCatalogController.php';

// Start session and check login
Session::start();
Session::requireLogin();

// Only allow students and faculty
if (!in_array($_SESSION['role'], ['student', 'faculty'])) {
    header("Location: bookcatalog.php");
    exit();
}

// Initialize BookCatalogController
$bookCatalogController = new BookCatalogController();

// Handle search and filtering
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Get book catalog and categories
$books = $bookCatalogController->getBookCatalog($search, $category);
$categories = $bookCatalogController->getBookCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Catalog - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .book-card {
            transition: transform 0.3s;
        }
        .book-card:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'sidebarModal.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block sidebar">
                <?php include 'sidebarModal.php'; ?>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Book Catalog</h1>
                </div>

                <!-- Search and Filter Section -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="get" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" 
                                   placeholder="Search books by title, author, ISBN..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            
                            <select name="category" class="form-select me-2" style="max-width: 200px;">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"
                                        <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Books Display Section -->
                <div class="row">
                    <?php if (empty($books)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                No books found matching your search criteria.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($books as $book): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card book-card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </h6>
                                        <p class="card-text">
                                            <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?><br>
                                            <strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?><br>
                                            <strong>Edition:</strong> <?php echo htmlspecialchars($book['edition'] ?? 'N/A'); ?><br>
                                            <strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?><br>
                                            <strong>Accession No:</strong> <?php echo htmlspecialchars($book['accession_number']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge <?php 
                                                echo $book['status'] === 'available' ? 'bg-success' : 'bg-danger'; 
                                            ?>">
                                                <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                            <?php if ($book['status'] === 'available'): ?>
                                                <a href="borrow-book.php?resource_id=<?php echo $book['resource_id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    Borrow Book
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>