
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

// Verify that this request belongs to this hospital
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

// Get matched donors and their responses
$stmt = $pdo->prepare("
    SELECT
        request_matches.id AS match_id,
        request_matches.score,
        request_matches.status,
        request_matches.notified_at,
        request_matches.responded_at,
        users.name AS donor_name,
        donors.blood_group,
        donors.phone,
        cities.city_name,
        cities.province
    FROM request_matches

    INNER JOIN donors
        ON request_matches.donor_id = donors.id

    INNER JOIN users
        ON donors.user_id = users.id

    INNER JOIN cities
        ON donors.city_id = cities.id

    WHERE request_matches.request_id = ?

    ORDER BY
        FIELD(
            request_matches.status,
            'accepted',
            'declined',
            'notified',
            'matched',
            'no_response',
            'donated'
        ),
        users.name ASC
");

$stmt->execute([$requestId]);

$matchedDonors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate response totals
$totalDonors = count($matchedDonors);
$accepted = 0;
$declined = 0;
$pending = 0;
$donated = 0;

foreach ($matchedDonors as $donor) {

    if ($donor["status"] === "accepted") {
        $accepted++;
    } elseif ($donor["status"] === "declined") {
        $declined++;
    } elseif ($donor["status"] === "donated") {
        $donated++;
    } elseif (
        in_array(
            $donor["status"],
            ["matched", "notified", "no_response"],
            true
        )
    ) {
        $pending++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Responses - DonorNexa</title>

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

            <a href="../logout.php" class="profile-logout">
                Logout
            </a>

        </div>

    </nav>

    <main class="hospital-requests-main">

        <section class="hospital-requests-header">

            <h1>Donor Responses</h1>

            <p>
                Track donor responses for your blood request.
            </p>

        </section>

        <div class="hospital-request-card">

            <h2>
                Request #<?php echo (int)$request["id"]; ?>
            </h2>

            <p>
                Blood Group:
                <strong>
                    <?php echo htmlspecialchars($request["blood_group"]); ?>
                </strong>
            </p>

            <p>
                Hospital:
                <strong>
                    <?php echo htmlspecialchars($hospital["hospital_name"]); ?>
                </strong>
            </p>

            <hr>

            <h2>Response Summary</h2>

            <div class="request-card-details">

                <div class="request-detail">
                    <span>Total Matched Donors</span>
                    <strong><?php echo $totalDonors; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Accepted</span>
                    <strong><?php echo $accepted; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Declined</span>
                    <strong><?php echo $declined; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Pending Responses</span>
                    <strong><?php echo $pending; ?></strong>
                </div>

                <div class="request-detail">
                    <span>Donated</span>
                    <strong><?php echo $donated; ?></strong>
                </div>

            </div>

        </div>

        <br>

        <h2>Matched Donors</h2>

        <?php if (empty($matchedDonors)): ?>

            <div class="hospital-request-card">
                <p>No donors have been matched to this request yet.</p>
            </div>

        <?php else: ?>

            <?php foreach ($matchedDonors as $donor): ?>

                <div class="hospital-request-card">

                    <div class="request-card-header">

                        <h2>
                            <?php echo htmlspecialchars($donor["donor_name"]); ?>
                        </h2>

                        <span class="request-status status-<?php
                            echo htmlspecialchars($donor["status"]);
                        ?>">
                            <?php
                            echo htmlspecialchars(
                                ucfirst(str_replace("_", " ", $donor["status"]))
                            );
                            ?>
                        </span>

                    </div>

                    <div class="request-card-details">

                        <div class="request-detail">
                            <span>Blood Group</span>
                            <strong>
                                <?php echo htmlspecialchars($donor["blood_group"]); ?>
                            </strong>
                        </div>

                        <div class="request-detail">
                            <span>Phone</span>
                            <strong>
                                <?php echo htmlspecialchars($donor["phone"]); ?>
                            </strong>
                        </div>

                        <div class="request-detail">
                            <span>City</span>
                            <strong>
                                <?php echo htmlspecialchars($donor["city_name"]); ?>
                            </strong>
                        </div>

                        <div class="request-detail">
                            <span>Province</span>
                            <strong>
                                <?php echo htmlspecialchars($donor["province"]); ?>
                            </strong>
                        </div>

                        <div class="request-detail">
                            <span>Matching Score</span>
                            <strong>
                                <?php echo htmlspecialchars($donor["score"]); ?>%
                            </strong>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

        <br>

        <a href="my-requests.php" class="request-view-btn">
            Back to My Requests
        </a>

    </main>

    <footer class="profile-footer">

        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>

    </footer>

</body>
</html>
