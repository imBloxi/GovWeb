<?php
include 'includes/auth.php';
include 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['role'])) {
    header('Location: auth/login.php');
    exit();
}

// Navigation based on user role
if ($_SESSION['role'] === 'admin') {
    $nav_links = [
        ['url' => 'admin/index.php', 'label' => 'Admin Panel'],
        ['url' => 'police/index.php', 'label' => 'Police Operations'],
        ['url' => 'court/index.php', 'label' => 'Court Operations'],
        ['url' => 'auth/logout.php', 'label' => 'Logout']
    ];
} elseif ($_SESSION['role'] === 'officer') {
    $nav_links = [
        ['url' => 'police/index.php', 'label' => 'Police Operations'],
        ['url' => 'court/index.php', 'label' => 'Court Operations'],
        ['url' => 'auth/logout.php', 'label' => 'Logout']
    ];
} elseif ($_SESSION['role'] === 'moderator') {
    $nav_links = [
        ['url' => 'police/index.php', 'label' => 'Police Operations'],
        ['url' => 'court/index.php', 'label' => 'Court Operations'],
        ['url' => 'auth/logout.php', 'label' => 'Logout']
    ];
} elseif ($_SESSION['role'] === 'civilian') {
    $nav_links = [
        ['url' => 'civilians.php', 'label' => 'View My Details'],
        ['url' => 'licenses.php', 'label' => 'Manage Licenses'],
        ['url' => 'auth/logout.php', 'label' => 'Logout']
    ];
}

?>

<main>
    <h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
    <nav>
        <ul>
            <?php foreach ($nav_links as $link): ?>
                <li><a href="<?php echo $link['url']; ?>"><?php echo $link['label']; ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>
</main>

<?php include 'includes/footer.php'; ?>
