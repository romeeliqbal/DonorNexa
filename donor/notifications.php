
<?php
require_once "../auth.php";

// Only donors can access this page
if ($_SESSION["role"] !== "donor") {
    die("Access denied.");
}


// Handle donor response to a blood request

// Handle donor response to a blood request
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["respond_to_request"])
) {
    $matchId = (int) $_POST["match_id"];
    $response = $_POST["response"];

    if (!in_array($response, ["accepted", "declined"], true)) {
        die("Invalid response.");
    }

    $responseStmt = $pdo->prepare("
        UPDATE request_matches AS rm
        INNER JOIN donors AS d
            ON rm.donor_id = d.id
        SET rm.status = ?
        WHERE rm.id = ?
          AND d.user_id = ?
          AND rm.status IN ('matched', 'notified')
    ");

    $responseStmt->execute([
        $response,
        $matchId,
        $_SESSION["user_id"]
    ]);

    if ($responseStmt->rowCount() > 0) {
        header("Location: notifications.php?response=success");
    } else {
        header("Location: notifications.php?response=unchanged");
    }

    exit;
}

// Mark notification as read
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["mark_read"])
) {
    $notificationId = (int) $_POST["notification_id"];

    $updateStmt = $pdo->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE id = ? AND user_id = ?
    ");

    $updateStmt->execute([
        $notificationId,
        $_SESSION["user_id"]
    ]);

    header("Location: notifications.php");
    exit;
}

// Get notifications for the logged-in donor
$stmt = $pdo->prepare("
   SELECT
    notifications.id,
    notifications.message,
    notifications.is_read,
    notifications.created_at,
    request_matches.id AS match_id,
    request_matches.status AS match_status,
    request_matches.request_id
FROM notifications

LEFT JOIN request_matches
    ON notifications.message LIKE CONCAT(
        '%request #',
        request_matches.request_id,
        '.%'
    )

WHERE notifications.user_id = ?

ORDER BY notifications.created_at DESC
");

$stmt->execute([$_SESSION["user_id"]]);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Notifications - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="donor-dashboard">

    <nav class="donor-navbar">

        <div class="donor-nav-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="donor-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="profile.php">My Profile</a>
            <a href="availability.php">Manage Availability</a>
            <a href="../logout.php" class="donor-logout-btn">Logout</a>
        </div>

    </nav>

    <main class="donor-main">

        <section class="donor-welcome">
            <div>
                <p class="donor-welcome-label">DONOR PORTAL</p>
                <h1>My Notifications</h1>
                <p>Stay updated about blood requests that match your blood group.</p>
            </div>
        </section>

        <section class="donor-info-section">

            <div class="donor-info-box">

                <h2>Your Notifications</h2>

                <?php if (empty($notifications)): ?>

                    <p>You don't have any notifications yet.</p>

                <?php else: ?>

                    <?php foreach ($notifications as $notification): ?>

                        <div class="donor-card">

                            <h3>
                                <?php echo $notification["is_read"] == 0
                                    ? "New Notification"
                                    : "Notification"; ?>
                            </h3>

                            <p>
                                <?php echo htmlspecialchars($notification["message"]); ?>
                            </p>

                            <small>
                                <?php echo htmlspecialchars($notification["created_at"]); ?>
                            </small>

                            <?php if ($notification["is_read"] == 0): ?>

    <form method="POST">

        <input type="hidden" name="notification_id"
            value="<?php echo (int)$notification["id"]; ?>">

        <button type="submit" name="mark_read"
            class="request-edit-btn">
            Mark as Read
        </button>

    </form>

<?php else: ?>

    <p><strong>Read</strong></p>

<?php endif; ?>

                        </div>


<?php if (
    !empty($notification["match_id"]) &&
    in_array($notification["match_status"], ["matched", "notified"], true)
): ?>

    <form method="POST" style="margin-top: 15px;">

        <input type="hidden" name="match_id"
            value="<?php echo (int)$notification["match_id"]; ?>">

        <button type="submit"
            name="respond_to_request"
            value="1"
            onclick="this.form.response.value='accepted';"
            class="request-edit-btn">
            Accept Request
        </button>

        <button type="submit"
            name="respond_to_request"
            value="1"
            onclick="this.form.response.value='declined';"
            class="request-edit-btn">
            Decline Request
        </button>

        <input type="hidden" name="response" value="">

    </form>

<?php elseif (!empty($notification["match_status"])): ?>

    <p>
        **Response Status:**
        <?php echo htmlspecialchars(ucfirst($notification["match_status"])); ?>
    </p>

<?php endif; ?>


                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </section>

    </main>

    <footer class="donor-footer">
        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>
    </footer>

</body>
</html>
