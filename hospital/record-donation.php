
<?php
require_once "../auth.php";

// Only hospitals can access this page
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get the logged-in hospital
$stmt = $pdo->prepare("
    SELECT id
    FROM hospitals
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$hospital = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospital) {
    die("Hospital profile not found.");
}

// Get accepted donor matches belonging to this hospital
$stmt = $pdo->prepare("
    SELECT
        rm.id AS match_id,
        rm.request_id,
        rm.status,
        u.name AS donor_name,
        d.blood_group,
        br.blood_group AS requested_group
    FROM request_matches rm
    INNER JOIN donors d
        ON rm.donor_id = d.id
    INNER JOIN users u
        ON d.user_id = u.id
    INNER JOIN blood_requests br
        ON rm.request_id = br.id
    WHERE br.hospital_id = ?
      AND rm.status = 'accepted'
      AND NOT EXISTS (
          SELECT 1
          FROM donations dn
          WHERE dn.match_id = rm.id
      )
    ORDER BY rm.id DESC
");

$stmt->execute([$hospital["id"]]);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Donation - DonorNexa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Record Donor Donations</h2>

<p>Select an accepted donor match to record a completed donation.</p>

<?php if (empty($matches)): ?>

    <p>No accepted matches are currently available for donation recording.</p>

<?php else: ?>

    <?php foreach ($matches as $match): ?>

        <div class="request-card">

            <h3>
                Donor: <?php echo htmlspecialchars($match["donor_name"]); ?>
            </h3>

            <p>
                Donor Blood Group:
                <?php echo htmlspecialchars($match["blood_group"]); ?>
            </p>

            <p>
                Requested Blood Group:
                <?php echo htmlspecialchars($match["requested_group"]); ?>
            </p>

            <p>
                Request ID:
                <?php echo (int)$match["request_id"]; ?>
            </p>

            <form method="POST" action="save-donation.php">

                <input
                    type="hidden"
                    name="match_id"
                    value="<?php echo (int)$match["match_id"]; ?>"
                >

                <label>Units Donated:</label>

                <input
                    type="number"
                    name="units"
                    min="1"
                    required
                >

                <button type="submit" class="request-edit-btn">
                    Record Donation
                </button>

            </form>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
