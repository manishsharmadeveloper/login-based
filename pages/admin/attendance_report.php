<?php
session_start();
require_once "../../config/auth.php";
checkRole('admin');

$conn = new mysqli("localhost", "root", "admin@23bca", "login_based_authenticate");

$month = $_GET['month'] ?? date("Y-m");

/* get all users */
$users = $conn->query("SELECT id, username FROM users");
?>

<h2>Attendance Report - <?= $month ?></h2>

<form method="GET">
    <input type="month" name="month" value="<?= $month ?>">
    <button type="submit">Filter</button>
</form>

<br>

<table border="1" cellpadding="10">
<tr>
    <th>User</th>
    <th>Present Days</th>
</tr>

<?php while ($user = $users->fetch_assoc()) {

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE user_id = ? 
        AND DATE_FORMAT(attendance_date, '%Y-%m') = ?
    ");

    $stmt->bind_param("is", $user['id'], $month);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

?>

<tr>
    <td><?= $user['username'] ?></td>
    <td><?= $res['total'] ?> days</td>
</tr>

<?php } ?>

</table>