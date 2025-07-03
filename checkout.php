<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page or show message
    header("Location: login.php");  // Adjust to your login page URL
    exit();
}

require_once 'db_connect.php';

// Function to get pharmacy name by pharmacy_id
function getPharmacyName($pharmacy_id, $conn)
{
    $query = "SELECT pharmacy_name FROM pharmacy WHERE pharmacy_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $pharmacy_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pharmacy = $result->fetch_assoc();
    return $pharmacy['pharmacy_name'] ?? 'Unknown Pharmacy';
}

// Function to get the user's address from the database (assuming the info is in the users table)
function getUserAddress($user_id, $conn)
{
    $query = "SELECT city, street, building, floor FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc(); // returns an associative array
}

// Function to decrement stock for each medicine by the ordered quantity
function decrementStock($medicine_id, $quantity, $conn)
{
    // Check current stock
    $query = "SELECT quantity_in_stock FROM medicines WHERE medicine_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine = $result->fetch_assoc();

    if ($medicine && $medicine['quantity_in_stock'] >= $quantity) {
        // Decrement stock by the ordered quantity
        $updateQuery = "UPDATE medicines SET quantity_in_stock = quantity_in_stock - ? WHERE medicine_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $quantity, $medicine_id);
        return $updateStmt->execute();
    } else {
        return false; // out of stock or insufficient stock
    }
}

// Function to create a booking and get booking_id
function createBooking($user_id, $pharmacy_id, $total_price, $medicine_ids, $conn)
{
    $query = "INSERT INTO bookings (user_id, pharmacy_id, medicine_ids, total_price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisd", $user_id, $pharmacy_id, $medicine_ids, $total_price);

    if ($stmt->execute()) {
        return $conn->insert_id; // Return the booking_id if the booking is created
    }
    return false; // Return false if there was an issue
}

// Function to create order status in the order_tracker table
function createOrderStatus($booking_id, $conn)
{
    // Insert a new entry in the order_tracker table with status 'Order Placed'
    $query = "INSERT INTO order_tracker (booking_id, status, status_time) VALUES (?, 'Order Placed', NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    return $stmt->execute();
}

// Function to get medicine name by medicine_id
function getMedicineName($medicine_id, $conn)
{
    $query = "SELECT medicine_name FROM medicines WHERE medicine_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medicine = $result->fetch_assoc();
    return $medicine['medicine_name'] ?? 'Unknown Medicine';
}

// Validate input
if (!isset($_GET['pharmacy_id']) || !isset($_GET['total_price'])) {
    die("Pharmacy ID and total price required");
}

$pharmacy_id = $_GET['pharmacy_id'];
$total_price = $_GET['total_price'];

// Get pharmacy name
$pharmacy_name = getPharmacyName($pharmacy_id, $conn);

// Fetch user address information (assuming user_id is in the session)
$user_id = $_SESSION['user_id'];
$user_address = getUserAddress($user_id, $conn);

// Extract the address values, with defaults to 'N/A' if not found
$city = $user_address['city'] ?? 'N/A';
$street = $user_address['street'] ?? 'N/A';
$building = $user_address['building'] ?? 'N/A';
$floor = $user_address['floor'] ?? 'N/A';

// Get medicine names and quantities from cart for this pharmacy
$medicine_display_info = 'No medicines found';
$total_items = 0;
if (!empty($_SESSION['cart'])) {
    $pharmacy_cart = array_filter($_SESSION['cart'], function ($item) use ($pharmacy_id) {
        return $item['pharmacy_id'] == $pharmacy_id;
    });

    if (!empty($pharmacy_cart)) {
        $medicine_info_array = [];
        foreach ($pharmacy_cart as $item) {
            $medicine_name = getMedicineName($item['medicine_id'], $conn);
            $quantity = $item['quantity'];
            $medicine_info_array[] = $medicine_name . " (Qty: " . $quantity . ")";
            $total_items += $quantity;
        }
        $medicine_display_info = implode(', ', $medicine_info_array);
    }
}

// When placing the order
if (isset($_POST['place_order'])) {
    if (!empty($_SESSION['cart'])) {
        // Filter items for this pharmacy
        $pharmacy_cart = array_filter($_SESSION['cart'], function ($item) use ($pharmacy_id) {
            return $item['pharmacy_id'] == $pharmacy_id;
        });

        if (!empty($pharmacy_cart)) {
            $medicine_ids_array = [];
            $out_of_stock = false;

            // First, check if all items have sufficient stock
            foreach ($pharmacy_cart as $item) {
                $medicine_id = $item['medicine_id'];
                $ordered_quantity = $item['quantity'];

                // Check current stock
                $query = "SELECT quantity_in_stock, medicine_name FROM medicines WHERE medicine_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $medicine_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $medicine = $result->fetch_assoc();

                if (!$medicine || $medicine['quantity_in_stock'] < $ordered_quantity) {
                    $medicine_name = $medicine['medicine_name'] ?? 'Unknown Medicine';
                    $available_stock = $medicine['quantity_in_stock'] ?? 0;
                    echo "<script>
                        alert('Insufficient stock for {$medicine_name}! You ordered {$ordered_quantity} but only {$available_stock} available.');
                        window.history.back();
                    </script>";
                    exit;
                }
            }

            // If all items have sufficient stock, proceed with order
            foreach ($pharmacy_cart as $item) {
                $medicine_id = $item['medicine_id'];
                $ordered_quantity = $item['quantity'];

                // Decrement stock by the ordered quantity
                if (decrementStock($medicine_id, $ordered_quantity, $conn)) {
                    $medicine_ids_array[] = $medicine_id;
                } else {
                    $medicine_name = getMedicineName($medicine_id, $conn);
                    echo "<script>
                        alert('Error updating stock for {$medicine_name}');
                        window.history.back();
                    </script>";
                    exit;
                }
            }

            $medicine_ids_str = implode(',', $medicine_ids_array);

            // Create a booking
            $booking_id = createBooking($user_id, $pharmacy_id, $total_price, $medicine_ids_str, $conn);

            if ($booking_id) {
                // Create an order status in order_tracker
                if (!createOrderStatus($booking_id, $conn)) {
                    echo "<script>alert('Error creating order status');</script>";
                    exit;
                }

                // Fetch medicine names for the order using the booking_id
                $ordered_medicines = [];
                foreach ($pharmacy_cart as $item) {
                    $medicine_name = getMedicineName($item['medicine_id'], $conn);
                    $ordered_medicines[] = $medicine_name . " (Qty: " . $item['quantity'] . ")";
                }

                // Store the ordered medicine names in session for use in the checkout page
                $_SESSION['ordered_medicines'] = $ordered_medicines;

                // Remove items for this pharmacy from the cart
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($pharmacy_id) {
                    return $item['pharmacy_id'] != $pharmacy_id;
                });

                echo "<script>
                    alert('Order placed successfully! Total items: {$total_items}');
                    window.location.href = 'user_dashboard.php';
                </script>";
                exit;
            } else {
                echo "<script>alert('There was a problem placing your order.');</script>";
            }
        } else {
            echo "<script>alert('No items found for this pharmacy in your cart.');</script>";
        }
    } else {
        echo "<script>alert('Your cart is empty.');</script>";
    }
}

// When cancelling the order
if (isset($_POST['cancel_order'])) {
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function ($item) use ($pharmacy_id) {
        return $item['pharmacy_id'] != $pharmacy_id;
    });

    echo "<script>
        alert('Order has been canceled.');
        window.location.href = 'user_dashboard.php';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Checkout</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background: url('images/checkout_bg.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .btn {
            padding: 12px 25px;
            text-decoration: none;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            margin-right: 10px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-cancel {
            background: #f44336;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .order-summary {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .address-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }

        h2 {
            color: #333;
        }

        .total-price {
            font-size: 20px;
            font-weight: bold;
            color: #2e7d32;
        }
    </style>

</head>

<body>

    <div class="container">
        <h2>Checkout - <?php echo htmlspecialchars($pharmacy_name); ?></h2>

        <div class="order-summary">
            <h3>Order Summary</h3>
            <p><strong>Medicines:</strong> <?php echo htmlspecialchars($medicine_display_info); ?></p>
            <p class="total-price">Total Payment: $<?php echo number_format($total_price, 2); ?></p>
            <p><em>(This includes a $3 delivery charge)</em></p>
        </div>

        <div class="address-info">
            <h3>Delivery Address</h3>
            <p><strong>City:</strong> <?php echo htmlspecialchars($city); ?></p>
            <p><strong>Street:</strong> <?php echo htmlspecialchars($street); ?></p>
            <p><strong>Building:</strong> <?php echo htmlspecialchars($building); ?></p>
            <p><strong>Floor:</strong> <?php echo htmlspecialchars($floor); ?></p>
        </div>

        <form method="POST">
            <button type="submit" name="place_order" class="btn">Place Order</button>
            <button type="submit" name="cancel_order" class="btn btn-cancel">Cancel Order</button>
        </form>
    </div>

</body>

</html>

<?php
$conn->close();
?>