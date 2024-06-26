<?php
include '../includes/auth.php';
include '../includes/db.php';
include '../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

$table_name = 'civilians';
$id_field = 'id';
$fields = ['name', 'dob', 'address', 'phone', 'email', 'gender', 'occupation'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission for updating or deleting a record
    $action = $_POST['action'];
    $record_id = $_POST[$id_field];

    if ($action === 'update') {
        $update_fields = [];
        foreach ($fields as $field) {
            $update_fields[] = "$field='" . $_POST[$field] . "'";
        }
        $update_query = "UPDATE $table_name SET " . implode(',', $update_fields) . " WHERE $id_field='$record_id'";
        $conn->query($update_query);
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM $table_name WHERE $id_field='$record_id'");
    }
}

$records = $conn->query("SELECT * FROM $table_name");
?>

<main>
    <h2>Manage Civilians</h2>
    <table>
        <tr>
            <?php foreach ($fields as $field): ?>
                <th><?php echo ucfirst($field); ?></th>
            <?php endforeach; ?>
            <th>Actions</th>
        </tr>
        <?php while ($record = $records->fetch_assoc()): ?>
        <tr>
            <form method="POST">
                <?php foreach ($fields as $field): ?>
                    <td><input type="text" name="<?php echo $field; ?>" value="<?php echo $record[$field]; ?>"></td>
                <?php endforeach; ?>
                <td>
                    <input type="hidden" name="<?php echo $id_field; ?>" value="<?php echo $record[$id_field]; ?>">
                    <button type="submit" name="action" value="update">Update</button>
                    <button type="submit" name="action" value="delete">Delete</button>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include '../includes/footer.php'; ?>
