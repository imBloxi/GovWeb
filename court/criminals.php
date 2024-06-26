<?php
include 'db.php';
include 'header.php';

$result = $conn->query("SELECT * FROM criminal_records JOIN civilians ON criminal_records.civilian_id = civilians.id");
?>

<main>
    <h2>Criminal Records</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Civilian Name</th>
            <th>Crime</th>
            <th>Date of Crime</th>
            <th>Sentence</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td><?php echo $row['crime']; ?></td>
            <td><?php echo $row['date_of_crime']; ?></td>
            <td><?php echo $row['sentence']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</main>

<?php include 'footer.php'; ?>
