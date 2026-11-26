
<?php
require_once "../auth.php";

// Only donors can access this page
if ($_SESSION["role"] !== "donor") {
    die("Access denied.");
}

// Get donor information from database
$stmt = $pdo->prepare("
    SELECT
        u.name,
        u.email,
        d.blood_group,
        d.date_of_birth,
        d.weight,
        d.phone,
        d.last_donation_date,
        d.is_available,
        c.city_name,
        c.province
    FROM users u
    INNER JOIN donors d ON u.id = d.user_id
    LEFT JOIN cities c ON d.city_id = c.id
    WHERE u.id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    die("Donor profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="profile-page">

    <nav class="profile-navbar">
        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="../logout.php" class="profile-logout">Logout</a>
        </div>
    </nav>

    <main class="profile-main">

        <section class="profile-header">
            <h1>My Donor Profile</h1>
            <p>View your personal and donor information.</p>
        </section>

        <section class="profile-card">

            <div class="profile-avatar">👤</div>

            <h2>
                <?php echo htmlspecialchars($donor["name"]); ?>
            </h2>

            <p class="profile-role">Registered Blood Donor</p>

            <div class="profile-details">

                <div class="profile-detail">
                    <span>Full Name</span>
                    <strong><?php echo htmlspecialchars($donor["name"]); ?></strong>
                </div>

                <div class="profile-detail">
                    <span>Email Address</span>
                    <strong><?php echo htmlspecialchars($donor["email"]); ?></strong>
                </div>

                <div class="profile-detail">
                    <span>Blood Group</span>
                    <strong><?php echo htmlspecialchars($donor["blood_group"]); ?></strong>
                </div>

                <div class="profile-detail">
                    <span>Phone Number</span>
                    <strong><?php echo htmlspecialchars($donor["phone"]); ?></strong>
                </div>

                <div class="profile-detail">
                    <span>Date of Birth</span>
                    <strong><?php echo htmlspecialchars($donor["date_of_birth"]); ?></strong>
                </div>

                <div class="profile-detail">
                    <span>Weight</span>
                    <strong>
                        <?php echo htmlspecialchars($donor["weight"] ?? "Not provided"); ?>
                        <?php echo $donor["weight"] !== null ? " kg" : ""; ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>City</span>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $donor["city_name"] ?? "Not provided"
                        );
                        ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>Province</span>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $donor["province"] ?? "Not provided"
                        );
                        ?>
                    </strong>
                </div>

                <div class="profile-detail">
                    <span>Last Donation Date</span>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $donor["last_donation_date"] ?? "No donation recorded"
                        );
                        ?>
                    </strong>
                </div>

            </div>

          <?php if (isset($_GET["updated"])): ?>
    <div class="profile-success">
        Profile updated successfully!
    </div>
<?php endif; ?>

<a href="edit-profile.php" class="profile-edit-btn">
    Edit Profile
</a>

<a href="dashboard.php" class="profile-back-btn">
    Back to Dashboard
</a>

        </section>

    </main>

    <footer class="profile-footer">
        <p>&copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.</p>
    </footer>

</body>
</html>
