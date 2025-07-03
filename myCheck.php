<?php
require_once 'db_connect.php';

// Retrieve booking ID from URL parameters
$booking_id = $_GET['booking_id'] ?? null;
if (!$booking_id) {
    die("Missing booking_id");
}

// Step 1: Get booking details
$sql = "SELECT * FROM bookings WHERE booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}

$booking = $result->fetch_assoc();

$user_id = $booking['user_id'];
$pharmacy_id = $booking['pharmacy_id'];
$medicine_ids = explode(',', $booking['medicine_ids']);
$total_price = $booking['total_price'];

// Step 2: Get pharmacy name
$pharmacy_name = '';
$stmt = $conn->prepare("SELECT pharmacy_name FROM pharmacy WHERE pharmacy_id = ?");
$stmt->bind_param("i", $pharmacy_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pharmacy_name = $row['pharmacy_name'];
}

// Step 3: Get user shipping address
$city = $street = $building = $floor = '';
$stmt = $conn->prepare("SELECT city, street, building, floor FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $city = $row['city'];
    $street = $row['street'];
    $building = $row['building'];
    $floor = $row['floor'];
}

// Step 4: Get medicine names
$medicine_ids = array_map('trim', $medicine_ids);
$placeholders = implode(',', array_fill(0, count($medicine_ids), '?'));
$types = str_repeat('i', count($medicine_ids));

$medicine_display_info = '';
$total_items = 0;

if (count($medicine_ids) > 0) {
    $query = "SELECT medicine_id, medicine_name FROM medicines WHERE medicine_id IN ($placeholders)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$medicine_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[$row['medicine_id']] = $row['medicine_name'];
    }
    
    // Read quantities from POST (or assume 1 if missing)
    $quantities = $_POST['quantities'] ?? [];
    
    foreach ($medicine_ids as $id) {
        $name = $medicines[$id] ?? 'Unknown';
        $qty = $quantities[$id] ?? 1;
        $medicine_display_info .= "$name";
        $total_items += $qty;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Summary</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header h1 {
            font-size: 2.2em;
            font-weight: 300;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header .subtitle {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px;
        }

        .order-summary {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            border-radius: 15px;
            padding: 30px;
            border: 1px solid rgba(79, 172, 254, 0.1);
            position: relative;
            overflow: hidden;
        }

        .order-summary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
        }

        .info-grid {
            display: grid;
            gap: 25px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            border-left-color: #4facfe;
        }

        .info-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 1.2em;
            flex-shrink: 0;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            font-size: 1.1em;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .info-value {
            color: #666;
            font-size: 1em;
            line-height: 1.6;
        }

        .total-section {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .total-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }

        .total-amount {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
            position: relative;
            z-index: 1;
        }

        .total-label {
            font-size: 1.1em;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .status-badge {
            display: inline-block;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .content {
                padding: 20px;
            }
            
            .order-summary {
                padding: 20px;
            }
            
            .info-item {
                padding: 15px;
            }
            
            .info-icon {
                width: 35px;
                height: 35px;
                margin-right: 15px;
            }
            
            .total-amount {
                font-size: 2em;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .action-section {
            margin-top: 30px;
            text-align: center;
        }

        .dashboard-btn {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .dashboard-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .dashboard-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }

        .dashboard-btn:hover::before {
            left: 100%;
        }

        .dashboard-btn:active {
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-receipt"></i> Order Summary</h1>
            <p class="subtitle">Your prescription order details</p>
        </div>
        
        <div class="content">
            <div class="order-summary">
                <div class="info-grid">
                    <div class="info-item fade-in">
                        <div class="info-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">
                                Pharmacy
                                <span class="status-badge">Verified</span>
                            </div>
                            <div class="info-value"><?= htmlspecialchars($pharmacy_name) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item fade-in">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Shipping Address</div>
                            <div class="info-value"><?= htmlspecialchars("$city, $street, Bldg $building, Floor $floor") ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item fade-in">
                        <div class="info-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Medications</div>
                            <div class="info-value"><?= htmlspecialchars($medicine_display_info) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item fade-in">
                        <div class="info-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Total Items</div>
                            <div class="info-value"><?= $total_items ?> item<?= $total_items !== 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="total-section">
                    <div class="total-amount">$<?= number_format($total_price, 2) ?></div>
                    <div class="total-label">Total Amount</div>
                </div>
                
                <div class="action-section">
                    <a href="user_dashboard.php" class="dashboard-btn">
                        <i class="fas fa-tachometer-alt"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>