<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection

// Check if user is authorized as police or admin
if ($_SESSION['role'] !== 'police' && $_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Initialize variables
$warrant_id = generateWarrantID(); // Generate random warrant ID
$civilian_id = isset($_POST['civilian_id']) ? intval($_POST['civilian_id']) : '';
$status = 'issued'; // Default status upon issue

$errors = [];

// Validate inputs
if (empty($civilian_id)) {
    $errors[] = 'Civilian ID is required';
}

// Proceed if no errors
if (empty($errors)) {
    // Insert warrant into database
    $insert_query = "INSERT INTO warrants (warrant_id, civilian_id, issue_date, status) VALUES (?, ?, NOW(), ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sis", $warrant_id, $civilian_id, $status);

    if ($stmt->execute()) {
        echo '<div class="success">Warrant issued successfully!</div>';
    } else {
        echo '<div class="error">Error: ' . $stmt->error . '</div>';
    }

    $stmt->close();
}

// Function to generate random alphanumeric warrant ID
function generateWarrantID($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>

<main>
    <h2>Process Warrant</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="civilian_id">Enter Civilian ID:</label>
            <input type="text" id="civilian_id" name="civilian_id" placeholder="Enter civilian ID">
        </div>
        <div>
            <button type="submit">Process Warrant</button>
        </div>
    </form>

    <?php
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
