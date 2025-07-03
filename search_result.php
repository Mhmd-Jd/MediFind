<?php
session_start();
require_once 'db_connect.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize message variables for feedback
$addedMessage = '';
$notifyMessage = '';
$messageType = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle 'Add to Cart' submission
    if (isset($_POST['add_to_cart'])) {
        // Sanitize and retrieve input values
        $medicine_id = filter_input(INPUT_POST, 'medicine_id', FILTER_SANITIZE_NUMBER_INT);
        $pharmacy_id = filter_input(INPUT_POST, 'pharmacy_id', FILTER_SANITIZE_NUMBER_INT);
        $medicine_name = htmlspecialchars(trim($_POST['medicine_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_INT);

        // Validate that all inputs are present and valid
        if ($medicine_id && $pharmacy_id && $medicine_name && $price && $quantity) {
            // Server-side validation: Check if requested quantity is available in stock
            $stock_check_stmt = $conn->prepare("SELECT quantity_in_stock FROM medicines WHERE medicine_id = ? AND pharmacy_id = ?");
            $stock_check_stmt->bind_param("ii", $medicine_id, $pharmacy_id);
            $stock_check_stmt->execute();
            $stock_result = $stock_check_stmt->get_result();

            if ($stock_row = $stock_result->fetch_assoc()) {
                $available_stock = $stock_row['quantity_in_stock'];

                // Validate quantity against available stock
                if ($quantity > $available_stock) {
                    $addedMessage = "Sorry, only $available_stock units available in stock. Please adjust the quantity.";
                    $messageType = 'error';
                } elseif ($quantity <= 0) {
                    $addedMessage = "Quantity must be at least 1.";
                    $messageType = 'error';
                } else {
                    // Check if medicine already exists in cart from same pharmacy
                    $found_in_cart = false;
                    $total_cart_quantity = 0;

                    foreach ($_SESSION['cart'] as $index => $cart_item) {
                        if ($cart_item['medicine_id'] == $medicine_id && $cart_item['pharmacy_id'] == $pharmacy_id) {
                            $total_cart_quantity = $cart_item['quantity'] + $quantity;

                            // Check if total quantity (existing + new) exceeds stock,inform user
                            if ($total_cart_quantity > $available_stock) {
                                $remaining_stock = $available_stock - $cart_item['quantity'];
                                if ($remaining_stock > 0) {
                                    $addedMessage = "You already have {$cart_item['quantity']} units in cart. You can only add $remaining_stock more units.";
                                } else {
                                    $addedMessage = "You already have the maximum available quantity ({$cart_item['quantity']} units) in your cart.";
                                }
                                $messageType = 'warning';
                            } else {
                                // Update existing cart item
                                $_SESSION['cart'][$index]['quantity'] = $total_cart_quantity;
                                $addedMessage = "Cart updated! Total quantity: $total_cart_quantity units.";
                                $messageType = 'success';
                            }
                            $found_in_cart = true;
                            break;
                        }
                    }
                    // If item not already in cart, add new item
                    if (!$found_in_cart) {
                        // Add new item to cart
                        $_SESSION['cart'][] = [
                            'medicine_id' => $medicine_id,
                            'pharmacy_id' => $pharmacy_id,
                            'medicine_name' => $medicine_name,
                            'price' => $price,
                            'quantity' => $quantity
                        ];
                        $addedMessage = "Medicine added to cart successfully! ($quantity units)";
                        $messageType = 'success';
                    }
                }
            } else {
                $addedMessage = "Medicine not found or unavailable.";
                $messageType = 'error';
            }
            $stock_check_stmt->close();
        } else {
            $addedMessage = "Invalid input data. Please try again.";
            $messageType = 'error';
        }

        // Handle 'Notify Me' reques
    } elseif (isset($_POST['notify_me'])) {
        $medicine_name = htmlspecialchars(trim($_POST['medicine_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars(trim($_POST['location'] ?? ''), ENT_QUOTES, 'UTF-8');
        $user_id = $_SESSION['user_id'] ?? null;

        // Validate inputs
        if (empty($medicine_name) || empty($location) || !$user_id) {
            $notifyMessage = "Medicine name, location, and user login are required.";
            $messageType = 'error';
        } else {
            // Get user's email
            $email = $_SESSION['email'] ?? '';
            if (empty($email)) {
                $email_stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
                $email_stmt->bind_param("i", $user_id);
                $email_stmt->execute();
                $email_result = $email_stmt->get_result();
                if ($email_row = $email_result->fetch_assoc()) {
                    $email = $email_row['email'];
                }
                $email_stmt->close();
            }

            // Find medicine and pharmacy within the location
            $stmt = $conn->prepare("SELECT medicine_id, pharmacy_id FROM medicines WHERE medicine_name = ? AND pharmacy_id IN (SELECT pharmacy_id FROM pharmacy WHERE location LIKE ?)");
            $likeLocation = '%' . $location . '%';
            $stmt->bind_param("ss", $medicine_name, $likeLocation);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $medicine_id = $row['medicine_id'];
                $pharmacy_id = $row['pharmacy_id'];

                // Check existing request
                $check_stmt = $conn->prepare("SELECT * FROM medicine_requests WHERE medicine_id = ? AND pharmacy_id = ? AND user_id = ?");
                $check_stmt->bind_param("iii", $medicine_id, $pharmacy_id, $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows == 0) {
                    $insert_stmt = $conn->prepare("INSERT INTO medicine_requests (medicine_id, user_id, pharmacy_id, email, request_time) VALUES (?, ?, ?, ?, NOW())");
                    $insert_stmt->bind_param("iiis", $medicine_id, $user_id, $pharmacy_id, $email);
                    $insert_stmt->execute();

                    if ($insert_stmt->affected_rows > 0) {
                        $notifyMessage = "Notification request sent successfully for '$medicine_name'.";
                        $messageType = 'success';
                    } else {
                        $notifyMessage = "Failed to send notification request.";
                        $messageType = 'error';
                    }
                    $insert_stmt->close();
                } else {
                    $notifyMessage = "You have already requested this medicine from this pharmacy.";
                    $messageType = 'warning';
                }
                $check_stmt->close();
            } else {
                $notifyMessage = "Medicine not found in any pharmacy at this location.";
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Validate search parameters
if (!isset($_GET['medicine']) || !isset($_GET['location'])) {
    echo "Invalid search parameters.";
    exit();
}

// Sanitize search inputs
$medicine_name = $conn->real_escape_string(trim($_GET['medicine']));
$location = $conn->real_escape_string(trim($_GET['location']));

// Search for medicines in database by name and location
$query = "SELECT medicines.*, pharmacy.pharmacy_name, pharmacy.location, pharmacy.pharmacy_id, medicines.image 
          FROM medicines
          JOIN pharmacy ON medicines.pharmacy_id = pharmacy.pharmacy_id
          WHERE medicines.medicine_name LIKE '%$medicine_name%'
          AND pharmacy.location LIKE '%$location%'
          ORDER BY medicines.medicine_name, pharmacy.pharmacy_name";

$result = $conn->query($query);
// Get generic name and first medicine_id
$generic_value = $medicine_name;
$medicine_id = null;

if ($result && $result->num_rows > 0) {
    $first_row = $result->fetch_assoc();
    $generic_value = $first_row['generic'];
    $medicine_id = $first_row['medicine_id'];
    $result->data_seek(0);
}

// Function to get current cart quantity for a specific medicine from specific pharmacy
function getCartQuantity($medicine_id, $pharmacy_id)
{
    if (!isset($_SESSION['cart']))
        return 0;

    foreach ($_SESSION['cart'] as $item) {
        if ($item['medicine_id'] == $medicine_id && $item['pharmacy_id'] == $pharmacy_id) {
            return $item['quantity'];
        }
    }
    return 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --error-color: #dc2626;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --border-radius: 8px;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .page-container {
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(219, 234, 254, 0.3) 100%);
        }

        .header {
            background: var(--card-background);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--card-background);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--background-color);
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-header {
            background: var(--card-background);
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
        }

        .search-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .search-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fef2f2;
            color: var(--error-color);
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: #fffbeb;
            color: var(--warning-color);
            border: 1px solid #fed7aa;
        }

        .medicine-grid {
            display: grid;
            gap: 1.5rem;
        }

        .medicine-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
        }

        .medicine-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .medicine-content {
            padding: 1.5rem;
        }

        .medicine-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .medicine-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .stock-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .stock-in {
            background: #dcfce7;
            color: var(--success-color);
        }

        .stock-out {
            background: #fef2f2;
            color: var(--error-color);
        }

        .stock-low {
            background: #fffbeb;
            color: var(--warning-color);
        }

        .medicine-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }

        .medicine-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .detail-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-weight: 500;
            color: var(--text-primary);
        }

        .price {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--success-color);
        }

        .stock-info {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .medicine-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .quantity-input {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-input label {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .quantity-input input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            text-align: center;
        }

        .quantity-input input:invalid {
            border-color: var(--error-color);
        }

        .cart-info {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .no-results i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .cart-summary {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .header-content {
                padding: 0 1rem;
            }

            .search-header {
                padding: 1.5rem;
            }

            .medicine-details {
                grid-template-columns: 1fr;
            }

            .medicine-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .cart-summary {
                position: static;
                margin-top: 2rem;
            }
        }

        @media (max-width: 480px) {
            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-pills"></i> MediFind
                </div>
                <div class="header-actions">
                    <?php if ($medicine_id !== null): ?>
                        <a class="btn btn-secondary"
                            href="alternatives.php?generic=<?php echo urlencode($generic_value); ?>&location=<?php echo urlencode($location); ?>&medicine_id=<?php echo urlencode($medicine_id); ?>">
                            <i class="fas fa-search"></i> View Alternatives
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-secondary"
                        href="view_all_medicines.php?medicine=<?php echo urlencode($medicine_name); ?>">
                        <i class="fas fa-map-marker-alt"></i> All Lebanon
                    </a>
                    <a class="btn btn-primary" href="view_cart.php">
                        <i class="fas fa-shopping-cart"></i> View Cart
                    </a>

                    <a href="user_dashboard.php" class="btn">← Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main class="main-content">
            <div class="search-header">
                <h1 class="search-title">
                    Search Results for "<?php echo htmlspecialchars($_GET['medicine']); ?>"
                </h1>
                <p class="search-subtitle">
                    <i class="fas fa-map-marker-alt"></i>
                    Location: <?php echo htmlspecialchars($_GET['location']); ?>
                </p>
            </div>


            <!-- If an item was added to the cart ($addedMessage), this displays a styled message with an icon -->
            <?php if (!empty($addedMessage)): ?> 
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i
                        class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                    <?php echo $addedMessage; ?>
                </div>
            <?php endif; ?>

            <!-- If the user submitted a "Notify me" request, show the corresponding message -->
            <?php if (!empty($notifyMessage)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i
                        class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                    <?php echo $notifyMessage; ?>
                </div>
            <?php endif; ?>

            <div class="medicine-grid">
                <?php
                $medicineFound = false;
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $medicineFound = true;
                        $in_stock = $row['quantity_in_stock'];
                        $cart_quantity = getCartQuantity($row['medicine_id'], $row['pharmacy_id']);
                        $available_to_add = $in_stock - $cart_quantity;
                        ?>
                        <div class="medicine-card">
                            <div class="medicine-content">
                                <div class="medicine-header">
                                    <h3 class="medicine-name"><?php echo htmlspecialchars($row['medicine_name']); ?></h3>
                                    <span class="stock-badge <?php
                                    if ($in_stock <= 0)
                                        echo 'stock-out';
                                    elseif ($in_stock <= 10)
                                        echo 'stock-low';
                                    else
                                        echo 'stock-in';
                                    ?>">
                                        <?php
                                        if ($in_stock <= 0)
                                            echo 'Out of Stock';
                                        elseif ($in_stock <= 10)
                                            echo "Low Stock ($in_stock left)";
                                        else
                                            echo 'In Stock';
                                        ?>
                                    </span>
                                </div>

                                <?php if (!empty($row['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>"
                                        alt="<?php echo htmlspecialchars($row['medicine_name']); ?>" class="medicine-image">
                                <?php endif; ?>

                                <div class="medicine-details">
                                    <div class="detail-item">
                                        <span class="detail-label">Pharmacy</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['pharmacy_name']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Location</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['location']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Generic</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($row['generic']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Price</span>
                                        <span class="detail-value price">$<?php echo number_format($row['price'], 2); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Stock Available</span>
                                        <span class="detail-value"><?php echo $in_stock; ?> units</span>
                                        <?php if ($cart_quantity > 0): ?>
                                            <span class="stock-info">
                                                <i class="fas fa-shopping-cart"></i> <?php echo $cart_quantity; ?> in cart
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($row['sideEffects'])): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Side Effects</span>
                                            <span class="detail-value"><?php echo htmlspecialchars($row['sideEffects']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="medicine-actions">
                                    <?php if ($available_to_add > 0): ?>
                                        <form method="POST"
                                            style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                            <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                            <input type="hidden" name="pharmacy_id" value="<?php echo $row['pharmacy_id']; ?>">
                                            <input type="hidden" name="medicine_name"
                                                value="<?php echo htmlspecialchars($row['medicine_name']); ?>">
                                            <input type="hidden" name="price" value="<?php echo $row['price']; ?>">

                                            <div class="quantity-input">
                                                <label
                                                    for="quantity_<?php echo $row['medicine_id']; ?>_<?php echo $row['pharmacy_id']; ?>">Qty:</label>
                                                <input type="number"
                                                    id="quantity_<?php echo $row['medicine_id']; ?>_<?php echo $row['pharmacy_id']; ?>"
                                                    name="quantity" value="1" min="1" max="<?php echo $available_to_add; ?>"
                                                    title="Maximum available: <?php echo $available_to_add; ?> units" required>
                                                <div class="cart-info">
                                                    Max: <?php echo $available_to_add; ?> units
                                                </div>
                                            </div>

                                            <button type="submit" name="add_to_cart" class="btn btn-primary">
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </form>
                                    <?php elseif ($cart_quantity >= $in_stock && $in_stock > 0): ?>
                                        <div style="color: var(--warning-color); font-weight: 500;">
                                            <i class="fas fa-info-circle"></i> Maximum quantity already in cart
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                            <input type="hidden" name="medicine_name"
                                                value="<?php echo htmlspecialchars($row['medicine_name']); ?>">
                                            <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                                            <input type="hidden" name="pharmacy_id" value="<?php echo $row['pharmacy_id']; ?>">

                                            <button type="submit" name="notify_me" class="btn btn-secondary">
                                                <i class="fas fa-bell"></i> Notify When Available
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No medicines found</h3>
                        <p>We couldn't find any medicines matching your search criteria.</p>
                        <p>Try adjusting your search terms or location.</p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Client-side quantity validation
        document.addEventListener('DOMContentLoaded', function () {
            const quantityInputs = document.querySelectorAll('input[name="quantity"]');

            quantityInputs.forEach(input => {
                input.addEventListener('input', function () {
                    const min = parseInt(this.min);
                    const max = parseInt(this.max);
                    const value = parseInt(this.value);

                    if (value < min) {
                        this.value = min;
                    } else if (value > max) {
                        this.value = max;
                        alert(`Maximum available quantity is ${max} units.`);
                    }
                });

                // Prevent typing invalid characters
                input.addEventListener('keypress', function (e) {
                    // Allow only numbers and control keys
                    if (!/[\d]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                        e.preventDefault();
                    }
                });

                // Form submission validation
                const form = input.closest('form');
                if (form) {
                    form.addEventListener('submit', function (e) {
                        const quantity = parseInt(input.value);
                        const max = parseInt(input.max);

                        if (quantity > max) {
                            e.preventDefault();
                            alert(`Cannot add ${quantity} units. Maximum available is ${max} units.`);
                            input.focus();
                            return false;
                        }

                        if (quantity < 1) {
                            e.preventDefault();
                            alert('Quantity must be at least 1.');
                            input.focus();
                            return false;
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>