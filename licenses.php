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
$search_license_number = '';

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_license_number = mysqli_real_escape_string($conn, $_POST['search_license_number']);
}

// Query to get all licenses or search by license number
$query = "SELECT l.id, c.first_name, c.last_name, l.license_number, l.license_type, l.issue_date, l.expiry_date 
          FROM licenses l 
          JOIN civilians c ON l.civilian_id = c.id";
if (!empty($search_license_number)) {
    $query .= " WHERE l.license_number = '$search_license_number'";
}
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<main>
    <h2>Licenses</h2>
    
    <!-- Search Form -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="search_license_number">Search by License Number:</label>
            <input type="text" id="search_license_number" name="search_license_number" value="<?php echo htmlspecialchars($search_license_number); ?>">
            <button type="submit">Search</button>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>License Number</th>
                <th>License Type</th>
                <th>Issue Date</th>
                <th>Expiry Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['license_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['issue_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['expiry_date'] ?? 'N/A'); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<?php include 'includes/footer.php'; ?>
