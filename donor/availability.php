
<?php
require_once "../auth.php";

// Only donors can access this page
if ($_SESSION["role"] !== "donor") {
    die("Access denied.");
}

// Get current availability
$stmt = $pdo->prepare("
    SELECT is_available
    FROM donors
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    die("Donor profile not found.");
}

$error = "";

// Update availability
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $availability = $_POST["availability"] ?? "";

    if (!in_array($availability, ["0", "1"], true)) {
        $error = "Please select a valid availability option.";
    } else {

        $updateStmt = $pdo->prepare("
            UPDATE donors
            SET is_available = ?
            WHERE user_id = ?
        ");

        $updateStmt->execute([
            (int)$availability,
            $_SESSION["user_id"]
        ]);

        header("Location: dashboard.php?availability_updated=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Availability - DonorNexa</title>

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

            <h1>Manage Availability</h1>

            <p class="edit-profile-subtitle">
                Let hospitals know whether you are currently available to donate blood.
            </p>

            <?php if ($error !== ""): ?>
                <div class="edit-profile-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="availability-options">

                    <label class="availability-option">
                        <input
                            type="radio"
                            name="availability"
                            value="1"
                            <?php echo $donor["is_available"] == 1 ? "checked" : ""; ?>
                        >
                        <span>
                            <strong>Available</strong>
                            <small>I am currently available to donate blood.</small>
                        </span>
                    </label>

                    <label class="availability-option">
                        <input
                            type="radio"
                            name="availability"
                            value="0"
                            <?php echo $donor["is_available"] == 0 ? "checked" : ""; ?>
                        >
                        <span>
                            <strong>Unavailable</strong>
                            <small>I am currently not available to donate blood.</small>
                        </span>
                    </label>

                </div>

                <button type="submit" class="edit-profile-button">
                    Save Availability
                </button>

                <a href="dashboard.php" class="edit-profile-cancel">
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
