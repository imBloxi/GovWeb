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
$search_name = '';

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_name = mysqli_real_escape_string($conn, $_POST['search_name']);
}

// Query to get all civilians or search by name
$query = "SELECT id, first_name, last_name, gender, email, phone, address, occupation, dob, person_code 
          FROM civilians";
if (!empty($search_name)) {
    $query .= " WHERE first_name LIKE '%$search_name%' OR last_name LIKE '%$search_name%'";
}
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<main>
    <h2>Civilians</h2>
    
    <!-- Search Form -->
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="search_name">Search by Name:</label>
            <input type="text" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
            <button type="submit">Search</button>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Gender</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Occupation</th>
                <th>Date of Birth</th>
                <th>Person Code</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['occupation']); ?></td>
                    <td><?php echo htmlspecialchars($row['dob']); ?></td>
                    <td><?php echo htmlspecialchars($row['person_code']); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</main>

<?php include 'includes/footer.php'; ?>
