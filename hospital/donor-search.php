
<?php
require_once "../auth.php";

// Only hospitals can access donor search
if ($_SESSION["role"] !== "hospital") {
    die("Access denied.");
}

// Get available cities
$cityStmt = $pdo->query("
    SELECT id, city_name, province
    FROM cities
    ORDER BY city_name
");

$cities = $cityStmt->fetchAll(PDO::FETCH_ASSOC);

// Get search filters
$bloodGroup = $_GET["blood_group"] ?? "";
$cityId = $_GET["city_id"] ?? "";

// Validate filters
$validBloodGroups = [
    "A+", "A-", "B+", "B-",
    "AB+", "AB-", "O+", "O-"
];

if (!in_array($bloodGroup, $validBloodGroups, true)) {
    $bloodGroup = "";
}

if ($cityId !== "" && filter_var($cityId, FILTER_VALIDATE_INT) === false) {
    $cityId = "";
}

// Build search query
$sql = "
    SELECT
        users.name,
        donors.blood_group,
        donors.phone,
        cities.city_name,
        cities.province
    FROM donors
    INNER JOIN users
        ON donors.user_id = users.id
    INNER JOIN cities
        ON donors.city_id = cities.id
    WHERE donors.is_available = 1
      AND users.status = 'active'
";

$params = [];

// Filter by blood group
if ($bloodGroup !== "") {
    $sql .= " AND donors.blood_group = ?";
    $params[] = $bloodGroup;
}

// Filter by city
if ($cityId !== "") {
    $sql .= " AND donors.city_id = ?";
    $params[] = $cityId;
}

$sql .= " ORDER BY users.name ASC LIMIT 100";

// Execute donor search
$searchStmt = $pdo->prepare($sql);
$searchStmt->execute($params);

$donors = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Donor Search - DonorNexa</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body class="hospital-requests-page">

    <nav class="profile-navbar">

        <div class="profile-brand">
            Donor<span>Nexa</span>
        </div>

        <div class="profile-nav-links">

            <a href="dashboard.php">Dashboard</a>

            <a href="create-request.php">Create Request</a>

            <a href="my-requests.php">My Requests</a>

            <a href="donor-search.php">Donor Search</a>

            <a href="../logout.php" class="profile-logout">Logout</a>

        </div>

    </nav>

    <main class="hospital-requests-main">

        <section class="hospital-requests-header">

            <h1>Donor Search</h1>

            <p>Find available blood donors by blood group and city.</p>

        </section>

        <!-- Search Form -->
       <div class="hospital-request-card">

            <h2>Search Filters</h2>

            <form method="GET">

                <div class="edit-profile-form-group">

                    <label for="blood_group">Blood Group</label>

                    <select id="blood_group" name="blood_group">

                        <option value="">All Blood Groups</option>

                        <option value="A+" <?php echo $bloodGroup === "A+" ? "selected" : ""; ?>>A+</option>
                        <option value="A-" <?php echo $bloodGroup === "A-" ? "selected" : ""; ?>>A-</option>
                        <option value="B+" <?php echo $bloodGroup === "B+" ? "selected" : ""; ?>>B+</option>
                        <option value="B-" <?php echo $bloodGroup === "B-" ? "selected" : ""; ?>>B-</option>
                        <option value="AB+" <?php echo $bloodGroup === "AB+" ? "selected" : ""; ?>>AB+</option>
                        <option value="AB-" <?php echo $bloodGroup === "AB-" ? "selected" : ""; ?>>AB-</option>
                        <option value="O+" <?php echo $bloodGroup === "O+" ? "selected" : ""; ?>>O+</option>
                        <option value="O-" <?php echo $bloodGroup === "O-" ? "selected" : ""; ?>>O-</option>

                    </select>

                </div>

                <div class="edit-profile-form-group">

                    <label for="city_id">City</label>

                    <select id="city_id" name="city_id">

                        <option value="">All Cities</option>

                        <?php foreach ($cities as $city): ?>

                            <option
                                value="<?php echo (int)$city["id"]; ?>"
                                <?php echo (string)$cityId === (string)$city["id"] ? "selected" : ""; ?>
                            >
                                <?php
                                echo htmlspecialchars(
                                    $city["city_name"] . ", " . $city["province"]
                                );
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <button type="submit" class="request-edit-btn">
                    Search Donors
                </button>

                <a href="donor-search.php" class="request-view-btn">
                    Clear Filters
                </a>

            </form>

        </div>

        <!-- Search Results -->
        <section class="donor-search-results">

            <h2>Available Donors (<?php echo count($donors); ?>)</h2>

            <?php if (count($donors) > 0): ?>

                <?php foreach ($donors as $donor): ?>

                   <div class="hospital-request-card donor-search-card">

                        <h3><?php echo htmlspecialchars($donor["name"]); ?></h3>

                        <div class="request-card-details">

                            <div class="request-detail">
                                <span>Blood Group</span>
                                <strong class="donor-search-blood-group">
    <?php echo htmlspecialchars($donor["blood_group"]); ?>
</strong>
                            </div>

                            <div class="request-detail">
                                <span>City</span>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $donor["city_name"] . ", " . $donor["province"]
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div class="request-detail">
                                <span>Phone</span>
                                <strong><?php echo htmlspecialchars($donor["phone"]); ?></strong>
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="hospital-request-card">

                    <p>
                        No available donors found for the selected filters.
                    </p>

                </div>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>