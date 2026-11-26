
<?php
session_start();

// Check whether the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: /DonorNexa/login.php");
    exit;
}

// Load database connection
require_once __DIR__ . "/config/db.php";

// Check account status and role
$stmt = $pdo->prepare("
    SELECT status, role
    FROM users
    WHERE id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check whether the account is active
if (!$user || $user["status"] !== "active") {
    $_SESSION = [];
    session_destroy();

    header("Location: /DonorNexa/login.php");
    exit;
}

// Check hospital verification status
if ($user["role"] === "hospital") {

    $stmt = $pdo->prepare("
        SELECT verification_status
        FROM hospitals
        WHERE user_id = ?
    ");

    $stmt->execute([$_SESSION["user_id"]]);
    $hospital = $stmt->fetch(PDO::FETCH_ASSOC);

    // Block pending, rejected, or missing hospital records
    if (!$hospital || $hospital["verification_status"] !== "verified") {
        die("
            <h2>Hospital Access Restricted</h2>
            <p>Your hospital account has not yet been verified by the administrator.</p>
            <p>Please wait for verification or contact the administrator.</p>
            <a href='/DonorNexa/logout.php'>Logout</a>
        ");
    }
}
?>
