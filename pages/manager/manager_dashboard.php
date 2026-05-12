<?php
session_start();

require_once "../../config/auth.php";
checkRole('manager');

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

if ($conn->connect_error) {
    die("Database connection failed");
}

$username = $_SESSION['username'] ?? 'Manager';
$user_id = $_SESSION['user_id'];

$message = "";

/* =========================
   CHANGE PASSWORD LOGIC
========================= */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $message = "New password and confirm password do not match.";
    } else {

        // get current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current, $user['password'])) {

            $hash = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $hash, $user_id);
            $update->execute();

            $message = "Password changed successfully.";

        } else {
            $message = "Current password is incorrect.";
        }
    }
}
?>

<!-- HEADER -->
<div style="display:flex; justify-content:space-between; padding:12px; background:#f2f2f2;">
    
    <div>
        <h3 style="margin:0;">Manager Dashboard</h3>
    </div>

    <div>
        Welcome, <b><?php echo htmlspecialchars($username); ?></b> 👋 |
        <a href="../../login/logout.php">Logout</a>
    </div>

</div>

<hr>

<h1>Welcome Manager Panel</h1>

<?php if ($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<!-- 🔐 CHANGE PASSWORD SECTION -->
<h3>Change Password</h3>

<form method="POST">

    <input type="password" name="current_password" placeholder="Current Password" required><br><br>

    <input type="password" name="new_password" placeholder="New Password" required><br><br>

    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br><br>

    <button type="submit">Update Password</button>

</form>

<hr>

<div>
    <a href="#">View Reports</a><br>
    <a href="#">Manage Users</a><br>
    <a href="#">Activity Logs</a>
</div>