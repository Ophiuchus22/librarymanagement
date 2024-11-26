<?php
require_once '../controller/UserController.php';
require_once '../controller/Session.php';

Session::start();
Session::requireAdmin();

$userController = new UserController();
$users = $userController->getUsers();

// Define role configurations
$roleConfig = [
    'admin' => ['max_books' => 10],
    'faculty' => ['max_books' => 5],
    'staff' => ['max_books' => 4],
    'student' => ['max_books' => 3]
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Delete
    if (isset($_POST['delete_user'])) {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        if ($userController->deleteUser($userId)) {
            Session::setFlash('success', 'User deleted successfully');
            header("Location: users.php");
            exit();
        } else {
            Session::setFlash('error', 'Error deleting user');
            header("Location: users.php");
            exit();
        }
    }
    // Handle Create/Update
    else {
        // Sanitize input
        $userData = [
            'username' => filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING),
            'first_name' => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING),
            'last_name' => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING),
            'email' => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
            'role' => filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING),
        ];

        // Set max_books based on role
        $userData['max_books'] = $roleConfig[$userData['role']]['max_books'] ?? 3;

        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Invalid email format');
            header("Location: users.php");
            exit();
        } else {
            // Handle password
            if (!empty($_POST['password'])) {
                $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
                // Update existing user
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
                if ($userController->updateUser($userId, $userData)) {
                    Session::setFlash('success', 'User updated successfully');
                    header("Location: users.php");
                    exit();
                } else {
                    Session::setFlash('error', 'Error updating user');
                    header("Location: users.php");
                    exit();
                }
            } else {
                // Create new user
                if (empty($_POST['password'])) {
                    Session::setFlash('error', 'Password is required for new users');
                    header("Location: users.php");
                    exit();
                } else if ($userController->createUser($userData)) {
                    Session::setFlash('success', 'User created successfully');
                    header("Location: users.php");
                    exit();
                } else {
                    Session::setFlash('error', 'Error creating user');
                    header("Location: users.php");
                    exit();
                }
            }
        }
    }
}

// Get flash messages
$success_message = Session::getFlash('success');
$error_message = Session::getFlash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.7.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 0px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebarModal.php'; ?>
        
        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center">
                <h2>User Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="bi bi-plus-lg"></i> Add New User
                </button>
            </div>
            <hr>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
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
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Max Books</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td><?php echo htmlspecialchars($user['max_books']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-user" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#userModal"
                                                data-user='<?php echo htmlspecialchars(json_encode($user)); ?>'>
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <?php if ($user['role'] !== 'admin' || count($users) > 1): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="delete_user" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-info view-user">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal (for both Add and Edit) -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="userForm" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="username" required
                                   pattern="[a-zA-Z0-9._-]{3,}" title="Username must be at least 3 characters and can only contain letters, numbers, dots, underscores, and hyphens">
                            <div class="invalid-feedback">
                                Please provide a valid username.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="password" minlength="6">
                            <small class="text-muted">Minimum 6 characters. Leave empty to keep current password when editing</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="admin">Admin</option>
                                <option value="faculty">Faculty</option>
                                <option value="staff">Staff</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alerts after 3 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 3000);
        });

        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Clear form when adding new user
        document.querySelector('[data-bs-target="#userModal"]').addEventListener('click', function() {
            const form = document.getElementById('userForm');
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('user_id').value = '';
            document.getElementById('password').required = true;
            document.querySelector('.modal-title').textContent = 'Add New User';
        });

        // Fill form when editing user
        document.querySelectorAll('.edit-user').forEach(button => {
            button.addEventListener('click', function() {
                const form = document.getElementById('userForm');
                form.classList.remove('was-validated');
                document.getElementById('password').required = false;
                
                const user = JSON.parse(this.dataset.user);
                document.getElementById('user_id').value = user.user_id;
                document.getElementById('username').value = user.username;
                document.getElementById('password').value = ''; // Clear password field
                document.getElementById('first_name').value = user.first_name;
                document.getElementById('last_name').value = user.last_name;
                document.getElementById('email').value = user.email;
                document.getElementById('role').value = user.role;
                document.querySelector('.modal-title').textContent = 'Edit User';
            });
        });

        // Confirm delete
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.onsubmit = function(e) {
                return confirm('Are you sure you want to delete this user?');
            };
        });
    });
    </script>
</body>
</html>