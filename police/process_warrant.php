<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection

// Check if user is authorized as police or admin
if ($_SESSION['role'] !== 'police' && $_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Initialize variables for form inputs and errors
$civilian_id = $issue_date = $status = $warrant_description = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs and check if set to avoid undefined index warning
    $civilian_id = isset($_POST['civilian_id']) ? $_POST['civilian_id'] : '';
    $issue_date = isset($_POST['issue_date']) ? $_POST['issue_date'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    $warrant_description = isset($_POST['warrant_description']) ? $_POST['warrant_description'] : '';

    // Simple validation (you may need more depending on your requirements)
    if (empty($civilian_id)) { $errors[] = 'Civilian ID is required'; }
    if (empty($issue_date)) { $errors[] = 'Issue date is required'; }
    if (empty($status)) { $errors[] = 'Status is required'; }

    // Proceed with insertion if there are no errors
    if (empty($errors)) {
        // Verify if the civilian_id exists in civilians table
        $check_civilian_query = "SELECT id FROM civilians WHERE id = ?";
        $stmt_check = $conn->prepare($check_civilian_query);
        $stmt_check->bind_param("i", $civilian_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            // Insert query using prepared statement for security
            $insert_query = "INSERT INTO warrants (civilian_id, issue_date, status, warrant_description) VALUES (?, ?, ?, ?)";

            // Prepare statement
            $stmt = $conn->prepare($insert_query);
            if ($stmt === false) {
                echo "Error preparing statement: " . $conn->error;
                exit();
            }

            // Bind parameters
            $stmt->bind_param("isss", $civilian_id, $issue_date, $status, $warrant_description);

            // Execute statement
            if ($stmt->execute()) {
                echo '<div class="success">Warrant issued successfully!</div>';
                // Clear form inputs after successful submission
                $civilian_id = $issue_date = $status = $warrant_description = '';
            } else {
                echo '<div class="error">Error issuing warrant: ' . $stmt->error . '</div>';
            }

            // Close statement
            $stmt->close();
        } else {
            echo '<div class="error">Civilian with ID ' . $civilian_id . ' does not exist.</div>';
        }

        // Close statement
        $stmt_check->close();
    }
}

include '../includes/header.php'; // Include header
?>

<main>
    <h2>Issue Warrant</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="civilian_id">Civilian ID:</label>
            <input type="text" id="civilian_id" name="civilian_id" value="<?php echo htmlspecialchars($civilian_id); ?>">
        </div>
        <div>
            <label for="issue_date">Issue Date:</label>
            <input type="date" id="issue_date" name="issue_date" value="<?php echo htmlspecialchars($issue_date); ?>">
        </div>
        <div>
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="issued" <?php if ($status === 'issued') echo 'selected'; ?>>Issued</option>
                <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                <option value="cancelled" <?php if ($status === 'cancelled') echo 'selected'; ?>>Cancelled</option>
            </select>
        </div>
        <div>
            <label for="warrant_description">Warrant Description:</label>
            <textarea id="warrant_description" name="warrant_description"><?php echo htmlspecialchars($warrant_description); ?></textarea>
        </div>
        <div>
            <button type="submit">Issue Warrant</button>
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
