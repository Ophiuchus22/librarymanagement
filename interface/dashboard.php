<?php
require_once '../controller/UserController.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost:3307", "root", "", "library_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get total books
$total_books = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc()['total'];

// Get available books
$available_books = $conn->query("
    SELECT COUNT(*) as total 
    FROM library_resources 
    WHERE status = 'available' 
    AND resource_id IN (SELECT resource_id FROM books)
")->fetch_assoc()['total'];

// Get total users
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.7.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .main-content {
            margin-left: 0px;
            padding: 20px;
        }

        .dashboard-stats {
            margin-top: 2rem;
        }

        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card .stat-icon {
            font-size: 3rem;
            opacity: 0.2;
            position: absolute;
            right: 1rem;
            bottom: 1rem;
            z-index: 0;
        }

        .stat-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .card-blue {
            background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);
        }

        .card-green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .card-orange {
            background: linear-gradient(135deg, #f46b45 0%, #eea849 100%);
        }

        .dashboard-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <?php include 'sidebarModal.php'; ?>
        
        <div class="main-content flex-grow-1">
            <h2 class="dashboard-title">Library Dashboard</h2>
            
            <div class="dashboard-stats">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card stat-card card-blue text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Books</h5>
                                <h3 class="stat-value"><?php echo number_format($total_books); ?></h3>
                                <i class="bi bi-book stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card card-green text-white">
                            <div class="card-body">
                                <h5 class="card-title">Available Books</h5>
                                <h3 class="stat-value"><?php echo number_format($available_books); ?></h3>
                                <i class="bi bi-journal-check stat-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card stat-card card-orange text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h3 class="stat-value"><?php echo number_format($total_users); ?></h3>
                                <i class="bi bi-people stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth loading effect
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>