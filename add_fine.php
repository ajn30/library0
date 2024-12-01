<?php
include 'db.php';
session_start();

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];

    $insert_sql = "INSERT INTO fines (user_id, amount) VALUES (?, ?)";
    $insert_stmt = $pdo->prepare($insert_sql);
    $insert_stmt->execute([$user_id, $amount]);

    header('Location: admin_panel.php');
    exit();
}
?>

<form action="add_fine.php" method="POST">
    <div class="form-group">
        <label for="user_id">User</label>
        <select name="user_id" id="user_id" class="form-control">
            <?php
            $users_sql = "SELECT id, name FROM users";
            $users_stmt = $pdo->prepare($users_sql);
            $users_stmt->execute();
            $users = $users_stmt->fetchAll();
            foreach ($users as $user) {
                echo "<option value='{$user['id']}'>{$user['name']}</option>";
            }
            ?>
        </select>
    </div>
    <div class="form-group">
        <label for="amount">Fine Amount</label>
        <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Add Fine</button>
</form>
