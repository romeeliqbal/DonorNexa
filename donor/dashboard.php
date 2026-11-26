
<?php
require_once "../auth.php";

// Only donors can access this page
if ($_SESSION["role"] !== "donor") {
    die("Access denied.");
}

// Get donor availability
$stmt = $pdo->prepare("
    SELECT is_available
    FROM donors
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);

$donorStatus = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donorStatus) {
    die("Donor profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Dashboard - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="donor-dashboard">

    <!-- Navigation Bar -->
    
<nav class="donor-navbar">

    <div class="donor-nav-links">

        <a href="dashboard.php">Dashboard</a>

        <a href="../index.php">Home</a>

        <a href="profile.php">My Profile</a>

        <a href="availability.php">Manage Availability</a>

        <a href="notifications.php">Notifications</a>

        <a href="../logout.php" class="donor-logout-btn">
            Logout
        </a>

    </div>

</nav>


    <!-- Main Dashboard -->
    <main class="donor-main">

        <!-- Welcome Section -->
        <section class="donor-welcome">

            <div>

                <p class="donor-welcome-label">
                    DONOR PORTAL
                </p>

                <h1>
                    Welcome,
                    <?php echo htmlspecialchars($_SESSION["name"]); ?>!
                </h1>

                <p>
                    Thank you for being a part of our blood donation community.
                </p>

                <?php if (isset($_GET["availability_updated"])): ?>

                    <div class="availability-success">
                        Your availability status has been updated successfully!
                    </div>

                <?php endif; ?>

            </div>

        </section>

        <!-- Dashboard Cards -->
        <section class="donor-cards">

            <div class="donor-card">

                <div class="donor-card-icon">🩸</div>

                <h3>Blood Donation</h3>

                <p>Your donation can help save lives.</p>

            </div>

            <div class="donor-card">

                <div class="donor-card-icon">🤝</div>

                <h3>Community</h3>

                <p>You are part of a community of donors.</p>

            </div>

            <div class="donor-card">

                <div class="donor-card-icon">❤️</div>

                <h3>Make a Difference</h3>

                <p>Your willingness to donate matters.</p>

            </div>

            <!-- Donation Availability Card -->
            <div class="donor-card">

                <div class="donor-card-icon">🩸</div>

                <h3>Donation Availability</h3>

                <p>
                    Current Status:

                    <strong>
                        <?php
                        echo $donorStatus["is_available"] == 1
                            ? "Available"
                            : "Unavailable";
                        ?>
                    </strong>
                </p>

                <a href="availability.php">
                    Manage Status
                </a>

            </div>

        </section>

        <!-- Information Section -->
        <section class="donor-info-section">

            <div class="donor-info-box">

                <h2>About Blood Donation</h2>

                <p>
                    Blood donation is an important way to help patients
                    who need blood during medical treatment and emergencies.
                    Donors make a valuable contribution to their communities.
                </p>

            </div>

            <div class="donor-info-box">

                <h2>Your Donor Account</h2>

                <p>
                    You are logged in successfully as a donor.
                    More donor profile and donation features will be
                    available as we continue building DonorNexa.
                </p>

            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="donor-footer">

        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>

    </footer>

</body>
</html>
