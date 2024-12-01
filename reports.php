<?php
include 'db.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is an admin
$user_id = $_SESSION['user_id'];
$sql = "SELECT role FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user['role'] !== 'admin') {
    header("Location: user_dashboard.php"); // Redirect non-admin users
    exit();
}

// Fetch the total count of pending, approved, and rejected requests
$requests_sql = "
    SELECT status, COUNT(*) AS count
    FROM book_requests
    GROUP BY status";
$requests_stmt = $pdo->prepare($requests_sql);
$requests_stmt->execute();
$requests_count = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total books count
$total_books_sql = "SELECT COUNT(*) AS total_books FROM books";
$total_books_stmt = $pdo->prepare($total_books_sql);
$total_books_stmt->execute();
$total_books = $total_books_stmt->fetch()['total_books'];

// Fetch total students count
$total_students_sql = "SELECT COUNT(*) AS total_students FROM users WHERE role = 'student'";
$total_students_stmt = $pdo->prepare($total_students_sql);
$total_students_stmt->execute();
$total_students = $total_students_stmt->fetch()['total_students'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="admin_panel.php" class="brand-link">
                <span class="brand-text font-weight-light">Admin Dashboard</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <!-- Existing links -->
                        <li class="nav-item">
                            <a href="admin_panel.php" class="nav-link">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_books.php" class="nav-link">
                                <i class="nav-icon fas fa-book"></i>
                                <p>Manage Books</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link active">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="container mt-4">
                <h2 class="mb-4">Library Reports</h2>

                <!-- Book Requests Report -->
                <div class="row mb-4">
                    <div class="col-12 col-md-4">
                        <div class="card bg-primary text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-clipboard-list"></i> Pending Requests</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4">
                                    <?php 
                                    $pending_requests = 0;
                                    foreach ($requests_count as $status) {
                                        if ($status['status'] == 'pending') {
                                            $pending_requests = $status['count'];
                                        }
                                    }
                                    echo $pending_requests;
                                    ?>
                                </h3>
                                <p class="card-text">Pending Requests</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-success text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-check-circle"></i> Approved Requests</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4">
                                    <?php 
                                    $approved_requests = 0;
                                    foreach ($requests_count as $status) {
                                        if ($status['status'] == 'approved') {
                                            $approved_requests = $status['count'];
                                        }
                                    }
                                    echo $approved_requests;
                                    ?>
                                </h3>
                                <p class="card-text">Approved Requests</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-danger text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-times-circle"></i> Rejected Requests</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4">
                                    <?php 
                                    $rejected_requests = 0;
                                    foreach ($requests_count as $status) {
                                        if ($status['status'] == 'rejected') {
                                            $rejected_requests = $status['count'];
                                        }
                                    }
                                    echo $rejected_requests;
                                    ?>
                                </h3>
                                <p class="card-text">Rejected Requests</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Library Statistics -->
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="card bg-info text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-book"></i> Total Books</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4"><?php echo $total_books; ?></h3>
                                <p class="card-text">Books in Library</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card bg-warning text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-user-graduate"></i> Total Students</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4"><?php echo $total_students; ?></h3>
                                <p class="card-text">Students Registered</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
</body>
</html>
