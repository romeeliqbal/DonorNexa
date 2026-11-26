
<?php
require_once "../auth.php";

// Only admins can access this page
if ($_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Handle verification action
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hospital_id = (int)($_POST["hospital_id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($hospital_id > 0 && in_array($action, ["verified", "rejected"], true)) {

        $stmt = $pdo->prepare("
            UPDATE hospitals
            SET verification_status = ?
            WHERE id = ?
              AND verification_status = 'pending'
        ");

        $stmt->execute([$action, $hospital_id]);
    }

    header("Location: verify-hospitals.php");
    exit;
}

// Get pending hospitals
$stmt = $pdo->prepare("
    SELECT
        hospitals.id,
        hospitals.hospital_name,
        hospitals.licence_no,
        users.name,
        users.email
    FROM hospitals
    INNER JOIN users ON hospitals.user_id = users.id
    WHERE hospitals.verification_status = 'pending'
    ORDER BY hospitals.id DESC
");

$stmt->execute();
$hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Hospitals - DonorNexa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <h1>Hospital Verification</h1>

    <p>Review hospital applications and approve or reject them.</p>

    <a href="dashboard.php">Back to Dashboard</a>

    <hr>

    <?php if (isset($_GET["success"])): ?>
        <p>Hospital status updated successfully.</p>
    <?php endif; ?>

    <?php if (count($hospitals) > 0): ?>

        <table>
            <thead>
                <tr>
                    <th>Hospital Name</th>
                    <th>Account Name</th>
                    <th>Email</th>
                    <th>Licence Number</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($hospitals as $hospital): ?>

                <tr>
                    <td><?= htmlspecialchars($hospital["hospital_name"]) ?></td>
                    <td><?= htmlspecialchars($hospital["name"]) ?></td>
                    <td><?= htmlspecialchars($hospital["email"]) ?></td>
                    <td><?= htmlspecialchars($hospital["licence_no"]) ?></td>

                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="hospital_id"
                                   value="<?= $hospital["id"] ?>">

                            <button type="submit" name="action"
                                    value="verified">
                                Verify
                            </button>
                        </form>

                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="hospital_id"
                                   value="<?= $hospital["id"] ?>">

                            <button type="submit" name="action"
                                    value="rejected">
                                Reject
                            </button>
                        </form>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    <?php else: ?>

        <p>No pending hospital applications found.</p>

    <?php endif; ?>

</div>

</body>
</html>
