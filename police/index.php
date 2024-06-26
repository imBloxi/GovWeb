<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'officer' && $_SESSION['role'] !== 'moderator') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}
?>

<main>
    <h2>Police Operations</h2>
    <nav>
        <ul>
            <li><a href="add_criminal.php">Add Criminal Record</a></li>
            <li><a href="view_operations.php">View Operations</a></li>
            <li><a href="dispatch.php">Dispatch</a></li>
            <li><a href="warrants.php">Warrants</a></li>
            <li><a href="../auth/logout.php">Logout</a></li>
        </ul>
    </nav>
</main>

<?php include '../includes/footer.php'; ?>
