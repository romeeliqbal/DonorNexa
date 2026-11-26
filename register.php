<?php

require_once __DIR__ . '/config/db.php';

$cityQuery = "SELECT id, city_name, province FROM cities ORDER BY city_name";

$cityStmt = $pdo->query($cityQuery);

$cities = $cityStmt->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $city_id = (int) ($_POST['city_id'] ?? 0);

    $errors = [];

    // Basic validation
    if ($name === '') {
        $errors[] = "Name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!in_array($role, ['donor', 'hospital'], true)) {
        $errors[] = "Invalid account type.";
    }

    if ($city_id <= 0) {
        $errors[] = "Please select a valid city.";
    }

    // Donor validation
    if ($role === 'donor') {

        $blood_group = $_POST['blood_group'] ?? '';
        $date_of_birth = $_POST['date_of_birth'] ?? '';
        $weight = $_POST['weight'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if (!in_array($blood_group, ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], true)) {
            $errors[] = "Please select a valid blood group.";
        }

        if (empty($date_of_birth)) {
            $errors[] = "Date of birth is required.";
        }

        if (!is_numeric($weight) || $weight <= 0) {
            $errors[] = "Please enter a valid weight.";
        }

        if ($phone === '') {
            $errors[] = "Phone number is required.";
        }
    }

    // Hospital validation
    if ($role === 'hospital') {

        $hospital_name = trim($_POST['hospital_name'] ?? '');
        $licence_no = trim($_POST['licence_no'] ?? '');

        if ($hospital_name === '') {
            $errors[] = "Hospital name is required.";
        }

        if ($licence_no === '') {
            $errors[] = "Licence number is required.";
        }
    }

    if (!empty($errors)) {

        foreach ($errors as $error) {
            echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>";
        }

    } else {

        try {

            // Start database transaction
            $pdo->beginTransaction();

            // Create user account
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                    (name, email, password_hash, role, status)
                    VALUES
                    (:name, :email, :password_hash, :role, 'active')";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password_hash' => $hashedPassword,
                ':role' => $role
            ]);

            // Get newly created user ID
            $user_id = $pdo->lastInsertId();

            // Create donor profile
            if ($role === 'donor') {

                $sql = "INSERT INTO donors
                        (user_id, blood_group, date_of_birth, weight, city_id, phone)
                        VALUES
                        (:user_id, :blood_group, :date_of_birth, :weight, :city_id, :phone)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':user_id' => $user_id,
                    ':blood_group' => $blood_group,
                    ':date_of_birth' => $date_of_birth,
                    ':weight' => $weight,
                    ':city_id' => $city_id,
                    ':phone' => $phone
                ]);

            }

            // Create hospital profile
            if ($role === 'hospital') {

                $sql = "INSERT INTO hospitals
                        (user_id, hospital_name, licence_no, city_id)
                        VALUES
                        (:user_id, :hospital_name, :licence_no, :city_id)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ':user_id' => $user_id,
                    ':hospital_name' => $hospital_name,
                    ':licence_no' => $licence_no,
                    ':city_id' => $city_id
                ]);

            }

            // Save both records successfully
            $pdo->commit();

            echo "<p style='color:green;'>Registration successful!</p>";

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($e->getCode() == '23000') {
                echo "<p style='color:red;'>This email or licence number is already registered.</p>";
            } else {
                error_log($e->getMessage());
                echo "<p style='color:red;'>Something went wrong. Please try again.</p>";
            }

        }
    }
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DonorNexa - Registration</title>
    <link rel="stylesheet" href="/DonorNexa/assets/css/style.css">
</head>
<body class="register-page">

    <h1>Create Your DonorNexa Account</h1>

    <form action="" method="POST" class="register-form">

        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" required>
        <br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br><br>

        <label for="role">Account Type:</label>
        <select id="role" name="role" required>
            <option value="">Select account type</option>
            <option value="donor">Donor</option>
            <option value="hospital">Hospital</option>
        </select>
        <br><br>

        <div id="donor-fields">

<label for="blood_group">Blood Group:</label>

<select id="blood_group" name="blood_group" required>
    <option value="">Select blood group</option>
    <option value="A+">A+</option>
    <option value="A-">A-</option>
    <option value="B+">B+</option>
    <option value="B-">B-</option>
    <option value="AB+">AB+</option>
    <option value="AB-">AB-</option>
    <option value="O+">O+</option>
    <option value="O-">O-</option>
</select>

<br><br>

<label for="date_of_birth">Date of Birth:</label>
<input type="date" id="date_of_birth" name="date_of_birth" required>

<br><br>

<label for="weight">Weight (kg):</label>
<input type="number" id="weight" name="weight" step="0.01" min="1" required>

<br><br>

<label for="phone">Phone Number:</label>
<input type="tel" id="phone" name="phone" required>

</div>

<div id="hospital-fields">

    <label for="hospital_name">Hospital Name:</label>
    <input type="text" id="hospital_name" name="hospital_name">

    <br><br>

    <label for="licence_no">Licence Number:</label>
    <input type="text" id="licence_no" name="licence_no">

</div>

<label for="city_id">Select City:</label>

<select id="city_id" name="city_id" required>

    <option value="">Select your city</option>

    <?php foreach ($cities as $city): ?>

        <option value="<?php echo $city['id']; ?>">
            <?php echo htmlspecialchars($city['city_name'] . ', ' . $city['province']); ?>
        </option>

    <?php endforeach; ?>

</select>

        <button type="submit">Register</button>

    </form>
<p>Already have an account? <a href="login.php">Login here</a></p>
<script src="/DonorNexa/assets/js/script.js"></script>

</body>
</html>
