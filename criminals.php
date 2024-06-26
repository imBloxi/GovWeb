<?php
include 'includes/auth.php'; // Ensure authentication
include 'includes/db.php';   // Include database connection
include 'includes/header.php'; // Include header

// Check if the user is authorized to view this page
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'moderator') {
    echo "Access denied!";
    include 'includes/footer.php';
    exit();
}

// Query to get all criminal records
$query = "SELECT cr.id, c.first_name, c.last_name, cr.crime_date, cr.crime_type, cr.description 
          FROM criminal_records cr 
          JOIN civilians c ON cr.civilian_id = c.id";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<main>
    <h2>Criminal Records</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Crime Date</th>
                <th>Crime Type</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['crime_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['crime_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<?php include 'includes/footer.php'; ?>
