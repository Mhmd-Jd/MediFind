<?php
session_start();
require_once 'db_connect.php';

// Validate and sanitize incoming GET parameters
if (!isset($_GET['generic']) || !isset($_GET['location']) || !isset($_GET['medicine_id'])) {
     // If any required parameter is missing, stop execution and display an error
    echo "Generic name, location, or main medicine ID not provided.";
    exit();
}

// Trim unnecessary whitespace and retrieve inputs
$generic = trim($_GET['generic']);
$location = trim($_GET['location']);
$main_medicine_id = (int) $_GET['medicine_id']; // Cast to int for safety

// Handle Add to Cart form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicine_id'], $_POST['pharmacy_id'], $_POST['medicine_name'], $_POST['price'], $_POST['quantity'])) {
   
    // Retrieve and sanitize POST data
    $medicineId = $_POST['medicine_id'];
    $pharmacyId = $_POST['pharmacy_id'];
    $medicineName = htmlspecialchars($_POST['medicine_name']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
   
     // If cart does not exist in session, initialize it as an empty array
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
   
    // Add the selected medicine to the cart session array
    $_SESSION['cart'][] = [
        'medicine_id' => $medicineId,
        'pharmacy_id' => $pharmacyId,
        'medicine_name' => $medicineName,
        'price' => $price,
        'quantity' => $quantity,
    ];

    $_SESSION['message'] = "✅ Added to cart successfully.";
    // Redirect to same page to avoid form resubmission
    $redirectUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query([
        'generic' => $generic,
        'location' => $location,
        'medicine_id' => $main_medicine_id
    ]);
    header("Location: $redirectUrl");
    exit();
}

// ✅ SHOW SUCCESS OR STATUS MESSAGE AFTER REDIRECT
$message = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);// Clear it after use
}


// ✅ FETCH MATCHING MEDICINES FROM THE DATABASE
// Prepare a secure SQL query to prevent SQL injection
$stmt = $conn->prepare("SELECT medicines.*, pharmacy.pharmacy_name, pharmacy.location 
                        FROM medicines
                        JOIN pharmacy ON medicines.pharmacy_id = pharmacy.pharmacy_id
                        WHERE medicines.generic = ? AND pharmacy.location = ?
                        ORDER BY medicines.price ASC");
$stmt->bind_param("ss", $generic, $location);
$stmt->execute(); // Execute the statement and fetch the result set
$result = $stmt->get_result();// Result will be used in HTML section
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alternatives for <?php echo htmlspecialchars($generic); ?> in <?php echo htmlspecialchars($location); ?> | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .search-info {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-info h2 {
            color: #4a5568;
            font-size: 1.8rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .medicine-grid {
            display: grid;
            gap: 25px;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }

        .medicine {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .medicine:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .medicine::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .main-medicine {
            background: linear-gradient(135deg, rgba(134, 239, 172, 0.3) 0%, rgba(74, 222, 128, 0.2) 100%);
            border: 2px solid #86efac;
            box-shadow: 0 8px 32px rgba(134, 239, 172, 0.3);
        }

        .main-medicine::before {
            background: linear-gradient(90deg, #86efac, #4ade80);
        }

        .badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(74, 222, 128, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .medicine h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            padding-right: 120px;
        }

        .medicine-info {
            display: grid;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .info-item i {
            width: 20px;
            color: #667eea;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #4a5568;
            min-width: 100px;
        }

        .info-value {
            color: #2d3748;
        }

        .price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #10b981;
        }

        .cart-form {
            background: rgba(248, 250, 252, 0.8);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid rgba(226, 232, 240, 0.5);
        }

        .quantity-container {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .quantity-label {
            font-weight: 600;
            color: #4a5568;
        }

        .quantity-input {
            width: 80px;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
        }

        .quantity-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .view-cart-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .view-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .dashboard-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            margin-top: 40px;
            padding: 15px 30px;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .message-box {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(5, 150, 105, 0.1));
            border: 2px solid #10b981;
            color: #065f46;
            padding: 20px;
            margin: 30px 0;
            border-radius: 15px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .no-result {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin: 40px 0;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .no-result i {
            font-size: 4rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .no-result h3 {
            font-size: 1.5rem;
            color: #4a5568;
            margin-bottom: 10px;
        }

        .no-result p {
            color: #718096;
            font-size: 1.1rem;
        }

        .back-section {
            text-align: center;
            margin-top: 50px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .medicine-grid {
                grid-template-columns: 1fr;
            }
            
            .medicine h3 {
                padding-right: 20px;
                font-size: 1.3rem;
            }
            
            .badge {
                position: static;
                display: inline-block;
                margin-bottom: 15px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .success-animation {
            animation: successPulse 0.6s ease;
        }

        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-pills"></i> MediFind</h1>
            <p>Find the best medicine alternatives in your area</p>
        </div>

        <div class="search-info">
            <h2>
                <i class="fas fa-search"></i>
                Alternatives for "<?php echo htmlspecialchars($generic); ?>" in "<?php echo htmlspecialchars($location); ?>"
            </h2>
        </div>

        <?php if ($message): ?>
            <div class="message-box">
                <i class="fas fa-check-circle" style="font-size: 20px; color: #10b981;"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php
        if ($result && $result->num_rows > 0) {
            echo '<div class="medicine-grid">';
            while ($row = $result->fetch_assoc()) {
                $isMain = ($row['medicine_id'] == $main_medicine_id);
        ?>
                <div class="medicine <?php echo $isMain ? 'main-medicine' : ''; ?>">
                    <?php if ($isMain): ?>
                        <div class="badge">
                            <i class="fas fa-star"></i> Main Medicine
                        </div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($row['medicine_name']); ?></h3>
                    
                    <div class="medicine-info">
                        <div class="info-item">
                            <i class="fas fa-clinic-medical"></i>
                            <span class="info-label">Pharmacy:</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['pharmacy_name']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span class="info-label">Location:</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['location']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span class="info-label">Side Effects:</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['sideEffects']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <span class="info-label">Generic:</span>
                            <span class="info-value"><?php echo htmlspecialchars($row['generic']); ?></span>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span class="info-label">Price:</span>
                            <span class="info-value price">$<?php echo number_format($row['price'], 2); ?></span>
                        </div>
                    </div>

                    <form method="POST" action="" class="cart-form" onsubmit="handleAddToCart(this)">
                        <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                        <input type="hidden" name="pharmacy_id" value="<?php echo $row['pharmacy_id']; ?>">
                        <input type="hidden" name="medicine_name" value="<?php echo htmlspecialchars($row['medicine_name']); ?>">
                        <input type="hidden" name="price" value="<?php echo $row['price']; ?>">

                        <div class="quantity-container">
                            <span class="quantity-label">
                                <i class="fas fa-sort-numeric-up"></i> Quantity:
                            </span>
                            <input type="number" 
                                   id="quantity_<?php echo $row['medicine_id']; ?>" 
                                   name="quantity" 
                                   min="1"
                                   max="<?php echo $row['quantity_in_stock']; ?>" 
                                   value="1" 
                                   required
                                   class="quantity-input">
                            <span style="color: #718096; font-size: 14px;">
                                (Max: <?php echo $row['quantity_in_stock']; ?>)
                            </span>
                        </div>

                        <div class="button-group">
                            <button type="submit" class="btn add-to-cart-btn">
                                <i class="fas fa-cart-plus"></i>
                                Add to Cart
                            </button>
                            <a href="view_cart.php" class="btn view-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                                View Cart
                            </a>
                        </div>
                    </form>
                </div>
        <?php
            }
            echo '</div>';
        } else {
            echo '<div class="no-result">';
            echo '<i class="fas fa-search"></i>';
            echo '<h3>No Alternatives Found</h3>';
            echo '<p>Sorry, no alternatives found for this generic medicine in this location.</p>';
            echo '</div>';
        }
        ?>

        <div class="back-section">
            <a href="user_dashboard.php" class="btn dashboard-btn">
                <i class="fas fa-home"></i>
                Go Back to dashboard
            </a>
        </div>
    </div>

    <script>
        function handleAddToCart(form) {
            // Add loading state
            const submitBtn = form.querySelector('.add-to-cart-btn');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            submitBtn.disabled = true;
            form.classList.add('loading');
            
            // Add success animation to medicine card after form submission
            setTimeout(() => {
                form.closest('.medicine').classList.add('success-animation');
            }, 100);
        }

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add entrance animations
        window.addEventListener('load', function() {
            const medicines = document.querySelectorAll('.medicine');
            medicines.forEach((medicine, index) => {
                medicine.style.opacity = '0';
                medicine.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    medicine.style.transition = 'all 0.6s ease';
                    medicine.style.opacity = '1';
                    medicine.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });

        // Enhanced quantity input handling
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max'));
                const value = parseInt(this.value);
                
                if (value > max) {
                    this.value = max;
                    this.style.borderColor = '#ef4444';
                    setTimeout(() => {
                        this.style.borderColor = '#e2e8f0';
                    }, 1000);
                }
            });
        });
    </script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>