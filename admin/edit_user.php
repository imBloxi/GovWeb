<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission for updating or deleting user
    $action = $_POST['action'];
    $user_id = $_POST['user_id'];

    if ($action === 'update') {
        $username = $_POST['username'];
        $role = $_POST['role'];
        $conn->query("UPDATE users SET username='$username', role='$role' WHERE id='$user_id'");
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM users WHERE id='$user_id'");
    }
}

$users = $conn->query("SELECT * FROM users");
?>

<main>
    <h2>Manage Users</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()): ?>
        <tr>
            <form method="POST">
                <td><?php echo $user['id']; ?></td>
                <td><input type="text" name="username" value="<?php echo $user['username']; ?>"></td>
                <td>
                    <select name="role">
                        <option value="civilian" <?php if ($user['role'] === 'civilian') echo 'selected'; ?>>Civilian</option>
                        <option value="officer" <?php if ($user['role'] === 'officer') echo 'selected'; ?>>Officer</option>
                        <option value="moderator" <?php if ($user['role'] === 'moderator') echo 'selected'; ?>>Moderator</option>
                        <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </td>
                <td>
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="action" value="update">Update</button>
                    <button type="submit" name="action" value="delete">Delete</button>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
