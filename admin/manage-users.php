
<?php
require_once "../auth.php";

// Only admins can access this page
if ($_SESSION["role"] !== "admin") {
    die("Access denied.");
}

// Handle block/unblock action
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["toggle_status"])
) {
    $userId = (int)($_POST["user_id"] ?? 0);

    // Prevent invalid IDs and blocking your own account
    if ($userId <= 0 || $userId === (int)$_SESSION["user_id"]) {
        die("Invalid user action.");
    }

    // Get the selected user's current status
    $stmt = $pdo->prepare("
        SELECT status
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([$userId]);
    $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$selectedUser) {
        die("User not found.");
    }

    // Change active to blocked or blocked to active
    if ($selectedUser["status"] === "active") {
        $newStatus = "blocked";
    } elseif ($selectedUser["status"] === "blocked") {
        $newStatus = "active";
    } else {
        die("Invalid account status.");
    }

    $updateStmt = $pdo->prepare("
        UPDATE users
        SET status = ?
        WHERE id = ?
    ");

    $updateStmt->execute([$newStatus, $userId]);

    header("Location: manage-users.php?success=1");
    exit;
}

// Get all registered users
$stmt = $pdo->query("
    SELECT id, name, email, role, status, created_at
    FROM users
    ORDER BY id DESC
");

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Management - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<h1>User Management</h1>

<p>View and manage registered DonorNexa accounts.</p>

<p>
    <a href="dashboard.php">Back to Admin Dashboard</a>
</p>

<?php if (isset($_GET["success"])): ?>
    <p style="color: green;">
        Account status updated successfully!
    </p>
<?php endif; ?>

<?php if (empty($users)): ?>

    <p>No registered users found.</p>

<?php else: ?>

    <div class="request-card">

        <table border="1" cellpadding="10" cellspacing="0">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>
                        <td>
                            <?php echo (int)$user["id"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user["name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($user["email"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(ucfirst($user["role"])); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars(ucfirst($user["status"])); ?>
                        </td>

                        <td>

                            <?php if ((int)$user["id"] === (int)$_SESSION["user_id"]): ?>

                                <span>Current Admin</span>

                            <?php else: ?>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?php echo (int)$user["id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="toggle_status"
                                        value="1"
                                        onclick="return confirm('Are you sure you want to change this account status?');"
                                    >
                                        <?php
                                        echo $user["status"] === "active"
                                            ? "Block User"
                                            : "Unblock User";
                                        ?>
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

</body>
</html>
