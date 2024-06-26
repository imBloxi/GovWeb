<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

$file_id = $_GET['file_id'];
$result = $conn->query("SELECT * FROM court_documents WHERE file_id='$file_id' ORDER BY issue_date DESC");
?>

<main>
    <h2>Documents for Court File: <?php echo $file_id; ?></h2>
    <table>
        <tr>
            <th>Document Type</th>
            <th>Issued By</th>
            <th>Issue Date</th>
            <th>Details</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['document_type']; ?></td>
            <td><?php echo $row['issued_by']; ?></td>
            <td><?php echo $row['issue_date']; ?></td>
            <td><?php echo $row['details']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
