<?php
session_start();
require_once 'db_connect.php';

// Handle Add to Cart POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $medicine_id = $_POST['medicine_id'];
    $pharmacy_id = $_POST['pharmacy_id'];
    $medicine_name = $_POST['medicine_name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add item to cart
    $_SESSION['cart'][] = [
        'medicine_id' => $medicine_id,
        'pharmacy_id' => $pharmacy_id,
        'medicine_name' => $medicine_name,
        'price' => $price,
        'quantity' => $quantity
    ];

    $addedMessage = "Added to cart successfully!";
}

// Handle GET request to search medicine and pharmacy
$medicineName = isset($_GET['medicine']) ? htmlspecialchars(trim($_GET['medicine'])) : '';
$pharmacyName = isset($_GET['pharmacy']) ? htmlspecialchars(trim($_GET['pharmacy'])) : '';

$statusMessage = '';
$medicineDetails = [];
$pharmacyLocation = '';

if ($medicineName && $pharmacyName) {
     // Get pharmacy ID and location by pharmacy name
    $stmtPharmacy = $conn->prepare("SELECT pharmacy_id, location FROM pharmacy WHERE pharmacy_name = ?");
    $stmtPharmacy->bind_param("s", $pharmacyName);
    $stmtPharmacy->execute();
    $resultPharmacy = $stmtPharmacy->get_result();

    if ($resultPharmacy->num_rows > 0) {
        $pharmacyRow = $resultPharmacy->fetch_assoc();
        $pharmacyId = $pharmacyRow['pharmacy_id'];
        $pharmacyLocation = $pharmacyRow['location'];

        // Now, check if the medicine exists in that pharmacy
        $stmt = $conn->prepare("SELECT * FROM medicines WHERE medicine_name = ? AND pharmacy_id = ?");
        $stmt->bind_param("si", $medicineName, $pharmacyId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $medicineDetails = $result->fetch_assoc();
            $statusMessage = "Available";
        } else {
            $statusMessage = "Not Found";
        }

        $stmt->close();
    } else {
        $statusMessage = "Pharmacy not found.";
    }

    $stmtPharmacy->close();
} else {
    $statusMessage = "Invalid search parameters.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pharmacy Search Result | MediFind</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/searchph_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            padding: 20px;
            margin: 0;
        }

        .result-container {
            max-width: 700px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            /* slightly transparent white */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        h2 {
            color: #0056b3;
            /* dark blue */
            text-align: center;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            background: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .back-link:hover {
            background: #0056b3;
        }

        .message {
            font-size: 18px;
            color: #333;
            text-align: center;
            margin-top: 10px;
        }

        .cart-actions {
            margin-top: 25px;
            text-align: center;
        }

        .add-to-cart {
            padding: 10px 20px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .add-to-cart:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            font-size: 18px;
            text-align: center;
            margin-top: 10px;
        }

        .view-alternatives-btn {
            background-color: #007BFF;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: block;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .view-alternatives-btn:hover {
            background-color: #0056b3;
        }

        label {
            font-weight: bold;
            color: #0056b3;
        }

        input[type="number"] {
            padding: 8px;
            width: 80px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 10px;
        }
    </style>

</head>

<body>

    <div class="result-container">
        <!-- View Alternatives Button at the Top -->
        <a href="alternative_pharmacy.php?generic=<?php echo urlencode($medicineName); ?>&pharmacy_name=<?php echo urlencode($pharmacyName); ?>"
            class="view-alternatives-btn">
            🔍 View Alternatives
        </a>

        <h2>Search Result</h2>

        <?php if ($medicineName && $pharmacyName): ?>
            <p>You searched for <strong><?php echo htmlspecialchars($medicineName); ?></strong> in pharmacy
                <strong><?php echo htmlspecialchars($pharmacyName); ?></strong>.</p>
            <p><strong>Status:</strong> <?php echo $statusMessage; ?></p>

            <?php if ($statusMessage === "Available" && $medicineDetails): ?>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($pharmacyLocation); ?></p>
                <p><strong>Price:</strong> $<?php echo htmlspecialchars($medicineDetails['price']); ?></p>
                <p><strong>Generic Name:</strong>
                    <?php echo htmlspecialchars($medicineDetails['generic'] ?? 'Not available'); ?></p>
                <p><strong>Side Effects:</strong>
                    <?php echo htmlspecialchars($medicineDetails['sideEffects'] ?? 'Not available'); ?></p>

                <!-- Quantity Counter -->
                <form method="POST">
                    <input type="hidden" name="medicine_id" value="<?php echo $medicineDetails['medicine_id']; ?>">
                    <input type="hidden" name="pharmacy_id" value="<?php echo $pharmacyId; ?>">
                    <input type="hidden" name="medicine_name"
                        value="<?php echo htmlspecialchars($medicineDetails['medicine_name']); ?>">
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($medicineDetails['price']); ?>">

                    <label for="quantity">Quantity: </label>
                    <input type="number" name="quantity" value="1" min="1" id="quantity" required>
                    <button type="submit" name="add_to_cart" class="add-to-cart">Add to Cart</button>
                </form>

                <?php if (isset($addedMessage)): ?>
                    <p class="message"><?php echo $addedMessage; ?></p>
                <?php endif; ?>

                <div class="cart-actions">
                    <a href="view_cart.php" class="add-to-cart">🛒 View Cart</a>
                </div>

            <?php else: ?>
                <p class="error"><?php echo $statusMessage; ?></p>
            <?php endif; ?>

        <?php else: ?>
            <p class="error"><?php echo $statusMessage; ?></p>
        <?php endif; ?>

        <!-- Always show Back to Dashboard button -->
        <div class="cart-actions">
            <a href="user_dashboard.php" class="back-link">🏠 Back to Dashboard</a>
        </div>
    </div>

</body>

</html>