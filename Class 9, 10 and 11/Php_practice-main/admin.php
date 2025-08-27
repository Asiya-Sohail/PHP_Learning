<?php
session_start();
require_once 'config.php';

// Optional: Check if admin is logged in
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Handle status toggle
if (isset($_GET['toggle_status']) && isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $new_status = intval($_GET['toggle_status']);
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php");
    exit();
}

// Fetch all users
$result = $conn->query("SELECT user_id, name, email, status FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
<?php if(isset($_SESSION['statusp'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['statusp']; ?>
                        <?php unset($_SESSION['statusp']); ?>
                    </div>
                <?php endif; ?>
                <?php if(isset($_SESSION['statusd'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['statusd']; ?>
                        <?php unset($_SESSION['statusd']); ?>
                    </div>
                <?php endif; ?>
    <h2>All Users</h2>
     <table class="table table-bordered">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Edit</th>
                <th>Delete</th>
                <th>Toggle Status</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <?php echo $row['status'] == 1 ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-danger">Off</span>'; ?>
                </td>
                <td>
                    <a href="editadmin.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                </td>
                <td>
                    <a href="deleteprofile.php?user_id=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
                <td>
                    <?php if ($row['status'] == 1): ?>
                        <a href="admin.php?toggle_status=0&user_id=<?php echo $row['user_id']; ?>" class="btn btn-warning btn-sm">Turn Off</a>
                    <?php else: ?>
                        <a href="admin.php?toggle_status=1&user_id=<?php echo $row['user_id']; ?>" class="btn btn-success btn-sm">Turn On</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php
$conn->close();
?>