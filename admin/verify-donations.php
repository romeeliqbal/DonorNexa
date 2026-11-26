
<?php
require_once "../auth.php";

// Only admins can access this page
if ($_SESSION["role"] !== "admin") {
    die("Access denied.");
}


// Handle donation verification
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["verify_donation"])
) {
    $donationId = (int)($_POST["donation_id"] ?? 0);

    if ($donationId <= 0) {
        die("Invalid donation ID.");
    }

    try {
        $pdo->beginTransaction();

        // Get donation, donor blood group and hospital
        $stmt = $pdo->prepare("
            SELECT
                dn.id,
                dn.units,
                dn.verified_by,
                d.blood_group,
                br.hospital_id
            FROM donations dn
            INNER JOIN request_matches rm
                ON dn.match_id = rm.id
            INNER JOIN donors d
                ON rm.donor_id = d.id
            INNER JOIN blood_requests br
                ON rm.request_id = br.id
            WHERE dn.id = ?
            FOR UPDATE
        ");

        $stmt->execute([$donationId]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$donation || $donation["verified_by"] !== null) {
            $pdo->rollBack();

            header("Location: verify-donations.php?error=1");
            exit;
        }

        // Mark donation as verified
        $verifyStmt = $pdo->prepare("
            UPDATE donations
            SET verified_by = ?
            WHERE id = ?
              AND verified_by IS NULL
        ");

        $verifyStmt->execute([
            $_SESSION["user_id"],
            $donationId
        ]);

        if ($verifyStmt->rowCount() !== 1) {
            throw new Exception("Donation verification failed.");
        }

        // Add donated units to hospital inventory
        // If the blood group already exists, increase its units
        $inventoryStmt = $pdo->prepare("
            INSERT INTO blood_inventory
                (hospital_id, blood_group, units_available, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                units_available = units_available + VALUES(units_available),
                updated_at = NOW()
        ");

        $inventoryStmt->execute([
            $donation["hospital_id"],
            $donation["blood_group"],
            $donation["units"]
        ]);

        // Save all changes together
        $pdo->commit();

        header("Location: verify-donations.php?success=1");
        exit;

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die("Verification error: " . htmlspecialchars($e->getMessage()));
    }
}


// Get all donations with donor and hospital details
$stmt = $pdo->prepare("
    SELECT
        dn.id AS donation_id,
        dn.match_id,
        dn.units,
        dn.donated_on,
        dn.verified_by,

        donor_user.name AS donor_name,
        d.blood_group,

        hospital.hospital_name

    FROM donations dn

    INNER JOIN request_matches rm
        ON dn.match_id = rm.id

    INNER JOIN donors d
        ON rm.donor_id = d.id

    INNER JOIN users donor_user
        ON d.user_id = donor_user.id

    INNER JOIN blood_requests br
        ON rm.request_id = br.id

    INNER JOIN hospitals hospital
        ON br.hospital_id = hospital.id

    ORDER BY dn.donated_on DESC
");

$stmt->execute();
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Donations - DonorNexa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Donation Verification</h2>

<p>Review donations recorded by hospitals.</p>

<?php if (isset($_GET["success"])): ?>

    <p style="color: green;">
        Donation verified successfully!
    </p>

<?php endif; ?>

<?php if (isset($_GET["error"])): ?>

    <p style="color: red;">
        Donation could not be verified. It may already be verified.
    </p>

<?php endif; ?>

<?php if (empty($donations)): ?>

    <p>No donations have been recorded yet.</p>

<?php else: ?>

    <?php foreach ($donations as $donation): ?>

        <div class="request-card">

            <h3>
                Donation #<?php echo (int)$donation["donation_id"]; ?>
            </h3>

            <p>
                Donor:
                <?php echo htmlspecialchars($donation["donor_name"]); ?>
            </p>

            <p>
                Blood Group:
                <?php echo htmlspecialchars($donation["blood_group"]); ?>
            </p>

            <p>
                Hospital:
                <?php echo htmlspecialchars($donation["hospital_name"]); ?>
            </p>

            <p>
                Match ID:
                <?php echo (int)$donation["match_id"]; ?>
            </p>

            <p>
                Units Donated:
                <?php echo (int)$donation["units"]; ?>
            </p>

            <p>
                Donation Date:
                <?php echo htmlspecialchars($donation["donated_on"]); ?>
            </p>

            <?php if ($donation["verified_by"] === null): ?>

                <p style="color: orange;">
                    Status: Pending Verification
                </p>

                <form method="POST">

                    <input
                        type="hidden"
                        name="donation_id"
                        value="<?php echo (int)$donation["donation_id"]; ?>"
                    >

                    <button
                        type="submit"
                        name="verify_donation"
                        value="1"
                        class="request-edit-btn"
                        onclick="return confirm('Are you sure you want to verify this donation?');"
                    >
                        Verify Donation
                    </button>

                </form>

            <?php else: ?>

                <p style="color: green;">
                    Status: Verified
                </p>

                <p>
                    Verified By Admin ID:
                    <?php echo (int)$donation["verified_by"]; ?>
                </p>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>

</body>
</html>
