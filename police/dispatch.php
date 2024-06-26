<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'moderator' && $_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'];

    $sql = "INSERT INTO dispatches (message) VALUES ('$message')";
    if ($conn->query($sql) === TRUE) {
        echo "Dispatch sent!";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$result = $conn->query("SELECT * FROM dispatches ORDER BY timestamp DESC");
?>

<main>
    <h2>Dispatch Center</h2>
    <form method="POST">
        <textarea name="message" placeholder="Enter dispatch message" required></textarea>
        <button type="submit">Send Dispatch</button>
    </form>
    
    <h3>Recent Dispatches</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Message</th>
            <th>Timestamp</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['message']; ?></td>
            <td><?php echo $row['timestamp']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
