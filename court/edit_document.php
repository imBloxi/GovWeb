<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection

// Check if user is authorized as admin
if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_id = mysqli_real_escape_string($conn, $_POST['document_id']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    $query = "UPDATE court_documents SET description = ? WHERE document_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $description, $document_id);
    
    if ($stmt->execute()) {
        echo "Document updated successfully.";
    } else {
        echo "Error updating document: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: print_document.php?document_id=" . urlencode($document_id));
    exit();
}
?>
