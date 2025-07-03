<?php
session_start();
require_once 'db_connect.php';

// Handle search input
// ✅ STEP 1: VALIDATE SEARCH INPUT
// If 'medicine' is not passed via GET, terminate with a messag
if (!isset($_GET['medicine'])) {
    echo "Medicine name not provided.";
    exit();
}

// Sanitize the input to prevent SQL injection and trim extra spaces
$medicine_name = $conn->real_escape_string(trim($_GET['medicine']));

// ✅ STEP 2: FETCH MEDICINES MATCHING SEARCH TERM
// Prepare SQL query to find all medicines with names similar to the search term
$query = "
    SELECT medicines.*, pharmacy.pharmacy_name, pharmacy.location, pharmacy.pharmacy_id 
    FROM medicines
    JOIN pharmacy ON medicines.pharmacy_id = pharmacy.pharmacy_id
    WHERE medicines.medicine_name LIKE '%$medicine_name%'
";


// Execute the query
$result = $conn->query($query);

// ✅ STEP 3: INITIALIZE CART IF NOT SET
// If this is the user's first interaction, start an empty 
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = ""; // To display success message


// ✅ STEP 4: HANDLE 'ADD TO CART' FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    // Sanitize and validate all necessary input values from POST request
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    $pharmacy_id = intval($_POST['pharmacy_id'] ?? 0);
    $medicine_name_post = $_POST['medicine_name'] ?? '';
    $price = floatval($_POST['price'] ?? 0.0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $stock_available = intval($_POST['stock_available'] ?? 0);

     // ✅ Check if requested quantity exceeds stock
    if ($quantity > $stock_available) {
        $message = "Error: Requested quantity ($quantity) exceeds available stock ($stock_available).";
         // ✅ Validate all necessary fields are present and valid
    } elseif ($medicine_id && $pharmacy_id && $medicine_name_post && $price > 0 && $quantity > 0) {
        $found = false;
        $current_cart_quantity = 0;

        // Check if item already exists in cart and get current quantity
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['medicine_id'] === $medicine_id && $item['pharmacy_id'] === $pharmacy_id) {
                $current_cart_quantity = $item['quantity'];
                break;
            }
        }

        // Check if total quantity (current + new) exceeds stock
        $total_quantity = $current_cart_quantity + $quantity;
        if ($total_quantity > $stock_available) {
            $message = "Error: Total quantity ($total_quantity) would exceed available stock ($stock_available). You already have $current_cart_quantity in your cart.";
        } else {
            // Add or update cart item
            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['medicine_id'] === $medicine_id && $item['pharmacy_id'] === $pharmacy_id) {
                    // Update quantity of existing item
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
  
            // If item not already in cart, add as new entry
            if (!$found) {
                $_SESSION['cart'][] = [
                    'medicine_id' => $medicine_id,
                    'pharmacy_id' => $pharmacy_id,
                    'medicine_name' => $medicine_name_post,
                    'price' => $price,
                    'quantity' => $quantity,
                ];
            }

            $message = "Added to cart successfully.";
        }
    } else {
        $message = "Invalid form submission. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Medicines | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }

        .header-content {
            width: 80%;
            margin: auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: white;
            margin: 0;
            font-size: 2.5rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .dashboard-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .container {
            width: 80%;
            margin: auto;
            padding: 20px;
        }

        .search-title {
            color: white;
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .message {
            background: rgba(212, 237, 218, 0.95);
            color: #155724;
            border: 1px solid rgba(195, 230, 203, 0.8);
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .message.error {
            background: rgba(248, 215, 218, 0.95);
            color: #721c24;
            border: 1px solid rgba(245, 198, 203, 0.8);
        }

        .medicine {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            margin-top: 20px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .medicine:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }

        .medicine-image {
            flex-shrink: 0;
            width: 150px;
            height: 150px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .medicine-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .medicine-image img:hover {
            transform: scale(1.05);
        }

        .medicine-image .no-image {
            color: #6c757d;
            font-size: 3rem;
            opacity: 0.5;
        }

        .medicine-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .medicine-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .medicine {
                flex-direction: column;
                text-align: center;
            }
            
            .medicine-image {
                align-self: center;
            }
            
            .medicine-info {
                grid-template-columns: 1fr;
            }
        }

        .medicine h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .medicine p {
            margin: 8px 0;
            color: #34495e;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .medicine p strong {
            color: #2c3e50;
            display: inline-block;
            min-width: 120px;
        }

       

        

        .stock-info {
            color: #28a745;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
            background: rgba(40, 167, 69, 0.1);
            display: inline-block;
        }

        .stock-info.low-stock {
            color: #856404;
            background: rgba(255, 193, 7, 0.1);
        }

        .stock-info.out-of-stock {
            color: #721c24;
            background: rgba(220, 53, 69, 0.1);
        }

        .add-cart-form {
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(248, 249, 250, 0.5);
            border-radius: 15px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .add-cart-form label {
            color: #495057;
            font-weight: 600;
        }

        .add-cart-form input[type=number] {
            width: 80px;
            padding: 10px;
            font-size: 16px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .add-cart-form input[type=number]:focus {
            border-color: #007BFF;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .add-cart-form button {
            background: linear-gradient(45deg, #007BFF, #0056b3);
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }

        .add-cart-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }

        .add-cart-form button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
            padding: 20px;
        }

        .view-cart-btn {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .view-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }

        .no-result {
            margin-top: 50px;
            font-size: 1.2rem;
            color: white;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .out-of-stock-message {
            color: #721c24;
            font-weight: 600;
            margin-top: 15px;
            padding: 10px 15px;
            background: rgba(248, 215, 218, 0.8);
            border-radius: 10px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="header-content">
            <h1>🏥 MediFind</h1>
            <a href="user_dashboard.php" class="dashboard-btn">← Dashboard</a>
        </div>
    </div>

    <div class="container">
        <h2 class="search-title">Search Results for "<?php echo htmlspecialchars($medicine_name); ?>"</h2>

        <?php if ($message): ?>
            <div class="message <?php echo (strpos($message, 'Error') === 0) ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php 
                $stock = intval($row['quantity_in_stock'] ?? 0);
                $stock_class = '';
                $stock_text = '';
                
                if ($stock <= 0) {
                    $stock_class = 'out-of-stock';
                    $stock_text = 'Out of Stock';
                } elseif ($stock <= 5) {
                    $stock_class = 'low-stock';
                    $stock_text = $stock . ' left';
                } else {
                    $stock_class = '';
                    $stock_text = $stock . ' available';
                }
                ?>
                
                <div class="medicine">
                    <div class="medicine-image">
                        <?php if (!empty($row['image']) && file_exists($row['image'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['medicine_name']); ?>"
                                 onerror="this.parentElement.innerHTML='<div class=\'no-image\'>💊</div>';">
                        <?php else: ?>
                            <div class="no-image">💊</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="medicine-content">
                        <h3><?php echo htmlspecialchars($row['medicine_name']); ?></h3>
                        
                        <div class="medicine-info">
                            <div>
                                <p><strong>Pharmacy:</strong> <?php echo htmlspecialchars($row['pharmacy_name']); ?></p>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($row['price'], 2); ?></p>
                                <p><strong>Stock:</strong> <span class="stock-info <?php echo $stock_class; ?>"><?php echo $stock_text; ?></span></p>
                            </div>
                            <div>
                                <p><strong>Formulation:</strong> <?php echo htmlspecialchars($row['formulation'] ?? 'N/A'); ?></p>
                                <p><strong>Generic:</strong> <?php echo htmlspecialchars($row['generic']); ?></p>
                                
                            </div>
                        </div>
                        
                        <p><strong>Side Effects:</strong> <?php echo htmlspecialchars($row['sideEffects']); ?></p>

                        <?php if ($stock > 0): ?>
                            <form method="post" class="add-cart-form" onsubmit="return validateQuantity(this);">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="medicine_id" value="<?php echo intval($row['medicine_id']); ?>">
                                <input type="hidden" name="pharmacy_id" value="<?php echo intval($row['pharmacy_id']); ?>">
                                <input type="hidden" name="medicine_name" value="<?php echo htmlspecialchars($row['medicine_name'], ENT_QUOTES); ?>">
                                <input type="hidden" name="price" value="<?php echo htmlspecialchars($row['price']); ?>">
                                <input type="hidden" name="stock_available" value="<?php echo $stock; ?>">

                                <label for="quantity_<?php echo intval($row['medicine_id']); ?>">Quantity:</label>
                                <input type="number" 
                                       id="quantity_<?php echo intval($row['medicine_id']); ?>" 
                                       name="quantity" 
                                       min="1" 
                                       max="<?php echo $stock; ?>" 
                                       value="1" 
                                       required
                                       data-max-stock="<?php echo $stock; ?>">

                                <button type="submit">🛒 Add to Cart</button>
                            </form>
                        <?php else: ?>
                            <div class="out-of-stock-message">
                                ⚠️ This item is currently out of stock and cannot be added to cart.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-result">
                <h3>🔍 No medicines found</h3>
                <p>We couldn't find any medicines matching your search. Try searching with different keywords or check the spelling.</p>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="view_cart.php" class="view-cart-btn">🛒 View Cart</a>
        </div>
    </div>

    <script>
        function validateQuantity(form) {
            const qtyInput = form.querySelector('input[name="quantity"]');
            const maxStock = parseInt(qtyInput.getAttribute('data-max-stock'));
            const qty = parseInt(qtyInput.value);
            
            if (!qty || qty < 1) {
                alert('Please enter a valid quantity.');
                qtyInput.focus();
                return false;
            }
            
            if (qty > maxStock) {
                alert(`Quantity cannot exceed available stock (${maxStock}).`);
                qtyInput.value = maxStock;
                qtyInput.focus();
                return false;
            }
            
            return true;
        }

        // Add real-time validation as user types
        document.addEventListener('DOMContentLoaded', function() {
            const quantityInputs = document.querySelectorAll('input[name="quantity"]');
            
            quantityInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const maxStock = parseInt(this.getAttribute('data-max-stock'));
                    const currentValue = parseInt(this.value);
                    
                    if (currentValue > maxStock) {
                        this.value = maxStock;
                    }
                    
                    if (currentValue < 1) {
                        this.value = 1;
                    }
                });
            });
        });
    </script>

</body>

</html>