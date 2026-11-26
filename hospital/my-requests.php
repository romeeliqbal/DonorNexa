
<?php
require_once "../auth.php";

// Only hospitals can access this page
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get logged-in hospital ID
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

// Get all blood requests submitted by this hospital
$requestStmt = $pdo->prepare("
    SELECT
        id,
        blood_group,
        units_needed,
        units_fulfilled,
        urgency,
        required_by,
        ward,
        notes,
        status,
        created_at
    FROM blood_requests
    WHERE hospital_id = ?
    ORDER BY created_at DESC
");

$requestStmt->execute([$hospital["id"]]);

$requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Blood Requests - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="hospital-requests-page">

    <!-- Navigation Bar -->
    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">

            <a href="dashboard.php">Dashboard</a>

            <a href="create-request.php">Create Request</a>

            <a href="../logout.php" class="profile-logout">
                Logout
            </a>

        </div>

    </nav>

    <!-- Main Content -->
    <main class="hospital-requests-main">

        <section class="hospital-requests-header">

            <h1>My Blood Requests</h1>

            <p>
                View and track all blood requests submitted by your hospital.
            </p>

        </section>

        <?php if (isset($_GET["request_cancelled"])): ?>

    <div class="request-message request-message-success">
        Blood request cancelled successfully.
    </div>

<?php elseif (isset($_GET["request_updated"])): ?>

    <div class="request-message request-message-success">
        Blood request updated successfully.
    </div>

<?php elseif (isset($_GET["cancel_error"])): ?>

    <div class="request-message request-message-error">
        Unable to cancel this request. It may already be closed or have fulfilled units.
    </div>

<?php endif; ?>

        <!-- Hospital Information -->
        <div class="hospital-requests-info">

            <h3>
                <?php echo htmlspecialchars($hospital["hospital_name"]); ?>
            </h3>

            <p>
                Total Requests:
                <strong><?php echo count($requests); ?></strong>
            </p>

        </div>

        <!-- Requests List -->
        <section class="hospital-requests-list">

            <?php if (count($requests) > 0): ?>

                <?php foreach ($requests as $request): ?>

                    <div class="hospital-request-card">

                        <div class="request-card-header">

                            <div>

                                <h2>
                                    Request #<?php echo (int)$request["id"]; ?>
                                </h2>

                                <p class="request-created-date">
                                    Submitted:
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

                            <span class="request-status status-<?php
                                echo htmlspecialchars($request["status"]);
                            ?>">
                                <?php
                                echo htmlspecialchars(
                                    ucfirst($request["status"])
                                );
                                ?>
                            </span>

                        </div>

                        <div class="request-card-details">

                            <div class="request-detail">

                                <span>Blood Group</span>

                                <strong>
                                    <?php echo htmlspecialchars($request["blood_group"]); ?>
                                </strong>

                            </div>

                            <div class="request-detail">

                                <span>Units Needed</span>

                                <strong>
                                    <?php echo (int)$request["units_needed"]; ?>
                                </strong>

                            </div>

                            <div class="request-detail">

                                <span>Units Fulfilled</span>

                                <strong>
                                    <?php echo (int)$request["units_fulfilled"]; ?>
                                </strong>

                            </div>

                            <div class="request-detail">

                                <span>Urgency</span>

                                <strong>
                                    <?php echo htmlspecialchars(ucfirst($request["urgency"])); ?>
                                </strong>

                            </div>

                            <div class="request-detail">

                                <span>Ward / Department</span>

                                <strong>
                                    <?php echo htmlspecialchars($request["ward"]); ?>
                                </strong>

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

                        </div>

                        <div class="request-remaining">

                            Remaining Units:

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

                        <?php if (!empty($request["notes"])): ?>

                            <div class="request-notes">

                                <strong>Additional Notes:</strong>

                                <p>
                                    <?php echo nl2br(htmlspecialchars($request["notes"])); ?>
                                </p>

                            </div>

                        <?php endif; ?>

                        <div class="request-card-actions">

                        <?php if ($request["status"] === "open"): ?>

    <a
        href="match-donors.php?id=<?php echo (int)$request["id"]; ?>"
        class="request-edit-btn"
    >
        Find Matches
    </a>

<?php endif; ?>

    <a
        href="view-request.php?id=<?php echo (int)$request["id"]; ?>"
        class="request-view-btn"
    >
        View Details
    </a>

    <a
    href="view-responses.php?id=<?php echo (int)$request["id"]; ?>"
    class="request-edit-btn"
>
    View Donor Responses
</a>

    <?php if ($request["status"] === "open"): ?>

        <a
            href="edit-request.php?id=<?php echo (int)$request["id"]; ?>"
            class="request-edit-btn"
        >
            Edit Request
        </a>

        <form
            method="POST"
            action="cancel-request.php"
            onsubmit="return confirm('Are you sure you want to cancel this blood request?');"
        >

            <input
                type="hidden"
                name="request_id"
                value="<?php echo (int)$request["id"]; ?>"
            >

            <button type="submit" class="request-cancel-btn">
                Cancel Request
            </button>

        </form>

    <?php endif; ?>

</div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="no-requests-message">

                    <h2>No Blood Requests Yet</h2>

                    <p>
                        Your hospital has not submitted any blood requests.
                    </p>

                    <a href="create-request.php">
                        Create Your First Request
                    </a>

                </div>

                
            <?php endif; ?>

        </section>

    </main>

    <!-- Footer -->
    <footer class="profile-footer">

        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>

    </footer>

</body>
</html>
