<?php
require_once "../auth.php";

// Only hospitals can access this page
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Only POST requests are allowed
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

// Get logged-in hospital ID
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

// Get request ID from POST
$requestId = filter_input(INPUT_POST, "request_id", FILTER_VALIDATE_INT);

if (!$requestId) {
    die("Invalid request ID.");
}

// Cancel only this hospital's open request
$cancelStmt = $pdo->prepare("
    UPDATE blood_requests
    SET status = 'cancelled'
    WHERE id = ?
      AND hospital_id = ?
      AND status = 'open'
      AND units_fulfilled = 0
");

$cancelStmt->execute([
    $requestId,
    $hospital["id"]
]);

if ($cancelStmt->rowCount() > 0) {

    header("Location: my-requests.php?request_cancelled=1");
    exit;

} else {

    header("Location: my-requests.php?cancel_error=1");
    exit;
}
?>