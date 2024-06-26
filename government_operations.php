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

// Initialize search variable
$search_operation_name = '';

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_operation_name = mysqli_real_escape_string($conn, $_POST['search_operation_name']);
}

// Query to get all operations or search by operation name
$query = "SELECT id, operation_name, operation_type, start_date, end_date, status, description 
          FROM operations";
if (!empty($search_operation_name)) {
    $query .= " WHERE operation_name LIKE '%$search_operation_name%'";
}
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<main>
    <h2>Government Operations</h2>
    
    <!-- Search Form -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="search_operation_name">Search by Operation Name:</label>
            <input type="text" id="search_operation_name" name="search_operation_name" value="<?php echo htmlspecialchars($search_operation_name); ?>">
            <button type="submit">Search</button>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Operation Name</th>
                <th>Operation Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Status</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['operation_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['operation_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<?php include 'includes/footer.php'; ?>
