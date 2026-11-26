
<?php
require_once "../auth.php";

// Only admins can access this page
if ($_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Get all blood requests
$stmt = $pdo->prepare("
    SELECT
        blood_requests.*,
        hospitals.hospital_name
    FROM blood_requests
    INNER JOIN hospitals
        ON blood_requests.hospital_id = hospitals.id
    ORDER BY blood_requests.created_at DESC
");

$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Blood Requests - DonorNexa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container">

    <h1>Manage Blood Requests</h1>

    <p>View and monitor all blood requests submitted by hospitals.</p>

    <a href="dashboard.php">Back to Dashboard</a>

    <hr>

    <?php if (count($requests) > 0): ?>

        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Hospital</th>
                    <th>Blood Group</th>
                    <th>Units Needed</th>
                    <th>Units Fulfilled</th>
                    <th>Urgency</th>
                    <th>Required By</th>
                    <th>Ward</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>

            <tbody>

            <?php foreach ($requests as $request): ?>

                <tr>
                    <td><?= (int)$request["id"] ?></td>

                    <td>
                        <?= htmlspecialchars($request["hospital_name"]) ?>
                    </td>

                    <td><?= htmlspecialchars($request["blood_group"]) ?></td>

                    <td><?= (int)$request["units_needed"] ?></td>

                    <td><?= (int)$request["units_fulfilled"] ?></td>

                    <td><?= htmlspecialchars($request["urgency"]) ?></td>

                    <td><?= htmlspecialchars($request["required_by"]) ?></td>

                    <td><?= htmlspecialchars($request["ward"] ?? "") ?></td>

                    <td><?= htmlspecialchars($request["status"]) ?></td>

                    <td><?= htmlspecialchars($request["created_at"]) ?></td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    <?php else: ?>

        <p>No blood requests found.</p>

    <?php endif; ?>

</div>

</body>
</html>
