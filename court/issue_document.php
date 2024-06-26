<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection
include '../includes/header.php'; // Include header

// Check if user is authorized as admin or judge
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'judge') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Initialize variables
$court_name = $person_name = $charges = $court_date = $judge_name = $case_number = $document_id = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $court_name = mysqli_real_escape_string($conn, $_POST['court_name']);
    $person_name = mysqli_real_escape_string($conn, $_POST['person_name']);
    $court_date = mysqli_real_escape_string($conn, $_POST['court_date']);
    $judge_name = mysqli_real_escape_string($conn, $_POST['judge_name']);
    $case_number = mysqli_real_escape_string($conn, $_POST['case_number']);
    
    // Custom function to escape array elements
    function escape_string($conn, $str) {
        return mysqli_real_escape_string($conn, $str);
    }
    
    $charges = array_map(function($charge) use ($conn) {
        return escape_string($conn, trim($charge));
    }, $_POST['charges']);

    // Validation
    if (empty($court_name)) $errors[] = "Court name is required.";
    if (empty($person_name)) $errors[] = "Person name is required.";
    if (empty($court_date)) $errors[] = "Court date is required.";
    if (empty($judge_name)) $errors[] = "Judge name is required.";
    if (empty($case_number)) $errors[] = "Case number is required.";
    if (empty($charges[0])) $errors[] = "At least one charge is required.";

    if (empty($errors)) {
        // Generate a unique document ID
        $document_id = uniqid('DOC-', true);

        // Insert document into database
        $stmt = $conn->prepare("INSERT INTO court_documents (document_id, court_name, person_name, court_date, judge_name, case_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $document_id, $court_name, $person_name, $court_date, $judge_name, $case_number);
        if ($stmt->execute()) {
            // Insert charges into database
            $stmt_charge = $conn->prepare("INSERT INTO court_charges (document_id, charge) VALUES (?, ?)");
            foreach ($charges as $charge) {
                if (!empty($charge)) {
                    $stmt_charge->bind_param("ss", $document_id, $charge);
                    $stmt_charge->execute();
                }
            }
            $stmt_charge->close();

            echo "<div class='success'>Document issued successfully. <a href='print_document.php?document_id={$document_id}' target='_blank'>Print Document</a></div>";
        } else {
            $errors[] = "Error issuing document: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Document</title>
    <link rel="stylesheet" href="../styles.css"> <!-- Ensure to adjust the path to your stylesheet -->
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .charge-field {
            display: flex;
            margin-bottom: 10px;
        }
        .charge-field input {
            flex-grow: 1;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <h2>Issue Court Document</h2>

    <?php
    if (!empty($errors)) {
        echo '<div class="error"><ul>';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul></div>';
    }
    ?>

    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="form-group">
            <label for="court_name">Court Name:</label>
            <input type="text" id="court_name" name="court_name" value="<?php echo htmlspecialchars($court_name); ?>">
        </div>
        <div class="form-group">
            <label for="person_name">Person Name:</label>
            <input type="text" id="person_name" name="person_name" value="<?php echo htmlspecialchars($person_name); ?>">
        </div>
        <div class="form-group">
            <label for="court_date">Court Date:</label>
            <input type="date" id="court_date" name="court_date" value="<?php echo htmlspecialchars($court_date); ?>">
        </div>
        <div class="form-group">
            <label for="judge_name">Judge Name:</label>
            <input type="text" id="judge_name" name="judge_name" value="<?php echo htmlspecialchars($judge_name); ?>">
        </div>
        <div class="form-group">
            <label for="case_number">Case Number:</label>
            <input type="text" id="case_number" name="case_number" value="<?php echo htmlspecialchars($case_number); ?>">
        </div>
        <div id="charges-container">
            <label>Charges:</label>
            <div class="charge-field">
                <input type="text" name="charges[]" value="">
                <button type="button" onclick="addChargeField()">Add Charge</button>
            </div>
        </div>
        <button type="submit">Issue Document</button>
    </form>

    <script>
        function addChargeField() {
            const container = document.getElementById('charges-container');
            const newField = document.createElement('div');
            newField.className = 'charge-field';
            newField.innerHTML = '<input type="text" name="charges[]" value=""><button type="button" onclick="removeChargeField(this)">Remove</button>';
            container.appendChild(newField);
        }

        function removeChargeField(button) {
            button.parentElement.remove();
        }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
