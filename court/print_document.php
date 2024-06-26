<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection

// Check if user is authorized as admin or judge
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'judge') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

$document_id = isset($_GET['document_id']) ? mysqli_real_escape_string($conn, $_GET['document_id']) : '';

if (empty($document_id)) {
    echo "Invalid document ID.";
    exit();
}

// Fetch document details
$query = "SELECT d.document_id, d.court_name, d.person_name, d.court_date, d.judge_name, d.case_number, d.description, c.charge 
          FROM court_documents d 
          JOIN court_charges c ON d.document_id = c.document_id 
          WHERE d.document_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $document_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $document = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "Document not found.";
    exit();
}
$stmt->close();

// Fetch personal information
$person_name = $document[0]['person_name'];
$query = "SELECT * FROM civilians WHERE CONCAT(first_name, ' ', last_name) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $person_name);
$stmt->execute();
$person_result = $stmt->get_result();
$person_info = $person_result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .document {
            border: 1px solid #000;
            padding: 20px;
            margin: 20px;
            position: relative;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 50px;
            color: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }
        .personal-info {
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        @media print {
            #edit-button {
                display: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="document">
        <div class="watermark">REAL court_charges</div>
        <h2>Court Document</h2>
        <p><strong>Document ID:</strong> <?php echo htmlspecialchars($document[0]['document_id']); ?></p>
        <p><strong>Court Name:</strong> <?php echo htmlspecialchars($document[0]['court_name']); ?></p>
        <p><strong>Person Name:</strong> <?php echo htmlspecialchars($document[0]['person_name']); ?></p>
        <p><strong>Court Date:</strong> <?php echo htmlspecialchars($document[0]['court_date']); ?></p>
        <p><strong>Judge Name:</strong> <?php echo htmlspecialchars($document[0]['judge_name']); ?></p>
        <p><strong>Case Number:</strong> <?php echo htmlspecialchars($document[0]['case_number']); ?></p>
        <div class="watermark">REAL court_charges</div>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($document[0]['description'])); ?></p>
        <h3>Charges:</h3>
        <ul>
            <?php foreach ($document as $doc) { ?>
                <li><?php echo htmlspecialchars($doc['charge']); ?></li>
            <?php } ?>
        </ul>
        <div class="personal-info">
            <div class="watermark">REAL court_charges</div>
            <p><strong>Person ID:</strong> <?php echo htmlspecialchars($person_info['id']); ?></p>
            <p><strong>Full Name:</strong> <?php echo htmlspecialchars($person_info['first_name'] . ' ' . $person_info['last_name']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($person_info['gender']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($person_info['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($person_info['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($person_info['address']); ?></p>
            <p><strong>Occupation:</strong> <?php echo htmlspecialchars($person_info['occupation']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($person_info['dob']); ?></p>
            <p><strong>Person Code:</strong> <?php echo htmlspecialchars($person_info['person_code']); ?></p>
        </div>
    </div>
    <button onclick="window.print()" class="no-print">Print Document</button>
    <?php if ($_SESSION['role'] === 'admin') { ?>
        <button id="edit-button" onclick="window.location.href='edit_document.php?document_id=<?php echo htmlspecialchars($document_id); ?>'" class="no-print">Edit Document</button>
    <?php } ?>
</body>
</html>
