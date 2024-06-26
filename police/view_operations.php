<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'moderator' && $_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

$result = $conn->query("SELECT * FROM police_operations");

?>

<main>
    <h2>Police Operations</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Operation Name</th>
            <th>Date</th>
            <th>Details</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['operation_name']; ?></td>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['details']; ?></td>
            <td><?php echo $row['status']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
