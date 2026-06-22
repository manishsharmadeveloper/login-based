<?php
session_start();

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

if ($conn->connect_error) {
    die("Database connection failed");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Get user
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {

        // Check if account is locked
        if ($user['is_locked'] == 1) {
            $error = "Account is locked. Contact admin.";
        } else {

            // Password verify
            if (password_verify($password, $user['password'])) {

                //  UPDATE LOGIN TIME + RESET ATTEMPTS
                $update = $conn->prepare("
                    UPDATE users 
                    SET last_login = NOW(), failed_attempts = 0 
                    WHERE id = ?
                ");
                $update->bind_param("i", $user['id']);
                $update->execute();

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];

                //  ROLE REDIRECT
                switch ($user['role']) {

                    case 'admin':
                        header("Location: ../pages/admin/admin_dashboard.php");
                        break;

                    case 'manager':
                        header("Location: ../pages/manager/manager_dashboard.php");
                        break;

                    case 'user':
                        header("Location: ../pages/users/user_dashboard.php");
                        break;

                    default:
                        header("Location: login.php");
                        break;
                }

                exit();

            } else {

                // WRONG PASSWORD
                $attempts = $user['failed_attempts'] + 1;

                if ($attempts >= 3) {

                    // LOCK ACCOUNT
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET failed_attempts = ?, is_locked = 1 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ii", $attempts, $user['id']);
                    $stmt->execute();

                    $error = "Account locked after 3 failed attempts.";

                } else {

                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET failed_attempts = ? 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ii", $attempts, $user['id']);
                    $stmt->execute();

                    $error = "Invalid password. Attempt $attempts of 3.";
                }
            }
        }

    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login System</title>
</head>
<body>

<h2>Login Page</h2>

<?php if ($error): ?>
    <p style="color:red;"><?php echo $error; ?></p>
<?php endif; ?>

<form method="POST" action="login.php">

    <input type="text" name="username" placeholder="Username" required><br><br>

    <input type="password" name="password" placeholder="Password" required><br><br>

    <button type="submit">Login</button>

</form>

</body>
</html>