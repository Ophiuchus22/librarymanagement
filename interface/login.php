<?php
require_once '../controller/UserController.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userController = new UserController();
    if ($userController->login($_POST['username'], $_POST['password'])) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a202c;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .card {
            background-color: #2d3748;
            border: none;
            border-radius: 1rem;
        }

        .card-body {
            padding: 2.5rem;
        }

        h3 {
            color: #edf2f7;
            font-weight: bold;
            margin-bottom: 2rem;
        }

        .form-label {
            color: #a0aec0;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .form-control {
            background-color: #4a5568;
            border: none;
            color: #edf2f7;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            border-radius: 0.5rem;
        }

        .form-control:focus {
            background-color: #4a5568;
            border: 1px solid #63b3ed;
            color: #edf2f7;
            box-shadow: none;
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        .btn-primary {
            background-color: #4299e1;
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #3182ce;
        }

        .alert-danger {
            background-color: #742a2a;
            border: none;
            color: #feb2b2;
            border-radius: 0.5rem;
        }

        .brand-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .brand-icon {
            font-size: 2rem;
            color: #4299e1;
            margin-right: 0.75rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            z-index: 10;
        }

        .input-group .form-control {
            padding-left: 2.75rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <div class="brand-section">
                            <i class="bi bi-book brand-icon"></i>
                            <h3 class="mb-0">Library Management</h3>
                        </div>
                        
                        <?php if (isset($error_message)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="input-group">
                                <i class="bi bi-person"></i>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                            </div>
                            
                            <div class="input-group">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>