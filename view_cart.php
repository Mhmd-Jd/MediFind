<?php
session_start();
require_once 'db_connect.php';

// Initialize cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Delete a specific item from cart
if (isset($_GET['action'], $_GET['medicine_id'], $_GET['pharmacy_id']) && $_GET['action'] === 'delete') {
    $medicine_id = $_GET['medicine_id'];
    $pharmacy_id = $_GET['pharmacy_id'];

     // Loop through the cart to find and remove the item
    foreach ($_SESSION['cart'] as $index => $item) {
        if (isset($item['medicine_id'], $item['pharmacy_id']) &&
            $item['medicine_id'] == $medicine_id && $item['pharmacy_id'] == $pharmacy_id) {
            unset($_SESSION['cart'][$index]);// Remove item
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: view_cart.php");
    exit;
}

// Cancel all items from a specific pharmacy
if (isset($_GET['action'], $_GET['pharmacy_id']) && $_GET['action'] === 'cancel') {
    $pharmacy_id = $_GET['pharmacy_id'];
     // Remove all items in cart from the given pharmacy
    foreach ($_SESSION['cart'] as $index => $item) {
        if (isset($item['pharmacy_id']) && $item['pharmacy_id'] == $pharmacy_id) {
            unset($_SESSION['cart'][$index]);
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header("Location: view_cart.php");
    exit;
}

// Update quantity with stock validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'], $_POST['medicine_id'], $_POST['pharmacy_id'])) {
    $medicine_id = $_POST['medicine_id'];
    $pharmacy_id = $_POST['pharmacy_id'];
    $new_quantity = max(1, intval($_POST['update_quantity']));
    
    // Get stock quantity from database
    $stmt = $conn->prepare("SELECT quantity_in_stock FROM medicines WHERE medicine_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $medicine_id, $pharmacy_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stock_data = $result->fetch_assoc();
        $stmt->close();
        
        $available_stock = $stock_data['quantity_in_stock'] ?? 0;
        
        // Limit quantity to available stock
        $final_quantity = min($new_quantity, $available_stock);
        
        // Update cart item
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['medicine_id'] == $medicine_id && $item['pharmacy_id'] == $pharmacy_id) {
                $item['quantity'] = $final_quantity;
                break;
            }
        }
        unset($item); // break reference
        
        // Set session message if quantity was limited
        if ($new_quantity > $available_stock) {
            $_SESSION['stock_message'] = "Quantity limited to available stock ($available_stock units)";
        }
    }
    
    header("Location: view_cart.php");
    exit;
}

// Get pharmacy name
function getPharmacyName($pharmacy_id, $conn) {
    $stmt = $conn->prepare("SELECT pharmacy_name FROM pharmacy WHERE pharmacy_id = ?");
    if (!$stmt) return 'Unknown Pharmacy';
    $stmt->bind_param("i", $pharmacy_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pharmacy = $result->fetch_assoc();
    $stmt->close();
    return $pharmacy['pharmacy_name'] ?? 'Unknown Pharmacy';
}

// Get user address
function getUserAddress($user_id, $conn) {
    $stmt = $conn->prepare("SELECT city, street, building, floor FROM users WHERE user_id = ?");
    if (!$stmt) return [];
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $address = $result->fetch_assoc();
    $stmt->close();
    return $address ?: [];
}

// Get stock quantity for a medicine at a pharmacy
function getStockQuantity($medicine_id, $pharmacy_id, $conn) {
    $stmt = $conn->prepare("SELECT quantity_in_stock FROM medicines WHERE medicine_id = ? AND pharmacy_id = ?");
    if (!$stmt) return 0;
    $stmt->bind_param("ii", $medicine_id, $pharmacy_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_data = $result->fetch_assoc();
    $stmt->close();
    return $stock_data['quantity_in_stock'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Your Cart | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/cart_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #ffffff;
        }
        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
            background: rgba(0, 0, 128, 0.7);
            border-radius: 15px;
            margin-top: 40px;
            min-height: 60vh;
        }
        .cart-header {
            font-size: 30px;
            margin-bottom: 20px;
            text-align: center;
        }
        .cart-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
            color: #000;
            position: relative;
        }
        .cart-item input[type=number] {
            width: 60px;
            padding: 5px;
        }
        .delete-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #FF4B2B;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .group-title {
            margin-top: 30px;
            font-size: 24px;
            border-bottom: 2px solid #ffffff;
        }
        .cart-actions {
            margin-top: 20px;
            display: flex;
            gap: 20px;
        }
        .checkout-btn, .cancel-btn {
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: white;
        }
        .checkout-btn {
            background: #00bfff;
        }
        .cancel-btn {
            background: #FF4B2B;
        }
        .empty-msg {
            text-align: center;
            font-size: 24px;
            margin-top: 80px;
        }
        .dashboard-btn {
            display: inline-block;
            margin-top: 40px;
            background: #007BFF;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
        }
        .stock-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .stock-warning {
            color: #ff6b35;
            font-weight: bold;
        }
        .alert-message {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
    <script>
        function updateQuantity(medicineId, pharmacyId, newQuantity) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'view_cart.php';
            
            const medicineIdInput = document.createElement('input');
            medicineIdInput.type = 'hidden';
            medicineIdInput.name = 'medicine_id';
            medicineIdInput.value = medicineId;
            
            const pharmacyIdInput = document.createElement('input');
            pharmacyIdInput.type = 'hidden';
            pharmacyIdInput.name = 'pharmacy_id';
            pharmacyIdInput.value = pharmacyId;
            
            const quantityInput = document.createElement('input');
            quantityInput.type = 'hidden';
            quantityInput.name = 'update_quantity';
            quantityInput.value = newQuantity;
            
            form.appendChild(medicineIdInput);
            form.appendChild(pharmacyIdInput);
            form.appendChild(quantityInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</head>
<body>

<div class="container">
    <h2 class="cart-header">Your Cart</h2>

    <?php 
    // Display stock message if any
    if (isset($_SESSION['stock_message'])) {
        echo '<div class="alert-message">' . htmlspecialchars($_SESSION['stock_message']) . '</div>';
        unset($_SESSION['stock_message']);
    }
    ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <p class="empty-msg">🛒 Your cart is empty.</p>
    <?php else: 
        $grouped_cart = [];
        foreach ($_SESSION['cart'] as $item) {
            if (!isset($item['pharmacy_id'], $item['medicine_id'], $item['medicine_name'], $item['price'], $item['quantity'])) continue;
            $grouped_cart[$item['pharmacy_id']][] = $item;
        }

        foreach ($grouped_cart as $pharmacy_id => $items):
            $pharmacy_name = getPharmacyName($pharmacy_id, $conn);
            $total_price = 0;
            $medicine_ids = [];

            echo '<h3 class="group-title">Pharmacy: ' . htmlspecialchars($pharmacy_name) . '</h3>';

            foreach ($items as $item):
                $medicine_id = $item['medicine_id'];
                $pharmacy_id_safe = urlencode($item['pharmacy_id']);
                $medicine_name = htmlspecialchars($item['medicine_name']);
                $price = floatval($item['price']);
                $quantity = intval($item['quantity']);
                $medicine_ids[] = urlencode($medicine_id);
                $total_price += $price * $quantity;
                
                // Get current stock
                $stock_quantity = getStockQuantity($medicine_id, $item['pharmacy_id'], $conn);
            ?>
                <div class="cart-item">
                    <h4><?= $medicine_name ?></h4>
                    <p>Price: $<?= number_format($price, 2) ?></p>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        Quantity: 
                        <input type="number" value="<?= $quantity ?>" min="1" max="<?= $stock_quantity ?>" 
                               onchange="updateQuantity(<?= $medicine_id ?>, <?= $item['pharmacy_id'] ?>, this.value)">
                    </div>
                    <div class="stock-info">
                        Available in stock: <?= $stock_quantity ?> units
                        <?php if ($quantity >= $stock_quantity): ?>
                            <span class="stock-warning">(Maximum reached)</span>
                        <?php endif; ?>
                    </div>
                    <button class="delete-btn" onclick="location.href='view_cart.php?action=delete&medicine_id=<?= urlencode($medicine_id) ?>&pharmacy_id=<?= $pharmacy_id_safe ?>'">Delete</button>
                </div>
            <?php endforeach;

            $total_price += 3; // flat delivery fee
            $user_id = $_SESSION['user_id'] ?? 0;
            $user_address = getUserAddress($user_id, $conn);
            $city = urlencode($user_address['city'] ?? '');
            $street = urlencode($user_address['street'] ?? '');
            $building = urlencode($user_address['building'] ?? '');
            $floor = urlencode($user_address['floor'] ?? '');
            $medicine_ids_str = implode(',', $medicine_ids);
            ?>
            <div class="cart-actions">
                <button class="checkout-btn"
                    onclick="location.href='checkout.php?pharmacy_id=<?= $pharmacy_id_safe ?>&total_price=<?= $total_price ?>&medicine_ids=<?= $medicine_ids_str ?>&city=<?= $city ?>&street=<?= $street ?>&building=<?= $building ?>&floor=<?= $floor ?>'">
                    Go to Checkout
                </button>
                <button class="cancel-btn"
                    onclick="if(confirm('Are you sure you want to cancel the order for <?= addslashes($pharmacy_name) ?>?')) location.href='view_cart.php?action=cancel&pharmacy_id=<?= $pharmacy_id_safe ?>'">
                    Cancel Order
                </button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="user_dashboard.php" class="dashboard-btn">⬅ Back to Dashboard</a>
</div>

</body>
</html>

<?php $conn->close(); ?>