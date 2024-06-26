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

// Initialize variables for form inputs and errors
$full_name = $crime_type = $description = $crime_date = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs and check if set to avoid undefined index warning
    $full_name = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, $_POST['full_name']) : '';
    $crime_type = isset($_POST['crime_type']) ? mysqli_real_escape_string($conn, $_POST['crime_type']) : '';
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : '';
    $crime_date = isset($_POST['crime_date']) ? mysqli_real_escape_string($conn, $_POST['crime_date']) : '';

    // Simple validation (you may need more depending on your requirements)
    if (empty($full_name)) { $errors[] = 'Full name is required'; }
    if (empty($crime_type)) { $errors[] = 'Crime type is required'; }
    if (empty($crime_date)) { $errors[] = 'Crime date is required'; }

    // Proceed with insertion if there are no errors
    if (empty($errors)) {
        // Insert query using prepared statement for security
        $insert_query = "INSERT INTO criminal_records (full_name, crime_type, description, crime_date) VALUES (?, ?, ?, ?)";

        // Prepare statement
        $stmt = $conn->prepare($insert_query);
        if ($stmt === false) {
            echo "Error preparing statement: " . $conn->error;
            exit();
        }

        // Bind parameters
        $stmt->bind_param("ssss", $full_name, $crime_type, $description, $crime_date);

        // Execute statement
        if ($stmt->execute()) {
            echo '<div class="success">Criminal registered successfully!</div>';
            // Clear form inputs after successful submission
            $full_name = $crime_type = $description = $crime_date = '';
        } else {
            echo '<div class="error">Error: ' . $stmt->error . '</div>';
        }

        // Close statement
        $stmt->close();
    }
}

?>

<main>
    <h2>Register Criminal</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">
        </div>
        <div>
            <label for="crime_type">Crime Type:</label>
            <input type="text" id="crime_type" name="crime_type" value="<?php echo htmlspecialchars($crime_type); ?>">
        </div>
        <div>
            <label for="description">Description:</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div>
            <label for="crime_date">Crime Date:</label>
            <input type="date" id="crime_date" name="crime_date" value="<?php echo htmlspecialchars($crime_date); ?>">
        </div>
        <div>
            <button type="submit">Register Criminal</button>
        </div>
    </form>

    <?php
    // Display errors if any
    if (!empty($errors)) {
        echo '<div class="error"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
</main>

<?php include '../includes/footer.php'; ?>
