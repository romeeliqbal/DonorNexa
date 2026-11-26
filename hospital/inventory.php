
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

// Get hospital blood inventory
$stmt = $pdo->prepare("
    SELECT
        blood_group,
        units_available,
        updated_at
    FROM blood_inventory
    WHERE hospital_id = ?
    ORDER BY blood_group ASC
");

$stmt->execute([$hospital["id"]]);
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blood Inventory - DonorNexa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<h2>Hospital Blood Inventory</h2>

<h3>
    <?php echo htmlspecialchars($hospital["hospital_name"]); ?>
</h3>

<p>Available blood units in your hospital.</p>

<?php if (empty($inventory)): ?>

    <p>No blood inventory is available yet.</p>

<?php else: ?>

    <div class="request-card">

        <table border="1" cellpadding="12" cellspacing="0">

            <thead>
                <tr>
                    <th>Blood Group</th>
                    <th>Available Units</th>
                    <th>Last Updated</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($inventory as $item): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item["blood_group"]); ?>
                        </td>

                        <td>
                            <?php echo (int)$item["units_available"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item["updated_at"]); ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

</body>
</html>
