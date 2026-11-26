
<?php
require_once "../auth.php";

// Only hospitals can record donations
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

// Get submitted values
$matchId = (int)($_POST["match_id"] ?? 0);
$units = (int)($_POST["units"] ?? 0);

if ($matchId <= 0 || $units <= 0) {
    die("Invalid match or donation units.");
}

// Get logged-in hospital
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

// Confirm that the accepted match belongs to this hospital
$stmt = $pdo->prepare("
    SELECT rm.id
    FROM request_matches rm
    INNER JOIN blood_requests br
        ON rm.request_id = br.id
    WHERE rm.id = ?
      AND br.hospital_id = ?
      AND rm.status = 'accepted'
");

$stmt->execute([
    $matchId,
    $hospital["id"]
]);

$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("This accepted donor match was not found.");
}

// Prevent duplicate donation records
$stmt = $pdo->prepare("
    SELECT id
    FROM donations
    WHERE match_id = ?
");

$stmt->execute([$matchId]);

if ($stmt->fetch()) {
    die("A donation has already been recorded for this match.");
}

// Save donation; admin verification will happen later
$stmt = $pdo->prepare("
    INSERT INTO donations
        (match_id, units, donated_on, verified_by)
    VALUES (?, ?, NOW(), NULL)
");

$stmt->execute([
    $matchId,
    $units
]);

// Update match status
$stmt = $pdo->prepare("
    UPDATE request_matches
    SET status = 'donated'
    WHERE id = ?
");

$stmt->execute([$matchId]);

header("Location: record-donation.php?success=1");
exit;
?>
