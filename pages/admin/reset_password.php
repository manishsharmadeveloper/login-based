<?php
session_start();

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

if ($conn->connect_error) {
    die("Database connection failed");
}

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access denied");
}

// Generate password function
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#%&!';
    return substr(str_shuffle($chars), 0, $length);
}

$message = "";
$generatedPassword = "";

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);

    // check user
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {

        // If generate button clicked OR checkbox
        if (isset($_POST['generate'])) {
            $newPassword = generatePassword();
            $generatedPassword = $newPassword;
        } else {
            $newPassword = $_POST['password'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE users 
            SET password = ?, failed_attempts = 0, is_locked = 0 
            WHERE username = ?
        ");
        $stmt->bind_param("ss", $hash, $username);
        $stmt->execute();

        $message = "Password updated successfully for $username";

    } else {
        $message = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>

    <script>
        // 🔥 Generate password instantly in UI (no refresh needed)
        function generateUI() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#%&!";
            let pass = "";

            for (let i = 0; i < 10; i++) {
                pass += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            document.getElementById("password").value = pass;
            document.getElementById("generatedBox").innerText = "Generated Password: " + pass;
        }
    </script>
</head>

<body>

<h2>Admin - Reset Password</h2>

<?php if ($message): ?>
    <p style="color:green;"><?php echo $message; ?></p>
<?php endif; ?>

<!-- Show generated password after submit -->
<?php if ($generatedPassword): ?>
    <p style="color:blue;">
        Server Generated Password: <b><?php echo $generatedPassword; ?></b>
    </p>
<?php endif; ?>

<form method="POST">

    <input type="text" name="username" placeholder="Enter Username" required><br><br>

    <!-- password field -->
    <input type="text" id="password" name="password" placeholder="Enter Password"><br><br>

    <!-- UI generated password display -->
    <p id="generatedBox" style="color:blue;"></p>

    <!-- Buttons -->
    <button type="button" onclick="generateUI()">Generate Password</button>

    <label>
        <input type="checkbox" name="generate" value="1">
        Use Server Generated Password
    </label>

    <br><br>

    <button type="submit">Update Password</button>

</form>

</body>
</html>