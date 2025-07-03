<?php
session_start();
require_once 'db_connect.php';

// Check if generic and pharmacy_name are passed in the URL
if (!isset($_GET['generic']) || !isset($_GET['pharmacy_name'])) {
    echo "Generic name or pharmacy name not provided.";
    exit();
}
// Sanitize the inputs to prevent SQL injection
$generic = $conn->real_escape_string(trim($_GET['generic']));
$pharmacyName = $conn->real_escape_string(trim($_GET['pharmacy_name']));

// Get the pharmacy_id and location based on the pharmacy name
$stmtPharmacy = $conn->prepare("SELECT pharmacy_id, location FROM pharmacy WHERE pharmacy_name = ?");
$stmtPharmacy->bind_param("s", $pharmacyName);
$stmtPharmacy->execute();
$resultPharmacy = $stmtPharmacy->get_result();

if ($resultPharmacy->num_rows > 0) {
    // If the pharmacy exists, fetch its ID and location
    $pharmacyRow = $resultPharmacy->fetch_assoc();
    $pharmacyId = $pharmacyRow['pharmacy_id'];
    $pharmacyLocation = $pharmacyRow['location'];

    // Query to get all medicines matching the generic name and same location as the pharmacy
    $query = "SELECT medicines.*, pharmacy.pharmacy_name, pharmacy.location 
              FROM medicines
              JOIN pharmacy ON medicines.pharmacy_id = pharmacy.pharmacy_id
              WHERE medicines.generic = '$generic' AND pharmacy.location = '$pharmacyLocation'";

    $result = $conn->query($query);
} else {
    echo "Pharmacy not found.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Alternatives for <?php echo htmlspecialchars($generic); ?> at <?php echo htmlspecialchars($pharmacyName); ?>
        | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f7f7f7;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
        }

        .medicine {
            background: #fff;
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .medicine h3 {
            margin: 0;
        }

        .medicine p {
            margin: 5px 0;
        }

        .no-result {
            margin-top: 30px;
            font-size: 18px;
            color: #888;
        }

        .dashboard-btn {
            background-color: #007BFF;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }

        .dashboard-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Alternatives for "<?php echo htmlspecialchars($generic); ?>" at
            "<?php echo htmlspecialchars($pharmacyName); ?>"</h2>

        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                ?>
                <div class="medicine">
                    <h3><?php echo htmlspecialchars($row['medicine_name']); ?></h3>
                    <p><strong>Pharmacy:</strong> <?php echo htmlspecialchars($row['pharmacy_name']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                    <p><strong>Side Effects:</strong> <?php echo htmlspecialchars($row['sideEffects']); ?></p>
                    <p><strong>Price:</strong> $<?php echo htmlspecialchars($row['price']); ?></p>
                    <p><strong>Generic:</strong> <?php echo htmlspecialchars($row['generic']); ?></p>
                    <p><strong>Quantity Available:</strong> <?php echo htmlspecialchars($row['quantity_in_stock']); ?></p>
                </div>
                <?php
            }
        } else {
            echo "<p class='no-result'>No alternatives found for this generic in this pharmacy.</p>";
        }
        ?>

        <div style="margin-top: 30px;">
            <a href="user_dashboard.php" class="dashboard-btn">🏠 Go to Dashboard</a>
        </div>
    </div>
</body>

</html>