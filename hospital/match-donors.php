<?php
require_once "../auth.php";

// Only hospitals can access matching
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get logged-in hospital
$hospitalStmt = $pdo->prepare("
    SELECT id, hospital_name, city_id
    FROM hospitals
    WHERE user_id = ?
");

$hospitalStmt->execute([$_SESSION["user_id"]]);
$hospital = $hospitalStmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital profile not found.");
}

// Get request ID
$requestId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$requestId) {
    die("Invalid request ID.");
}

// Retrieve only this hospital's request
$requestStmt = $pdo->prepare("
    SELECT *
    FROM blood_requests
    WHERE id = ? AND hospital_id = ?
");

$requestStmt->execute([$requestId, $hospital["id"]]);
$request = $requestStmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request not found or access denied.");
}

// Matching only works for open requests
if ($request["status"] !== "open") {
    die("Donor matching is only available for open requests.");
}

// Donor blood groups compatible with the requested recipient group
$compatibility = [
    "A+"  => ["A+", "A-", "O+", "O-"],
    "A-"  => ["A-", "O-"],
    "B+"  => ["B+", "B-", "O+", "O-"],
    "B-"  => ["B-", "O-"],
    "AB+" => ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"],
    "AB-" => ["A-", "B-", "AB-", "O-"],
    "O+"  => ["O+", "O-"],
    "O-"  => ["O-"]
];

// Get compatible groups for this request
$compatibleGroups = $compatibility[$request["blood_group"]] ?? [];

// Prepare placeholders for SQL IN clause
$placeholders = implode(",", array_fill(0, count($compatibleGroups), "?"));

// Find available compatible donors
$sql = "
       SELECT
    donors.id AS donor_id,
donors.user_id AS donor_user_id,
    donors.city_id AS donor_city_id,
    users.name,
    donors.blood_group,
    donors.phone,
    cities.city_name,
    cities.province
    FROM donors
    INNER JOIN users
        ON donors.user_id = users.id
    INNER JOIN cities
        ON donors.city_id = cities.id
    WHERE donors.is_available = 1
      AND users.status = 'active'
      AND donors.blood_group IN ($placeholders)
    ORDER BY
        (donors.city_id = ?) DESC,
        users.name ASC
";

$params = array_merge($compatibleGroups, [$hospital["city_id"]]);

$matchStmt = $pdo->prepare($sql);
$matchStmt->execute($params);

$matchedDonors = $matchStmt->fetchAll(PDO::FETCH_ASSOC);
// Save compatible donors into request_matches
$saveMessage = "";
$saveError = "";

// Create CSRF token if it doesn't exist
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Process save matches form

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["save_matches"])) {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $saveError = "Invalid security token. Please refresh the page.";
    } else {

        try {
            $pdo->beginTransaction();

            $checkStmt = $pdo->prepare("
                SELECT id
                FROM request_matches
                WHERE request_id = ? AND donor_id = ?
            ");

            $saveStmt = $pdo->prepare("
                INSERT INTO request_matches
                    (request_id, donor_id, score, status)
                VALUES (?, ?, ?, 'matched')
            ");

            $notificationStmt = $pdo->prepare("
                INSERT INTO notifications
                    (user_id, message, is_read)
                VALUES (?, ?, 0)
            ");

            $newMatches = 0;
            $newNotifications = 0;

            foreach ($matchedDonors as $donor) {

                $score = (
                    (int)$donor["donor_city_id"] ===
                    (int)$hospital["city_id"]
                ) ? 100 : 50;

                // Check whether this donor is already matched
                $checkStmt->execute([
                    $request["id"],
                    $donor["donor_id"]
                ]);

                if ($checkStmt->fetch()) {
                    continue;
                }

                // Save the donor match
                $saveStmt->execute([
                    $request["id"],
                    $donor["donor_id"],
                    $score
                ]);

                $newMatches++;

                // Create a notification for the donor
                $message = "A hospital has created a new blood request for "
                    . $request["blood_group"]
                    . ". Your blood group matches request #"
                    . $request["id"]
                    . ". Please log in to view the request.";

                $notificationStmt->execute([
                    $donor["donor_user_id"],
                    $message
                ]);

                $newNotifications++;
            }

            $pdo->commit();

            $saveMessage = "Successfully saved "
                . $newMatches
                . " new matches and created "
                . $newNotifications
                . " donor notifications.";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $saveError = "Unable to save matches or notifications: "
                . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Matches - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="hospital-requests-page">

    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="create-request.php">Create Request</a>
            <a href="my-requests.php">My Requests</a>
            <a href="donor-search.php">Donor Search</a>
            <a href="../logout.php" class="profile-logout">Logout</a>
        </div>

    </nav>

    <main class="hospital-requests-main">

        <section class="hospital-requests-header">

            <h1>Donor Matching Results</h1>

            <p>
                Compatible available donors for your blood request.
            </p>

        </section>

        <!-- Request Information -->
        <div class="hospital-request-card">

            <h2>Request #<?php echo (int)$request["id"]; ?></h2>

            <div class="request-card-details">

                <div class="request-detail">
                    <span>Requested Blood Group</span>
                    <strong class="donor-search-blood-group">
                        <?php echo htmlspecialchars($request["blood_group"]); ?>
                    </strong>
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
                    <span>Hospital</span>
                    <strong><?php echo htmlspecialchars($hospital["hospital_name"]); ?></strong>
                </div>

            </div>

        </div>

        <!-- Donor Results -->
        <section class="donor-search-results">

            <h2>Compatible Donors (<?php echo count($matchedDonors); ?>)</h2>

            <?php if (!empty($matchedDonors)): ?>

                <?php foreach ($matchedDonors as $donor): ?>

                    <div class="hospital-request-card donor-search-card">

                        <h3><?php echo htmlspecialchars($donor["name"]); ?></h3>

                        <div class="request-card-details">

                            <div class="request-detail">
                                <span>Blood Group</span>
                                <strong class="donor-search-blood-group">
                                    <?php echo htmlspecialchars($donor["blood_group"]); ?>
                                </strong>
                            </div>

                            <div class="request-detail">
                                <span>City</span>
                                <strong>
                                    <?php echo htmlspecialchars($donor["city_name"] . ", " . $donor["province"]); ?>
                                </strong>
                            </div>

                            <div class="request-detail">
                                <span>Phone</span>
                                <strong>
                                    <?php echo htmlspecialchars($donor["phone"]); ?>
                                </strong>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="hospital-request-card">
                    <p>No compatible available donors were found.</p>
                </div>

            <?php endif; ?>

        </section>

        <!-- Save Matches Feedback -->

<?php if ($saveMessage !== ""): ?>

    <div class="request-message request-message-success">
        <?php echo htmlspecialchars($saveMessage); ?>
    </div>

<?php endif; ?>

<?php if ($saveError !== ""): ?>

    <div class="request-message request-message-error">
        <?php echo htmlspecialchars($saveError); ?>
    </div>

<?php endif; ?>


<!-- Save Matches Form -->

<?php if (!empty($matchedDonors)): ?>

    <form method="POST" style="margin: 20px 0;">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <button
            type="submit"
            name="save_matches"
            class="request-edit-btn"
        >
            Save Compatible Donors
        </button>

    </form>

<?php endif; ?>

        <a href="my-requests.php" class="request-view-btn">
            Back to My Requests
        </a>

    </main>

</body>
</html>