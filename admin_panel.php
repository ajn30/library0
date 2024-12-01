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

// Fetch pending book requests
$requests_sql = "
    SELECT br.id, b.title, b.author, br.request_date, u.name AS user_name 
    FROM book_requests br
    JOIN books b ON br.book_id = b.id   -- Join with the books table
    JOIN users u ON br.user_id = u.id
    WHERE br.status = 'pending'";

$requests_stmt = $pdo->prepare($requests_sql);
$requests_stmt->execute();
$requests = $requests_stmt->fetchAll();

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

// Fetch fines for each user
$fines_sql = "
    SELECT u.id AS user_id, u.name AS user_name, SUM(f.amount) AS total_fines 
    FROM users u
    LEFT JOIN fines f ON u.id = f.user_id
    GROUP BY u.id";
$fines_stmt = $pdo->prepare($fines_sql);
$fines_stmt->execute();
$fines = $fines_stmt->fetchAll();

// Handle approve or reject actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        // Approve the request
        $update_sql = "UPDATE book_requests SET status = 'approved' WHERE id = ?";
    } else if ($action == 'reject') {
        // Reject the request
        $update_sql = "UPDATE book_requests SET status = 'rejected' WHERE id = ?";
    }

    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$request_id]);

    // Redirect to refresh the page
    header("Location: admin_panel.php");
    exit();
}

// Handle marking fine as paid
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'mark_paid') {
        $user_id = $_POST['user_id'];

        // Update fine status to paid
        $update_sql = "UPDATE fines SET status = 'paid' WHERE user_id = ? AND status = 'unpaid'";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$user_id]);

        // Redirect to refresh the page
        header("Location: admin_panel.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Library Management System</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <!-- FontAwesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Add Chart.js -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .chart-container {
            width: 40%; /* Reduced width */
            margin: 0 auto;
            padding-top: 20px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="admin_panel.php" class="brand-link">
                <span class="brand-text font-weight-light">Admin Dashboard</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
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
                            <a href="manage_users.php" class="nav-link">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Manage Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="view_books.php" class="nav-link active">
                                <i class="nav-icon fas fa-eye"></i>
                                <p>View Books</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="add_book.php" class="nav-link">
                                <i class="nav-icon fas fa-plus"></i>
                                <p>Add Book</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="reports.php" class="nav-link">
                                <i class="nav-icon fas fa-chart-line"></i>
                                <p>Reports</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="add_user.php" class="nav-link">
                                <i class="nav-icon fas fa-user-plus"></i>
                                <p>Add New User</p>
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

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <div class="container mt-4">
                <h2 class="mb-4">Pending Book Requests</h2>

                <!-- Total Books Count and Total Students Count -->
                <div class="row mb-4">
                    <!-- Total Books Card -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-primary text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-book"></i> Total Books</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4"><?php echo $total_books; ?></h3>
                                <p class="card-text">Books in the Library</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Students Card -->
                    <div class="col-12 col-md-6">
                        <div class="card bg-info text-white" style="border-radius: 15px; width: 200px; height: 200px; margin: auto;">
                            <div class="card-header" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                                <h5 class="card-title"><i class="fas fa-user-graduate"></i> Total Students</h5>
                            </div>
                            <div class="card-body text-center">
                                <h3 class="display-4"><?php echo $total_students; ?></h3>
                                <p class="card-text">Students in the System</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Circular Chart -->
                <div class="chart-container">
                    <h3>Books Distribution</h3>
                    <canvas id="booksDistributionChart"></canvas>
                </div>

                <!-- Book Requests Table -->
                <div class="card">
                    <div class="card-body">
                        <h3>Pending Book Requests</h3>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Author</th>
                                    <th>User Name</th>
                                    <th>Request Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['author']); ?></td>
                                        <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                                        <td>
                                            <form action="admin_panel.php" method="POST">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Users with Fines Table -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h3>Users with Fines</h3>
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>User Name</th>
                                    <th>Total Fines</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fines) > 0): ?>
                                    <?php foreach ($fines as $fine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fine['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($fine['total_fines'], 2)); ?> USD</td>
                                            <td><?php echo $fine['total_fines'] > 0 ? 'Unpaid' : 'Paid'; ?></td>
                                            <td>
                                                <?php if ($fine['total_fines'] > 0): ?>
                                                    <form action="admin_panel.php" method="POST">
                                                        <input type="hidden" name="user_id" value="<?php echo $fine['user_id']; ?>">
                                                        <button type="submit" name="action" value="mark_paid" class="btn btn-success btn-sm">Mark as Paid</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4">No fines for any user.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Script for Books Distribution -->
    <script>
        var ctx = document.getElementById('booksDistributionChart').getContext('2d');
        var booksDistributionChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Books Borrowed', 'Books Available'],
                datasets: [{
                    data: [<?php echo $total_students; ?>, <?php echo $total_books; ?>],
                    backgroundColor: ['#ff6384', '#36a2eb']
                }]
            }
        });
    </script>

</body>
</html>
