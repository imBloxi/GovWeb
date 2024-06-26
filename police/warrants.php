<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection
include '../includes/header.php'; // Include header

// Check if user is authorized as police or admin
if ($_SESSION['role'] !== 'police' && $_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Query to fetch all civilians
$query = "SELECT id, CONCAT(first_name, ' ', last_name) AS full_name FROM civilians";
$result = $conn->query($query);

// Initialize variable to hold selected civilian ID
$civilian_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $civilian_id = isset($_POST['civilian_id']) ? (int)$_POST['civilian_id'] : null;

    if ($civilian_id) {
        // Query to fetch civilian details by ID
        $query = "SELECT id, first_name, last_name, email, phone, address FROM civilians WHERE id = ?";
        
        // Prepare statement
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $civilian_id);
        
        // Execute statement
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $civilian = $result->fetch_assoc();
            
            if ($civilian) {
                // Display civilian details
                echo "<h2>Civilian Information</h2>";
                echo "<p><strong>Name:</strong> {$civilian['first_name']} {$civilian['last_name']}</p>";
                echo "<p><strong>Email:</strong> {$civilian['email']}</p>";
                echo "<p><strong>Phone:</strong> {$civilian['phone']}</p>";
                echo "<p><strong>Address:</strong> {$civilian['address']}</p>";

                // Option to issue warrant
                echo '<h2>Issue Warrant</h2>';
                echo '<form method="POST" action="process_warrant.php">';
                echo '<input type="hidden" name="civilian_id" value="' . $civilian['id'] . '">';
                echo '<button type="submit">Issue Warrant</button>';
                echo '</form>';
            } else {
                echo "Civilian not found.";
            }
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        
        // Close statement
        $stmt->close();
    } else {
        echo "Please select a civilian.";
    }
}
?>

<main>
    <h2>Select Civilian</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <label for="civilian_id">Select Civilian:</label>
        <select id="civilian_id" name="civilian_id">
            <option value="">-- Select Civilian --</option>
            <?php while ($row = $result->fetch_assoc()): ?>
                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['full_name']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit">View Details</button>
    </form>
</main>

<?php include '../includes/footer.php'; ?>
