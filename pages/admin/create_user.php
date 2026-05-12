<?php
// session_start();
require_once "../../config/auth.php";
checkRole('admin');

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

/* Only admin can access */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../login/login.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $role);

    if ($stmt->execute()) {
        $message = "User created successfully!";
    } else {
        $message = "Error: " . $conn->error;
    }
}
$username = $_SESSION['username'] ?? 'Admin';
?>

<div style="display:flex; justify-content:space-between; padding:12px; background:#f2f2f2;">
    
    <div>
        <h3 style="margin:0;">Admin Dashboard</h3>
    </div>

    <div>
        Welcome, <b><?php echo htmlspecialchars($username); ?></b> 👋 |
        <a href="../../login/logout.php">Logout</a>
    </div>

</div>
<h2>Create New User</h2>

<p style="color:green;"><?php echo $message; ?></p>

<form method="POST">

    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Role:</label><br>
    <select name="role" required>
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="user">User</option>
    </select><br><br>

    <button type="submit">Create User</button>

</form>

<br>
<a href="admin_dashboard.php">Back to Dashboard</a>