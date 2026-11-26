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

// Get request ID
$requestId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$requestId) {
    die("Invalid request ID.");
}

// Get this hospital's request
$stmt = $pdo->prepare("
    SELECT *
    FROM blood_requests
    WHERE id = ? AND hospital_id = ?
");

$stmt->execute([$requestId, $hospital["id"]]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Request not found or access denied.");
}

// Only allow editing open requests with no fulfilled units
if ($request["status"] !== "open" || (int)$request["units_fulfilled"] > 0) {
    die("This request can no longer be edited.");
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
        filter_var($units_needed, FILTER_VALIDATE_INT) === false ||
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

        // Update only this hospital's open request
        $updateStmt = $pdo->prepare("
            UPDATE blood_requests
            SET blood_group = ?,
                units_needed = ?,
                urgency = ?,
                required_by = ?,
                ward = ?,
                notes = ?
            WHERE id = ?
              AND hospital_id = ?
              AND status = 'open'
              AND units_fulfilled = 0
        ");

        $updateStmt->execute([
            $blood_group,
            (int)$units_needed,
            $urgency,
            $required_by,
            $ward,
            $notes,
            $requestId,
            $hospital["id"]
        ]);

        header("Location: my-requests.php?request_updated=1");
        exit;
    }
}

// Convert database date to datetime-local format
$requiredByValue = date(
    "Y-m-d\TH:i",
    strtotime($request["required_by"])
);

// Keep submitted values if validation fails
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $requiredByValue = $required_by;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Blood Request - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="hospital-requests-page">

    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="my-requests.php">My Requests</a>
            <a href="../logout.php" class="profile-logout">Logout</a>
        </div>

    </nav>

    <main class="hospital-requests-main">

        <section class="hospital-requests-header">
            <h1>Edit Blood Request</h1>
            <p>Update the information for your blood request.</p>
        </section>

        <div class="hospital-request-card">

            <h2>Request #<?php echo (int)$request["id"]; ?></h2>

            <?php if ($error !== ""): ?>
                <p style="color: red;">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form method="POST">

                <div class="edit-profile-form-group">
                    <label for="blood_group">Blood Group</label>

                    <select id="blood_group" name="blood_group" required>

                        <option value="">Select Blood Group</option>

                        <?php
                        $bloodGroups = [
                            "A+", "A-", "B+", "B-",
                            "AB+", "AB-", "O+", "O-"
                        ];

                        foreach ($bloodGroups as $group):
                        ?>

                            <option
                                value="<?php echo $group; ?>"
                                <?php
                                $selectedGroup = $_POST["blood_group"] ?? $request["blood_group"];
                                echo ($selectedGroup === $group) ? "selected" : "";
                                ?>
                            >
                                <?php echo $group; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="edit-profile-form-group">
                    <label for="units_needed">Units Needed</label>

                    <input
                        type="number"
                        id="units_needed"
                        name="units_needed"
                        min="1"
                        max="1000"
                        required
                        value="<?php echo htmlspecialchars($_POST["units_needed"] ?? $request["units_needed"]); ?>"
                    >
                </div>

                <div class="edit-profile-form-group">
                    <label for="urgency">Urgency</label>

                    <select id="urgency" name="urgency" required>

                        <?php
                        $urgencies = ["critical", "urgent", "normal"];
                        $selectedUrgency = $_POST["urgency"] ?? $request["urgency"];

                        foreach ($urgencies as $level):
                        ?>

                            <option
                                value="<?php echo $level; ?>"
                                <?php echo ($selectedUrgency === $level) ? "selected" : ""; ?>
                            >
                                <?php echo ucfirst($level); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="edit-profile-form-group">
                    <label for="required_by">Required By</label>

                    <input
                        type="datetime-local"
                        id="required_by"
                        name="required_by"
                        required
                        value="<?php echo htmlspecialchars($requiredByValue); ?>"
                    >
                </div>

                <div class="edit-profile-form-group">
                    <label for="ward">Ward / Department</label>

                    <input
                        type="text"
                        id="ward"
                        name="ward"
                        required
                        value="<?php echo htmlspecialchars($_POST["ward"] ?? $request["ward"]); ?>"
                    >
                </div>

                <div class="edit-profile-form-group">
                    <label for="notes">Additional Notes (Optional)</label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="5"
                    ><?php echo htmlspecialchars($_POST["notes"] ?? $request["notes"]); ?></textarea>
                </div>

                <button type="submit" class="request-edit-btn">
                    Save Changes
                </button>

                <a href="view-request.php?id=<?php echo (int)$request["id"]; ?>" class="request-view-btn">
                    Back
                </a>

            </form>

        </div>

    </main>

</body>
</html>