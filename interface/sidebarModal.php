<?php
require_once '../controller/Session.php';
Session::start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<div class="sidebar bg-dark shadow-sm" style="min-height: 100vh; width: 250px;">
    <div class="p-4">
        <!-- Logo and Title Section -->
        <div class="d-flex align-items-center mb-4">
            <i class="bi bi-book fs-4 text-primary"></i>
            <h4 class="text-white ms-2 fw-bold">Library</h4>
        </div>
        
        <!-- User Profile Section -->
        <div class="user-profile mb-4 p-3 bg-secondary rounded">
            <small class="text-light">Hello,</small>
            <div class="fw-bold text-white"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
            <span class="badge bg-primary mt-1"><?php echo ucfirst($_SESSION['role']); ?></span>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="nav-menu">
            <ul class="nav flex-column gap-2">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 text-light rounded py-2 px-3" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 text-light rounded py-2 px-3" href="users.php">
                        <i class="bi bi-people"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 text-light rounded py-2 px-3" href="resources.php">
                        <i class="bi bi-book"></i>
                        <span>Resources</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-2 text-light rounded py-2 px-3" href="borrowings.php">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>Borrowings</span>
                    </a>
                </li>
                
                <li class="nav-item mt-4">
                    <a class="nav-link d-flex align-items-center gap-2 text-danger rounded py-2 px-3" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<style>
/* General Sidebar Styles */
.sidebar {
    background-color: #1a202c;
    width: 250px;
    display: flex;
    flex-direction: column;
}

/* Profile Section */
.user-profile {
    color: #f0f0f0;
}

/* Navbar Links */
.nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #a0aec0;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s, color 0.3s;
}

.nav-link:hover {
    background-color: #2d3748;
    color: #edf2f7;
}

/* Active State (Optional, add logic for active menu) */
.nav-link.active {
    background-color: #4a5568;
    color: #edf2f7;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: relative;
    }
}
</style>
