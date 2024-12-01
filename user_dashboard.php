    <?php
    include 'db.php';
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Fetch user info from the database
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Fetch user's total fine (sum of fines for overdue books)
    $fine_sql = "
        SELECT SUM(fine_amount) AS total_fine
        FROM transactions
        WHERE user_id = ? AND status = 'borrowed' AND return_date IS NULL AND due_date < CURDATE()";
    $fine_stmt = $pdo->prepare($fine_sql);
    $fine_stmt->execute([$user_id]);
    $fine = $fine_stmt->fetch();
    $total_fine = $fine['total_fine'] ? $fine['total_fine'] : 0.00;

    // Fetch all available books for the dropdown
    $books_sql = "SELECT id, title, author FROM books";
    $books_stmt = $pdo->prepare($books_sql);
    $books_stmt->execute();
    $books = $books_stmt->fetchAll();

    // Handle book request form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_book'])) {
        $book_id = $_POST['book_id'];

        // Validate input
        if (!empty($book_id)) {
            // Check if the user has already borrowed this book
            $check_sql = "SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'borrowed' AND book_id = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$user_id, $book_id]);
            $borrowed_count = $check_stmt->fetchColumn();

            if ($borrowed_count > 0) {
                $message = "You have already borrowed this book.";
            } else {
                // Insert the book request into the database
                $insert_sql = "INSERT INTO book_requests (user_id, book_id) VALUES (?, ?)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$user_id, $book_id]);

                $message = "Your book request has been submitted successfully.";
            }
        } else {
            $message = "Please select a book.";
        }
    }

    // Handle book request update (Edit)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_request'])) {
        $request_id = $_POST['request_id'];
        $new_book_id = $_POST['book_id'];

        // Update the book request
        $update_sql = "UPDATE book_requests SET book_id = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$new_book_id, $request_id, $user_id]);

        $message = "Book request updated successfully.";
    }

    // Handle book request deletion
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_request'])) {
        $request_id = $_POST['request_id'];

        // Delete the book request from the database
        $delete_sql = "DELETE FROM book_requests WHERE id = ? AND user_id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$request_id, $user_id]);

        $message = "Book request deleted successfully.";
    }

    // Handle Borrow Book Action
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['borrow_book'])) {
        $request_id = $_POST['request_id'];

        // Check if the request is approved
        $check_request_sql = "SELECT status, book_id FROM book_requests WHERE id = ? AND user_id = ?";
        $check_request_stmt = $pdo->prepare($check_request_sql);
        $check_request_stmt->execute([$request_id, $user_id]);
        $request = $check_request_stmt->fetch();

        if ($request && $request['status'] == 'approved') {
            // Insert a transaction for borrowing the book
            $transaction_sql = "INSERT INTO transactions (user_id, book_id, transaction_type) VALUES (?, ?, 'borrowed')";
            $transaction_stmt = $pdo->prepare($transaction_sql);
            $transaction_stmt->execute([$user_id, $request['book_id']]);

            // Update the book request status to "borrowed"
            $update_request_sql = "UPDATE book_requests SET status = 'borrowed' WHERE id = ?";
            $update_request_stmt = $pdo->prepare($update_request_sql);
            $update_request_stmt->execute([$request_id]);

            $message = "You have borrowed the book successfully.";
        } else {
            $message = "This request is not approved yet.";
        }
    }

    // Handle Return Book Action
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_book'])) {
        $request_id = $_POST['request_id'];

        // Check if the book is borrowed
        $check_transaction_sql = "SELECT status, book_id FROM transactions WHERE user_id = ? AND book_id IN (SELECT book_id FROM book_requests WHERE id = ?) AND transaction_type = 'borrowed'";
        $check_transaction_stmt = $pdo->prepare($check_transaction_sql);
        $check_transaction_stmt->execute([$user_id, $request_id]);
        $transaction = $check_transaction_stmt->fetch();

        if ($transaction) {
            // Insert a transaction for returning the book
            $return_sql = "INSERT INTO transactions (user_id, book_id, transaction_type) VALUES (?, ?, 'returned')";
            $return_stmt = $pdo->prepare($return_sql);
            $return_stmt->execute([$user_id, $transaction['book_id']]);

            // Update the request status to "returned"
            $update_request_sql = "UPDATE book_requests SET status = 'returned' WHERE id = ?";
            $update_request_stmt = $pdo->prepare($update_request_sql);
            $update_request_stmt->execute([$request_id]);

            $message = "You have returned the book successfully.";
        } else {
            $message = "You have not borrowed this book yet.";
        }
    }

    // Fetch book requests and their status, along with the title and author from the books table
    $request_sql = "
        SELECT br.id, b.title, b.author, br.request_date, br.status 
        FROM book_requests br
        JOIN books b ON br.book_id = b.id
        WHERE br.user_id = ?";
    $request_stmt = $pdo->prepare($request_sql);
    $request_stmt->execute([$user_id]);
    $requests = $request_stmt->fetchAll();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>User Dashboard - Library Management System</title>
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
                background-color: #fff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            th {
                background-color: #007bff;
                color: #fff;
            }
            .status {
                font-weight: bold;
            }
            .pending {
                color: orange;
            }
            .approved {
                color: green;
            }
            .rejected {
                color: red;
            }
        </style>
    </head>
    <body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="user_dashboard.php">Library Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
            <a class="nav-link active" href="user_dashboard.php">Dashboard</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="borrow_book.php">Borrow Book</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="return_book.php">Return Book</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
        </div>
    </div>
    </nav>

    <div class="container mt-5">
        <h2 class="mb-4">Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>

    <!-- Display fine amount -->
    <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <!-- Fine Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-cash-coin me-3" viewBox="0 0 16 16">
                <path d="M8 1a7 7 0 1 0 7 7 7.006 7.006 0 0 0-7-7Zm0 1a6 6 0 1 1-6 6 6.006 6.006 0 0 1 6-6Zm1 6V7h3V6H9V3H8v3H5v1h3v3h1Z"/>
            </svg>
            
            <!-- Fine Details -->
            <div>
                <strong>Outstanding Fine: </strong> 
                <span class="fw-bold fs-4 text-danger">
                    <?php echo number_format($total_fine, 2); ?>
                </span>
                <p class="mb-0 text-muted">This amount must be paid to borrow more books.</p>
            </div>
        </div>
        <!-- Close button -->
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>


        <!-- Book Request Section -->
        <div class="card p-4 mb-4">
            <h4>Request a New Book</h4>
            <?php if (isset($message)): ?>
                <div class="alert alert-info">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form action="user_dashboard.php" method="POST">
                <div class="mb-3">
                    <label for="book_id" class="form-label">Select a Book</label>
                    <select class="form-select" name="book_id" id="book_id" required>
                        <option value="" disabled selected>Select a book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?> by <?php echo htmlspecialchars($book['author']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="request_book" class="btn btn-custom">Request Book</button>
            </form>
        </div>

        <!-- Book Requests Section -->
        <div class="card p-4">
            <h4>Your Book Requests</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['title']); ?></td>
                            <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                            <td class="status <?php echo strtolower($request['status']); ?>"><?php echo ucfirst($request['status']); ?></td>
                            <td>
                                <!-- Borrow Book Button (only if approved) -->
                                <?php if ($request['status'] == 'approved'): ?>
                                    <form action="user_dashboard.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="borrow_book" class="btn btn-success">Borrow</button>
                                    </form>
                                <?php endif; ?>

                                <!-- Return Book Button (only if borrowed) -->
                                <?php if ($request['status'] == 'borrowed'): ?>
                                    <form action="user_dashboard.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" name="return_book" class="btn btn-info">Return</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    </body>
    </html>
