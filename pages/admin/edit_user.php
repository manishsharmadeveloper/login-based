<?php
session_start();

require_once "../../config/auth.php";
checkRole('admin');

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

if ($conn->connect_error) {
    die("Database connection failed");
}

/* Security check */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login/login.php");
    exit();
}

$id = $_GET['id'];

/* Get user */
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* Update user (ONLY username + role) */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $id);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

$admin_name = $_SESSION['username'] ?? 'Admin';
?>

<!-- HEADER -->
<div style="display:flex; justify-content:space-between; padding:12px; background:#f2f2f2;">
    
    <div>
        <h3 style="margin:0;">Admin Dashboard</h3>
    </div>

    <div>
        Welcome, <b><?php echo htmlspecialchars($admin_name); ?></b> 👋 |
        <a href="../../login/logout.php">Logout</a>
    </div>

</div>

<h2>Edit User</h2>

<form method="POST">

    Username:<br>
    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
    <br><br>

    Role:<br>
    <select name="role">
        <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
        <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>Manager</option>
        <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
    </select>

    <br><br>

    <button type="submit">Update User</button>

</form>

<br>
<a href="users.php">Back</a>