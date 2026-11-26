
<?php
require_once "../auth.php";

// Only hospitals can access this page
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get logged-in hospital
$stmt = $pdo->prepare("
    SELECT id, hospital_name
    FROM hospitals
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital profile not found.");
}

// Get request ID
$requestId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$requestId) {
    die("Invalid request ID.");
}

// Retrieve request belonging to this hospital
$stmt = $pdo->prepare("
    SELECT *
    FROM blood_requests
    WHERE id = ? AND hospital_id = ?
");

$stmt->execute([$requestId, $hospital["id"]]);

$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request not found or access denied.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Request Details - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="hospital-requests-page">

    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">

            <a href="dashboard.php">Dashboard</a>

            <a href="my-requests.php">My Requests</a>

            <a href="../logout.php" class="profile-logout">Logout</a>

        </div>

    </nav>

    <main class="hospital-requests-main">

        <section class="hospital-requests-header">

            <h1>Blood Request Details</h1>

            <p>
                Complete information about your submitted blood request.
            </p>

        </section>

        <div class="hospital-request-card">

            <div class="request-card-header">

                <div>

                    <h2>
                        Request #<?php echo (int)$request["id"]; ?>
                    </h2>

                    <p class="request-created-date">
                        <?php
                        echo htmlspecialchars(
                            date(
                                "d M Y, h:i A",
                                strtotime($request["created_at"])
                            )
                        );
                        ?>
                    </p>

                </div>

                <span class="request-status status-<?php echo htmlspecialchars($request["status"]); ?>">
                    <?php echo htmlspecialchars(ucfirst($request["status"])); ?>
                </span>

            </div>

            <div class="request-card-details">

                <div class="request-detail">
                    <span>Hospital</span>
                    <strong><?php echo htmlspecialchars($hospital["hospital_name"]); ?></strong>
                </div>

                <div class="request-detail">
                    <span>Blood Group</span>
                    <strong><?php echo htmlspecialchars($request["blood_group"]); ?></strong>
                </div>

                <div class="request-detail">
                    <span>Units Needed</span>
                    <strong><?php echo (int)$request["units_needed"]; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Units Fulfilled</span>
                    <strong><?php echo (int)$request["units_fulfilled"]; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Urgency</span>
                    <strong><?php echo htmlspecialchars(ucfirst($request["urgency"])); ?></strong>
                </div>

                <div class="request-detail">
                    <span>Ward</span>
                    <strong><?php echo htmlspecialchars($request["ward"]); ?></strong>
                </div>

                <div class="request-detail">
                    <span>Required By</span>
                    <strong>
                        <?php
                        echo htmlspecialchars(
                            date(
                                "d M Y, h:i A",
                                strtotime($request["required_by"])
                            )
                        );
                        ?>
                    </strong>
                </div>

                <div class="request-detail">
                    <span>Remaining Units</span>
                    <strong>
                        <?php
                        echo max(
                            0,
                            (int)$request["units_needed"] -
                            (int)$request["units_fulfilled"]
                        );
                        ?>
                    </strong>
                </div>

            </div>

            <div class="request-notes">

                <strong>Additional Notes</strong>

                <p>
                    <?php
                    echo !empty($request["notes"])
                        ? nl2br(htmlspecialchars($request["notes"]))
                        : "No additional notes provided.";
                    ?>
                </p>

            </div>

            <a href="my-requests.php" class="request-view-btn">
                Back to My Requests
            </a>

        </div>

    </main>

    <footer class="profile-footer">

        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>

    </footer>

</body>
</html>
