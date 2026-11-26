
<?php
require_once "../auth.php";

// Only hospitals can access this page
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get hospital ID
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

$error = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $blood_group = $_POST["blood_group"] ?? "";
    $units_needed = $_POST["units_needed"] ?? "";
    $urgency = $_POST["urgency"] ?? "";
    $required_by = $_POST["required_by"] ?? "";
    $ward = trim($_POST["ward"] ?? "");
    $notes = trim($_POST["notes"] ?? "");

    $validBloodGroups = [
        "A+", "A-", "B+", "B-",
        "AB+", "AB-", "O+", "O-"
    ];

    $validUrgencies = [
        "critical", "urgent", "normal"
    ];

    if (
        !in_array($blood_group, $validBloodGroups, true) ||
        !filter_var($units_needed, FILTER_VALIDATE_INT) ||
        (int)$units_needed < 1 ||
        (int)$units_needed > 1000 ||
        !in_array($urgency, $validUrgencies, true) ||
        $required_by === "" ||
        $ward === ""
    ) {

        $error = "Please enter valid information in all required fields.";

    } elseif (
        strtotime($required_by) === false ||
        strtotime($required_by) <= time()
    ) {

        $error = "Required date and time must be in the future.";

    } else {

        // Insert blood request into database
        $insertStmt = $pdo->prepare("
            INSERT INTO blood_requests (
                hospital_id,
                blood_group,
                units_needed,
                units_fulfilled,
                urgency,
                required_by,
                ward,
                notes,
                status
            )
            VALUES (?, ?, ?, 0, ?, ?, ?, ?, 'open')
        ");

        $insertStmt->execute([
            $hospital["id"],
            $blood_group,
            (int)$units_needed,
            $urgency,
            $required_by,
            $ward,
            $notes
        ]);

        header("Location: dashboard.php?request_created=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Blood Request - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="edit-profile-page">

    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">

            <a href="dashboard.php">Dashboard</a>

            <a href="../logout.php" class="profile-logout">
                Logout
            </a>

        </div>

    </nav>

    <main class="edit-profile-main">

        <section class="edit-profile-card">

            <h1>Create Blood Request</h1>

            <p class="edit-profile-subtitle">
                Submit a blood requirement for your hospital.
            </p>

            <?php if ($error !== ""): ?>

                <div class="edit-profile-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="edit-form-group">

                    <label for="blood_group">Blood Group</label>

                   
<select id="blood_group" name="blood_group" required>

    <option value="">Select Blood Group</option>

    <option value="A+">A+</option>
    <option value="A-">A-</option>
    <option value="B+">B+</option>
    <option value="B-">B-</option>
    <option value="AB+">AB+</option>
    <option value="AB-">AB-</option>
    <option value="O+">O+</option>
    <option value="O-">O-</option>

</select>

                    

                </div>

                <div class="edit-form-group">

                    <label for="units_needed">Units Needed</label>

                    <input
                        type="number"
                        id="units_needed"
                        name="units_needed"
                        min="1"
                        max="1000"
                        value="<?php echo htmlspecialchars($_POST["units_needed"] ?? ""); ?>"
                        required
                    >

                </div>

                <div class="edit-form-group">

                    <label for="urgency">Urgency Level</label>

                    <select id="urgency" name="urgency" required>

                        <option value="">Select Urgency</option>

                        <option value="critical"
                            <?php echo (($_POST["urgency"] ?? "") === "critical") ? "selected" : ""; ?>>
                            Critical
                        </option>

                        <option value="urgent"
                            <?php echo (($_POST["urgency"] ?? "") === "urgent") ? "selected" : ""; ?>>
                            Urgent
                        </option>

                        <option value="normal"
                            <?php echo (($_POST["urgency"] ?? "") === "normal") ? "selected" : ""; ?>>
                            Normal
                        </option>

                    </select>

                </div>

                <div class="edit-form-group">

                    <label for="required_by">Required Date and Time</label>

                    <input
                        type="datetime-local"
                        id="required_by"
                        name="required_by"
                        value="<?php echo htmlspecialchars($_POST["required_by"] ?? ""); ?>"
                        required
                    >

                </div>

                <div class="edit-form-group">

                    <label for="ward">Ward / Department</label>

                    <input
                        type="text"
                        id="ward"
                        name="ward"
                        maxlength="150"
                        value="<?php echo htmlspecialchars($_POST["ward"] ?? ""); ?>"
                        placeholder="Example: Emergency Ward"
                        required
                    >

                </div>

                <div class="edit-form-group">

                    <label for="notes">Additional Notes (Optional)</label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        maxlength="2000"
                        placeholder="Enter additional information..."
                        style="width:100%; padding:13px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; font-family:Arial;"
                    ><?php echo htmlspecialchars($_POST["notes"] ?? ""); ?></textarea>

                </div>

                <button type="submit" class="edit-profile-button">
                    Submit Blood Request
                </button>

                <a href="dashboard.php" class="edit-profile-cancel">
                    Cancel
                </a>

            </form>

        </section>

    </main>

    <footer class="profile-footer">

        <p>
            &copy; <?php echo date("Y"); ?> DonorNexa. All rights reserved.
        </p>

    </footer>

</body>
</html>
