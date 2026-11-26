
<?php
require_once "../auth.php";

// Check whether the logged-in user is a hospital
if ($_SESSION["role"] !== "hospital") {
    die("Access denied. You are not authorized to view this page.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="hospital-dashboard">

    <!-- Navigation Bar -->
    <nav class="hospital-navbar">
        <div class="hospital-nav-brand">
            Donor<span>Nexa</span>
        </div>

       
<div class="hospital-nav-links">
    <a href="dashboard.php">Dashboard</a>
    <a href="../index.php">Home</a>
    <a href="create-request.php">Create Blood Request</a>
    <a href="my-requests.php">My Blood Requests</a>
    <a href="donor-search.php">Donor Search</a>
    <a href="record-donation.php">Record Donation</a>
    <a href="inventory.php">Blood Inventory</a>
    <a href="../logout.php" class="hospital-logout-btn">Logout</a>
</div>
</nav>

    <!-- Main Dashboard -->
    <main class="hospital-main">

        <!-- Welcome Section -->
        <section class="hospital-welcome">
            <div>
                <p class="hospital-welcome-label">HOSPITAL PORTAL</p>

                <h1>
                    Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!
                </h1>

                <p>
                    Manage your hospital's blood requests and help connect
                    patients with blood donors.
                </p>
            </div>
        </section>

        <!-- Dashboard Cards -->
        <section class="hospital-cards">

            <div class="hospital-card">
                <div class="hospital-card-icon">🩸</div>
                <h3>Blood Requests</h3>
                <p>Create and manage blood requests for patients in need.</p>
            </div>

            <div class="hospital-card">
                <div class="hospital-card-icon">🏥</div>
                <h3>Hospital Verification</h3>
                <p>Your hospital account verification information.</p>
            </div>

            <div class="hospital-card">
                <div class="hospital-card-icon">📦</div>
                <h3>Blood Inventory</h3>
                <p>Keep track of blood units available at your hospital.</p>
            </div>

        </section>

        <!-- Information Sections -->
        <section class="hospital-info-section">

            <div class="hospital-info-box">
                <h2>Emergency Blood Requests</h2>

                <p>
                    Blood requests help hospitals connect with suitable
                    donors when patients need blood. Request management
                    features will be added in upcoming development phases.
                </p>
            </div>

            <div class="hospital-info-box">
                <h2>Hospital Account</h2>

                <p>
                    You are successfully logged in to the DonorNexa
                    hospital portal. Hospital profile, verification,
                    and management tools will be developed further.
                </p>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="hospital-footer">
        <p>&copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.</p>
    </footer>

</body>
</html>
