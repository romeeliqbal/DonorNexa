
<?php
require_once "../auth.php";


if ($_SESSION["role"] !== "admin") {
    die("Access denied. You are not authorized to view this page.");
}

// Total registered users
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();

// Total donors
$stmt = $pdo->query("
    SELECT COUNT(*) FROM donors
");
$totalDonors = $stmt->fetchColumn();

// Total hospitals
$stmt = $pdo->query("
    SELECT COUNT(*) FROM hospitals
");
$totalHospitals = $stmt->fetchColumn();

// Total blood requests
$stmt = $pdo->query("
    SELECT COUNT(*) FROM blood_requests
");
$totalRequests = $stmt->fetchColumn();

// Total donations
$stmt = $pdo->query("
    SELECT COUNT(*) FROM donations
");
$totalDonations = $stmt->fetchColumn();

// Pending hospital verifications
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM hospitals
    WHERE verification_status = 'pending'
");
$pendingHospitals = $stmt->fetchColumn();
?>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-dashboard">

    
<!-- Navigation Bar -->
<nav class="admin-navbar">

    <div class="admin-nav-brand">
        Donor<span>Nexa</span>
        <small>ADMIN PANEL</small>
    </div>

    <div class="admin-nav-links">

        <a href="dashboard.php">Dashboard</a>

        <a href="../index.php">Home</a>

        <a href="manage-users.php">Manage Users</a>

        <a href="verify-hospitals.php">Verify Hospitals</a>

        <a href="manage-requests.php">Blood Requests</a>

        <a href="verify-donations.php">Verify Donations</a>

        <a href="../logout.php" class="admin-logout-btn">
            Logout
        </a>

    </div>

</nav>


    <!-- Main Dashboard -->
    <main class="admin-main">

        <!-- Welcome Section -->
        <section class="admin-welcome">

            <div>
                <p class="admin-welcome-label">ADMINISTRATION PORTAL</p>

                <h1>
                    Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!
                </h1>

                <p>
                    Manage and monitor the DonorNexa blood donation
                    coordination platform.
                </p>
            </div>

        </section>


<!-- Dashboard Statistics -->
<section class="admin-cards">

    <div class="admin-card">
        <div class="admin-card-icon">👥</div>
        <h3>Total Users</h3>
        <h2><?php echo (int)$totalUsers; ?></h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon">🩸</div>
        <h3>Total Donors</h3>
        <h2><?php echo (int)$totalDonors; ?></h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon">🏥</div>
        <h3>Total Hospitals</h3>
        <h2><?php echo (int)$totalHospitals; ?></h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon">📋</div>
        <h3>Blood Requests</h3>
        <h2><?php echo (int)$totalRequests; ?></h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon">💉</div>
        <h3>Total Donations</h3>
        <h2><?php echo (int)$totalDonations; ?></h2>
    </div>

    <div class="admin-card">
        <div class="admin-card-icon">⏳</div>
        <h3>Pending Verifications</h3>
        <h2><?php echo (int)$pendingHospitals; ?></h2>
    </div>

</section>


        <!-- Admin Cards -->
        <section class="admin-cards">

            <div class="admin-card">
    <div class="admin-card-icon">👥</div>
    <h3>User Management</h3>
    <p>Manage donor and hospital user accounts.</p>

    <a href="manage-users.php">
        Manage Users
    </a>
</div>

            

            <div class="admin-card">
    <div class="admin-card-icon">🏥</div>
    <h3>Hospital Verification</h3>
    <p>Review hospital registration and verification.</p>

    <a href="verify-hospitals.php">Verify Hospitals</a>
</div>
            

            <div class="admin-card">
                <div class="admin-card-icon">🩸</div>
                <h3>Blood Requests</h3>
                <p>Monitor blood requests submitted by hospitals.</p>

                <a href="manage-requests.php">
    Manage Blood Requests
</a>
            </div>

           <div class="admin-card">
    <div class="admin-card-icon">⚙️</div>
    <h3>System Administration</h3>

    <p>Oversee the DonorNexa platform.</p>

    <a href="verify-donations.php">
        Verify Donations
    </a>
</div>

        </section>

        <!-- Information Section -->
        <section class="admin-info-section">

            <div class="admin-info-box">
                <h2>Platform Management</h2>

                <p>
                    The administrator is responsible for monitoring
                    platform activities, managing accounts, and
                    maintaining the reliability of DonorNexa.
                </p>
            </div>

            <div class="admin-info-box">
                <h2>Administrator Account</h2>

                <p>
                    You are successfully logged in as an administrator.
                    Additional management tools and reports will be
                    developed in upcoming phases.
                </p>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p>&copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.</p>
    </footer>

</body>
</html>
