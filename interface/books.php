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
                                        <label class="form-label">Category</label>
                                        <div class="dropdown category-dropdown">
                                            <button class="form-control text-start dropdown-toggle" type="button" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Select Category
                                            </button>
                                            <ul class="dropdown-menu category-menu w-100" aria-labelledby="categoryDropdown">
                                                <li class="dropend">
                                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                                                        Fiction
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="dropdown-menu sub-category-menu">
                                                        <li><a class="dropdown-item" href="#" data-category="Literary Fiction">Literary Fiction</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Historical Fiction">Historical Fiction</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Romance">Romance</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Science Fiction">Science Fiction</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Fantasy">Fantasy</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Mystery">Mystery</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Thriller">Thriller</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Horror">Horror</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Crime/Detective">Crime/Detective</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Adventure">Adventure</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Contemporary Fiction">Contemporary Fiction</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Magical Realism">Magical Realism</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Young Adult (YA) Fiction">Young Adult (YA) Fiction</a></li>
                                                    </ul>
                                                </li>
                                                <li class="dropend">
                                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                                                        Non-Fiction
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="dropdown-menu sub-category-menu">
                                                        <li><a class="dropdown-item" href="#" data-category="Biography/Autobiography">Biography/Autobiography</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Memoir">Memoir</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="History">History</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Science">Science</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Philosophy">Philosophy</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Psychology">Psychology</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Self-Help">Self-Help</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Business/Economics">Business/Economics</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Political Science">Political Science</a></li>
                                                    </ul>
                                                </li>
                                                <li class="dropend">
                                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                                                        Arts and Humanities
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="dropdown-menu sub-category-menu">
                                                        <li><a class="dropdown-item" href="#" data-category="Art">Art</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Music">Music</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Cinema">Cinema</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Theater">Theater</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Poetry">Poetry</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Literary Criticism">Literary Criticism</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Cultural Studies">Cultural Studies</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Architecture">Architecture</a></li>
                                                    </ul>
                                                </li>
                                                <li class="dropend">
                                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                                                        Academic/Educational
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="dropdown-menu sub-category-menu">
                                                        <li><a class="dropdown-item" href="#" data-category="Textbooks">Textbooks</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Academic Research">Academic Research</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Reference Books">Reference Books</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Educational Materials">Educational Materials</a></li>
                                                    </ul>
                                                </li>
                                                <li class="dropend">
                                                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-bs-toggle="dropdown">
                                                        Children's and Special
                                                        <i class="bi bi-chevron-right"></i>
                                                    </a>
                                                    <ul class="dropdown-menu sub-category-menu">
                                                        <li><a class="dropdown-item" href="#" data-category="Children's Books">Children's Books</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Picture Books">Picture Books</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Chapter Books">Chapter Books</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Early Readers">Early Readers</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Comics/Graphic Novels">Comics/Graphic Novels</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Cookbooks">Cookbooks</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Sports">Sports</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Health and Wellness">Health and Wellness</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Photography">Photography</a></li>
                                                        <li><a class="dropdown-item" href="#" data-category="Crafts and Hobbies">Crafts and Hobbies</a></li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </div>
                                        <input type="hidden" name="category" id="category" required>
                                    </div>
                                    <div class="mb-3" hidden>
                                        <label for="accession_number" class="form-label">Accession Number</label>
                                        <input type="text" class="form-control" id="accession_number" name="accession_number" readonly>
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
                                    <!-- Replace the existing category input with this dropdown -->
                                    
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
        document.addEventListener('DOMContentLoaded', function () {
            const categoryDropdown = document.getElementById('categoryDropdown');
            const categoryInput = document.getElementById('category');

            // Ensure dropdown width adjusts to its content
            const adjustDropdownWidth = () => {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.minWidth = `${menu.parentElement.offsetWidth}px`;
                });
            };
            adjustDropdownWidth();
            window.addEventListener('resize', adjustDropdownWidth);

            // Main category dropdown click handler
            categoryDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
                const dropdownMenu = this.nextElementSibling;
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                } else {
                    dropdownMenu.classList.add('show');
                }
            });

            // Handle category selection
            document.querySelectorAll('.dropdown-menu .dropdown-item[data-category]').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const selectedCategory = this.dataset.category;
                    categoryDropdown.textContent = selectedCategory;
                    categoryInput.value = selectedCategory;

                    // Hide all dropdown menus
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.remove('show');
                    });
                });
            });

            // Handle nested dropdowns
            document.querySelectorAll('.dropend').forEach(dropend => {
                const mainItem = dropend.querySelector('.dropdown-item');
                const submenu = dropend.querySelector('.dropdown-menu');

                // Show submenu on hover
                mainItem.addEventListener('mouseenter', function (e) {
                    e.stopPropagation();
                    // Hide all other submenus
                    document.querySelectorAll('.dropend .dropdown-menu').forEach(menu => {
                        if (menu !== submenu) {
                            menu.classList.remove('show');
                        }
                    });
                    submenu.classList.add('show');
                });

                // Handle mouse leaving the dropdown area
                dropend.addEventListener('mouseleave', function () {
                    submenu.classList.remove('show');
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bookModal = document.getElementById('bookModal');
            const editBookButtons = document.querySelectorAll('.edit-book');
            const categoryDropdown = document.getElementById('categoryDropdown');
            const categoryInput = document.getElementById('category');

            // Handle edit book functionality
            editBookButtons.forEach(button => {
                button.addEventListener('click', function () {
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

                    // Update category dropdown text
                    if (book.category) {
                        categoryDropdown.textContent = book.category;
                        categoryInput.value = book.category;
                    } else {
                        categoryDropdown.textContent = 'Select Category';
                        categoryInput.value = '';
                    }
                });
            });

            // Reset form when adding new book
            const addNewBookButton = document.querySelector('button[data-bs-target="#bookModal"]');
            addNewBookButton.addEventListener('click', function () {
                document.getElementById('resource_id').value = '';
                document.getElementById('title').value = '';
                document.getElementById('author').value = '';
                document.getElementById('isbn').value = '';
                document.getElementById('publisher').value = '';
                document.getElementById('edition').value = '';
                document.getElementById('publication_date').value = '';
                document.getElementById('category').value = '';
                document.getElementById('accession_number').value = '';
                categoryDropdown.textContent = 'Select Category';
            });
        });
    </script>

<style>
    /* Category Dropdown Styles */
    .category-dropdown .category-menu {
        border: 1px solid #ccc;
        border-radius: 0.5rem;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 0.5rem;
    }

    .category-dropdown .category-item {
        padding: 0.5rem 1rem;
        font-size: 1rem;
        color: #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .category-dropdown .category-item:hover {
        background-color: #f8f9fa;
        color: #000;
    }

    /* Adjust Submenus */
    .category-dropdown .category-menu .dropend .category-menu {
        top: 0;
        left: 100%;
        margin-left: 0.2rem;
    }

    /* Dropdown Button Styles */
    .category-dropdown .category-toggle {
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-size: 1rem;
        text-align: left;
    }

    .category-dropdown .category-toggle:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    /* Add Spacing Around Category Dropdown */
    .category-dropdown {
        margin-bottom: 1.5rem;
    }

    /* Responsiveness */
    @media (max-width: 768px) {
        .category-dropdown .category-item {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }

        .category-dropdown .category-menu {
            padding: 0.4rem;
        }

        .category-dropdown .category-toggle {
            padding: 0.6rem;
            font-size: 0.9rem;
        }
    }

    /* Sub-category Menu Styles */
    .category-dropdown .category-menu .sub-category-menu {
        background-color: #f1f1f1;
        border: 1px solid #ddd;
    }

    /* Custom caret for category dropdown */
    .category-dropdown .category-item i.bi {
        color: #6c757d;
        margin-left: 0.5rem;
    }

    .category-dropdown .category-item:hover i.bi {
        color: #495057;
    }
</style>


</body>
</html>