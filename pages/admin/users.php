<?php
session_start();

require_once "../../config/auth.php";
checkRole('admin');

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

if ($conn->connect_error) {
    die("Database connection failed");
}

/* =========================
   LOCK USER
========================= */
if (isset($_GET['lock'])) {

    $id = $_GET['lock'];

    $stmt = $conn->prepare("UPDATE users SET is_locked = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

/* =========================
   UNLOCK USER
========================= */
if (isset($_GET['unlock'])) {

    $id = $_GET['unlock'];

    $stmt = $conn->prepare("
        UPDATE users 
        SET is_locked = 0, failed_attempts = 0 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

/* =========================
   DELETE USER
========================= */
if (isset($_GET['delete'])) {

    $id = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: users.php");
    exit();
}

/* =========================
   RESET PASSWORD
========================= */

$generatedPassword = "";
$openModalUser = null;


if (isset($_POST['reset_password'])) {

    $userId = intval($_POST['user_id']);

    function generatePassword($length = 10)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@#%&!';
        return substr(str_shuffle($chars), 0, $length);
    }


    $generatedPassword = generatePassword();

    $hash = password_hash($generatedPassword, PASSWORD_DEFAULT);


    $stmt = $conn->prepare("
        UPDATE users 
        SET password=?, failed_attempts=0, is_locked=0
        WHERE id=?
    ");

    $stmt->bind_param("si", $hash, $userId);
    $stmt->execute();


    // Remove old password completely
    $_SESSION['reset_password'] = $generatedPassword;
    $_SESSION['reset_user'] = $userId;


    header("Location: users.php");
    exit();
}


// Load only current reset password

if (isset($_SESSION['reset_password'])) {

    $generatedPassword = $_SESSION['reset_password'];
    $openModalUser = $_SESSION['reset_user'];
}


/* read session */

$showPasswords = $_SESSION['temp_passwords'] ?? [];
$openModalUser = $_SESSION['open_modal_user'] ?? null;


/* =========================
   FETCH USERS
========================= */
$result = $conn->query("
    SELECT *,
    DATEDIFF(NOW(), last_login) AS days_since_login
    FROM users
");

$username = $_SESSION['username'] ?? 'Admin';
?>

<!-- HEADER -->
<div style="display:flex; justify-content:space-between; padding:12px; background:#f2f2f2;">
    <div>
        <h3 style="margin:0;">Admin Dashboard</h3>
    </div>

    <div>
        Welcome, <b><?php echo htmlspecialchars($username); ?></b> 👋 |
        <a href="../../login/logout.php">Logout</a>
    </div>
</div>

<h2>User Management</h2>

<a href="create_user.php">+ Create New User</a>
<br><br>

<table border="1" cellpadding="10">

    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>Status</th>
        <th>Last Login</th>
        <th>Inactive Days</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>

        <tr>

            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= $row['role'] ?></td>

            <td>
                <?php if ($row['is_locked']) { ?>
                    <span style="background:red;color:white;padding:3px 6px;border-radius:4px;">🔒 Locked</span>
                <?php } else { ?>
                    <span style="background:green;color:white;padding:3px 6px;border-radius:4px;">Active</span>
                <?php } ?>
            </td>

            <td>
                <?= !empty($row['last_login']) ? $row['last_login'] : "Never logged in" ?>
            </td>

            <td>
                <?php
                if (!empty($row['last_login'])) {
                    $days = $row['days_since_login'];

                    if ($days == 0) {
                        echo "<span style='color:green;'>Today</span>";
                    } elseif ($days <= 7) {
                        echo "<span style='color:orange;'>$days days</span>";
                    } else {
                        echo "<span style='color:red;'>$days days</span>";
                    }
                } else {
                    echo "<span style='color:gray;'>Never</span>";
                }
                ?>
            </td>

            <td>

                <a href="edit_user.php?id=<?= $row['id'] ?>">Edit</a> |

                <?php if ($row['is_locked']) { ?>
                    <a href="users.php?unlock=<?= $row['id'] ?>" style="color:green;">Unlock</a> |
                <?php } else { ?>
                    <a href="users.php?lock=<?= $row['id'] ?>" style="color:red;">Lock</a> |
                <?php } ?>

                <a href="#" onclick="openModal(<?= $row['id'] ?>)">Reset Password</a> |

                <a href="users.php?delete=<?= $row['id'] ?>"
                    onclick="return confirm('Are you sure?')">Delete</a>

            </td>

        </tr>

    <?php } ?>

</table>

<!-- RESET PASSWORD MODAL -->
<div id="resetModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
background:rgba(0,0,0,0.5);">

    <div style="background:#fff;width:360px;margin:10% auto;padding:20px;border-radius:8px;text-align:center;">

        <h3>Reset Password</h3>

        <?php if ($generatedPassword != ""): ?>

            <div style="background:#e8f7ff;padding:10px;margin-bottom:10px;">
                <b>Generated Password:</b><br>

                <span style="font-size:18px;color:#0077cc;">
                    <?= htmlspecialchars($generatedPassword) ?>
                </span>

            </div>

        <?php endif; ?>

        <form method="POST">

            <input type="hidden" name="user_id" id="user_id">

            <button type="submit" name="reset_password"
                style="padding:8px 12px;background:green;color:white;border:none; cursor: pointer;">
                Generate Password
            </button>

            <button type="button" onclick="closeModal()"
                style="padding:8px 12px;background:red;color:white;border:none; cursor: pointer;">
                Close
            </button>

        </form>

    </div>
</div>

<script>
    function openModal(id) {

        document.getElementById("user_id").value = id;

        // Hide previous generated password
        let passwordBox = document.querySelector(".generated-password");

        if (passwordBox) {
            passwordBox.style.display = "none";
        }

        document.getElementById("resetModal").style.display = "block";
    }

    function closeModal() {
        document.getElementById("resetModal").style.display = "none";
    }

    <?php if ($openModalUser): ?>
        window.onload = function() {
            document.getElementById("user_id").value = <?= $openModalUser ?>;
            document.getElementById("resetModal").style.display = "block";
        };
    <?php endif; ?>
</script>

<br>
<a href="admin_dashboard.php">Back to Dashboard</a>