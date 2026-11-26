
<?php
require_once "../auth.php";

// Only donors can access this page
if ($_SESSION["role"] !== "donor") {
    die("Access denied.");
}

$error = "";
$success = "";

// Get all available cities
$cityStmt = $pdo->query("
    SELECT id, city_name, province
    FROM cities
    ORDER BY city_name
");

$cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);

// Get current donor information
$stmt = $pdo->prepare("
    SELECT phone, city_id
    FROM donors
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    die("Donor profile not found.");
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone = trim($_POST["phone"] ?? "");
    $city_id = $_POST["city_id"] ?? "";

    if ($phone === "" || $city_id === "") {
        $error = "Please complete all fields.";
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $error = "Please enter a valid phone number.";
    } else {

        // Check whether selected city exists
        $cityCheck = $pdo->prepare("
            SELECT id FROM cities WHERE id = ?
        ");

        $cityCheck->execute([$city_id]);

        if (!$cityCheck->fetch()) {
            $error = "Please select a valid city.";
        } else {

            // Update donor information
            $updateStmt = $pdo->prepare("
                UPDATE donors
                SET phone = ?, city_id = ?
                WHERE user_id = ?
            ");

            $updateStmt->execute([
                $phone,
                $city_id,
                $_SESSION["user_id"]
            ]);

            header("Location: profile.php?updated=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="edit-profile-page">

    <nav class="profile-navbar">
        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" class="profile-logout">Logout</a>
        </div>
    </nav>

    <main class="edit-profile-main">

        <section class="edit-profile-card">

            <h1>Edit Donor Profile</h1>

            <p class="edit-profile-subtitle">
                Update your contact information and city.
            </p>

            <?php if ($error !== ""): ?>
                <div class="edit-profile-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="edit-form-group">
                    <label for="phone">Phone Number</label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST["phone"] ?? $donor["phone"]
                            );
                        ?>"
                        required
                    >
                </div>

                <div class="edit-form-group">
                    <label for="city_id">City</label>

                    <select id="city_id" name="city_id" required>

                        <option value="">Select your city</option>

                        <?php foreach ($cities as $city): ?>

                            <option
                                value="<?php echo $city["id"]; ?>"
                                <?php
                                $selectedCity = $_POST["city_id"]
                                    ?? $donor["city_id"];

                                echo ((string)$selectedCity ===
                                    (string)$city["id"])
                                    ? "selected"
                                    : "";
                                ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $city["city_name"] . ", " .
                                    $city["province"]
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <button type="submit" class="edit-profile-button">
                    Save Changes
                </button>

                <a href="profile.php" class="edit-profile-cancel">
                    Cancel
                </a>

            </form>

        </section>

    </main>

    <footer class="profile-footer">
        <p>&copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.</p>
    </footer>

</body>
</html>
