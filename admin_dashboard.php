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
$requests_sql = "SELECT br.id, br.title, br.author, br.request_date, u.name AS user_name 
                 FROM book_requests br 
                 JOIN users u ON br.user_id = u.id 
                 WHERE br.status = 'pending'";
$requests_stmt = $pdo->prepare($requests_sql);
$requests_stmt->execute();
$requests = $requests_stmt->fetchAll();

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Library Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: linear-gradient(90deg, #007bff, #0056b3);
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-custom {
            background-color: #007bff;
            color: #fff;
            border-radius: 25px;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        table {
            background-color: transparent;
            border-radius: 10px;
            overflow: blue;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        th {
            background-color: white;
            color: #fff;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin_panel.php">Admin Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link active" href="admin_panel.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="manage_books.php">Manage Books</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="logout.php">Logout</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4">Pending Book Requests</h2>

    <!-- Book Requests Table -->
    <div class="card p-4">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>User</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Request Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($requests) > 0): ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['id']); ?></td>
                            <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                            <td><?php echo htmlspecialchars($request['author']); ?></td>
                            <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                            <td>
                                <form action="admin_panel.php" method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form action="admin_panel.php" method="POST" class="d-inline">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No pending requests.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>