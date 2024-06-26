<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection


// Check if user is authorized as admin or judge
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'judge') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Initialize variables
$search_query = isset($_GET['search_query']) ? mysqli_real_escape_string($conn, $_GET['search_query']) : '';
$search_type = isset($_GET['search_type']) ? mysqli_real_escape_string($conn, $_GET['search_type']) : '';
$warrant = null;

if (!empty($search_query) && !empty($search_type)) {
    // Determine the search type and prepare the query accordingly
    if ($search_type == 'warrant_id') {
        $query = "SELECT w.warrant_id, w.civilian_id, w.description, w.reason, w.issue_date, w.status, 
                  c.first_name, c.last_name, c.gender, c.dob, c.address, c.occupation 
                  FROM warrants w 
                  JOIN civilians c ON w.civilian_id = c.id 
                  WHERE w.warrant_id = ?";
    } elseif ($search_type == 'full_name') {
        $query = "SELECT w.warrant_id, w.civilian_id, w.description, w.reason, w.issue_date, w.status, 
                  c.first_name, c.last_name, c.gender, c.dob, c.address, c.occupation 
                  FROM warrants w 
                  JOIN civilians c ON w.civilian_id = c.id 
                  WHERE CONCAT(c.first_name, ' ', c.last_name) LIKE ?";
        $search_query = "%$search_query%"; // Use wildcard for partial matches
    } else {
        echo '<div class="error">Invalid search type.</div>';
    }

    if (!empty($query)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $search_query);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $warrant = $result->fetch_assoc();
            } else {
                echo '<div class="error">No warrant found with the given criteria.</div>';
            }
        } else {
            echo '<div class="error">Error: ' . $stmt->error . '</div>';
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
    <title>Court System</title>
    <link rel="stylesheet" href="../styles.css"> <!-- Ensure to adjust the path to your stylesheet -->
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        nav {
            width: 200px;
            background-color: #333;
            color: white;
            padding: 15px;
        }
        nav ul {
            list-style-type: none;
            padding: 0;
        }
        nav ul li {
            margin: 15px 0;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
        }
        main {
            flex-grow: 1;
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main>
        <h2>Search Warrant</h2>
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div>
                <label for="search_query">Search:</label>
                <input type="text" id="search_query" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>">
                <select id="search_type" name="search_type">
                    <option value="warrant_id" <?php echo $search_type == 'warrant_id' ? 'selected' : ''; ?>>Warrant ID</option>
                    <option value="full_name" <?php echo $search_type == 'full_name' ? 'selected' : ''; ?>>Full Name</option>
                </select>
                <button type="submit">Search</button>
            </div>
        </form>

        <?php if ($warrant): ?>
            <div>
                <h3>Warrant ID: <?php echo htmlspecialchars($warrant['warrant_id']); ?></h3>
                <p><strong>Civilian Name:</strong> <?php echo htmlspecialchars($warrant['first_name'] . ' ' . $warrant['last_name']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($warrant['gender']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($warrant['dob']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($warrant['address']); ?></p>
                <p><strong>Occupation:</strong> <?php echo htmlspecialchars($warrant['occupation']); ?></p>
                <p><strong>Warrant Description:</strong> <?php echo htmlspecialchars($warrant['description']); ?></p>
                <p><strong>Reason for Warrant:</strong> <?php echo htmlspecialchars($warrant['reason']); ?></p>
                <p><strong>Issue Date:</strong> <?php echo htmlspecialchars($warrant['issue_date']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($warrant['status']); ?></p>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
