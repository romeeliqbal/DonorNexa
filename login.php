
<?php
session_start();

require_once "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Find the user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user["password_hash"])) {

        if ($user["status"] !== "active") {
            $error = "Your account is blocked. Please contact the administrator.";
        } else {

            session_regenerate_id(true);

            // Store login information in session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            // Redirect according to user role
            if ($user["role"] === "donor") {
                header("Location: donor/dashboard.php");
                exit;
            } elseif ($user["role"] === "hospital") {
                header("Location: hospital/dashboard.php");
                exit;
            } elseif ($user["role"] === "admin") {
                header("Location: admin/dashboard.php");
                exit;
            }
        }

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - DonorNexa</title>

    <link rel="stylesheet" href="/DonorNexa/assets/css/style.css">
</head>

<body class="login-page">

    <div class="login-container">

        <div class="login-card">

            <div class="login-brand">
                <h1>Donor<span>Nexa</span></h1>
                <p>Connecting donors. Saving lives.</p>
            </div>

            <div class="login-heading">
                <h2>Welcome Back!</h2>
                <p>Login to access your DonorNexa account.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="login-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form">

                <div class="form-group">
                    <label for="email">Email Address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" name="login" class="login-button">
                    Login to Account
                </button>

            </form>

            <div class="login-footer">
                <p>
                    Don't have an account?
                    <a href="register.php">Register here</a>
                </p>

                <a href="index.php" class="back-home">
                    ← Back to Homepage
                </a>
            </div>

        </div>

    </div>

</body>
</html>
