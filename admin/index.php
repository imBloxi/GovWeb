<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}
?>

<main>
    <h2>Admin Panel</h2>
    <nav>
        <ul>
            <li><a href="edit_user.php">Manage Users</a></li>
            <li><a href="edit_civilian.php">Manage Civilians</a></li>
            <li><a href="edit_criminal.php">Manage Criminal Records</a></li>
            <li><a href="edit_license.php">Manage Licenses</a></li>
            <li><a href="edit_business.php">Manage Businesses</a></li>
            <li><a href="edit_court_file.php">Manage Court Files</a></li>
            <li><a href="edit_court_document.php">Manage Court Documents</a></li>
            <li><a href="edit_warrant.php">Manage Warrants</a></li>
        </ul>
    </nav>
</main>

<?php include '../includes/footer.php'; ?>
