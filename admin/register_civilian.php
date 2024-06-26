<?php
include '../includes/auth.php'; // Ensure authentication
include '../includes/db.php';  // Include database connection
include '../includes/header.php'; // Include header

// Check if user is authorized as admin or moderator
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'moderator') {
    echo "Access denied!";
    include '../includes/footer.php';
    exit();
}

// Initialize variables for form inputs
$username = $password = $first_name = $last_name = $occupation = $gender = $address = $phone = $email = $dob = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $occupation = mysqli_real_escape_string($conn, $_POST['occupation']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $dob = mysqli_real_escape_string($conn, $_POST['dob']);
    $person_code = generate_person_code($dob); // Generate person code based on DOB

    // Simple validation
    if (empty($username)) { $errors[] = 'Username is required'; }
    if (empty($password)) { $errors[] = 'Password is required'; }
    if (empty($first_name)) { $errors[] = 'First name is required'; }
    if (empty($last_name)) { $errors[] = 'Last name is required'; }
    if (empty($occupation)) { $errors[] = 'Occupation is required'; }
    if (empty($gender)) { $errors[] = 'Gender is required'; }
    if (empty($address)) { $errors[] = 'Address is required'; }
    if (empty($phone)) { $errors[] = 'Phone is required'; }
    if (empty($email)) { $errors[] = 'Email is required'; }
    if (empty($dob)) { $errors[] = 'Date of birth is required'; }

    // If no errors, insert into database
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert query
        $insert_query = "INSERT INTO civilians (username, password, first_name, last_name, gender, email, phone, address, occupation, dob, person_code)
                        VALUES ('$username', '$hashed_password', '$first_name', '$last_name', '$gender', '$email', '$phone', '$address', '$occupation', '$dob', '$person_code')";

        if ($conn->query($insert_query) === TRUE) {
            echo '<div class="success">Civilian registered successfully!</div>';
            // Clear form inputs after successful submission
            $username = $password = $first_name = $last_name = $occupation = $gender = $address = $phone = $email = $dob = '';
        } else {
            echo '<div class="error">Error: ' . $conn->error . '</div>';
        }
    }
}

// Function to generate person code based on date of birth
function generate_person_code($dob) {
    $date_parts = explode('-', $dob);
    $year = substr($date_parts[0], -2);
    $month = $date_parts[1];
    $day = $date_parts[2];
    $random_part = mt_rand(1000, 9999);
    return "$year$month$day-$random_part";
}
?>

<main>
    <h2>Register Civilian</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" value="<?php echo htmlspecialchars($password); ?>">
        </div>
        <div>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
        </div>
        <div>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
        </div>
        <div>
            <label for="gender">Gender:</label>
            <select id="gender" name="gender">
                <option value="male" <?php echo ($gender === 'male') ? 'selected' : ''; ?>>Male</option>
                <option value="female" <?php echo ($gender === 'female') ? 'selected' : ''; ?>>Female</option>
                <option value="other" <?php echo ($gender === 'other') ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        <div>
            <label for="occupation">Occupation:</label>
            <input type="text" id="occupation" name="occupation" value="<?php echo htmlspecialchars($occupation); ?>">
        </div>
        <div>
            <label for="phone">Phone:</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
        </div>
        <div>
            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div>
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>">
        </div>
        <div>
            <button type="submit">Register</button>
        </div>
    </form>

    <?php
    // Display errors if any
    if (!empty($errors)) {
        echo '<div class="error"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    ?>
</main>

<?php include '../includes/footer.php'; ?>
