<?php
session_start();
require_once 'db_connect.php';

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];

// Function to retrieve user details (name, address info)
function getUserDetails($user_id, $conn)
{
    $query = "SELECT first_name, city, street, building, floor FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Bind user_id as integer
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc() ?? null; // Return user details or null if not found
}

// Fetch user details for display
$user_details = getUserDetails($user_id, $conn);
$first_name = $user_details['first_name'] ?? 'Guest'; // Fallback to 'Guest' if name not found

// Fetch all order statuses with pharmacy names
function getAllOrderStatuses($user_id, $conn)
{
    $query = "SELECT ot.status, ot.status_time, ot.booking_id, b.pharmacy_id, b.total_price, p.pharmacy_name 
              FROM order_tracker ot 
              JOIN bookings b ON ot.booking_id = b.booking_id
              JOIN pharmacy p ON b.pharmacy_id = p.pharmacy_id
              WHERE b.user_id = ? 
              ORDER BY ot.status_time DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Store each order record in an array
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    return $orders;
}

// Fetch all order statuses for this user
$order_statuses = getAllOrderStatuses($user_id, $conn);

// Separate orders into active (not delivered) and history (delivered)
$active_orders = [];
$history_orders = [];

foreach ($order_statuses as $order) {
    if (strtolower($order['status']) === 'delivered') {
        $history_orders[] = $order; // Delivered orders go into history
    } else {
        $active_orders[] = $order; // Others are active
    }
}

// Function to get medicine names for a specific order based on booking_id
function getMedicineNamesForOrder($booking_id, $conn)
{
    // step 1: Fetch the medicine_ids for the given booking
    $query = "SELECT medicine_ids FROM bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // If there are no medicine_ids, return an error message
    if (!$row || empty($row['medicine_ids'])) {
        return "No medicine IDs found for this order.";
    }

    // Get the medicine_ids as a string
    $medicine_ids = $row['medicine_ids'];

    // Debugging: Log the medicine_ids value to ensure it's formatted correctly
    error_log("medicine_ids for booking_id $booking_id: $medicine_ids");

    // step 2: Fetch the medicine names using the comma-separated list of medicine_ids
    $query = "SELECT m.medicine_name 
              FROM medicines m 
              WHERE FIND_IN_SET(m.medicine_id, ?) > 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $medicine_ids);  // Bind as a string (since it's comma-separated)
    $stmt->execute();
    $result = $stmt->get_result();

    // step 3: Collect all medicine names in an array
    $medicine_names = [];
    while ($row = $result->fetch_assoc()) {
        $medicine_names[] = $row['medicine_name'];
    }

    // Return the list of medicine names as a comma-separated string
    return implode(", ", $medicine_names);
}

// Function to check if a user has already rated a booking
function hasUserRated($user_id, $booking_id, $conn)
{
    $query = "SELECT rating_id FROM ratings WHERE user_id = ? AND booking_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0; // If any record exists, return true
}

// Usage in your order display
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/userdb_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        /* Logo Styling */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 32px;
            color: #0D47A1;
            /* Darker Blue for the icon */
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            color: #1976D2;
            /* Medium Blue */
            font-family: 'Poppins', sans-serif;
        }

        .logo-text span {
            color: #42A5F5;
            /* Accent Blue */
        }


        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1E88E5;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header h2 {
            margin: 0;
            font-size: 24px;
        }

        .user-info {
            font-size: 18px;
            font-weight: 600;
        }

        .search-bar {
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        .search-bar button {
            padding: 12px 20px;
            background: #1565C0;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s ease;
        }

        .search-bar button:hover {
            background: #0d47a1;
        }

        .card {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .logout {
            background: #e53935;
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            margin-left: 10px;
        }

        .logout:hover {
            background: #c62828;
        }

        .view-checkout {
            background: #1E88E5;
            padding: 10px 20px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            margin-left: 10px;
            transition: background 0.3s ease;
        }

        .view-checkout:hover {
            background: #1565C0;
        }

        hr {
            border: 0;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }

        .toggle-button {
            background-color: #1E88E5;
            color: #fff;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }

        .toggle-button:hover {
            background-color: #1565C0;
        }

        .details {
            display: none;
            margin-top: 10px;
            padding-left: 10px;
        }

        .details.show {
            display: block;
        }
    </style>

</head>

<body>
    <div class="header">
        <div class="logo">
            <div class="logo-icon">🩺</div>
            <div class="logo-text">Medi<span>Find</span></div>
        </div>

        <div class="user-info">
            <?php echo htmlspecialchars($first_name); ?>
            <a href="edit_profile.php" title="Edit Profile"
                style="margin-left: 15px; text-decoration: none; color: white; font-size: 20px;">
                ⚙
            </a>
            <a href="logout.php" class="logout">Logout</a>
        </div>

    </div>




    <!-- Search by location -->
    <div class="search-bar">
        <input type="text" id="medicine_name" placeholder="Search for medicine..." required>
        <input type="text" id="location" placeholder="Enter location..." required>
        <button id="search_button">Search by Location</button>
    </div>

    <!-- Search in a specific pharmacy -->
    <div class="search-bar">
        <input type="text" id="medicine_name_pharmacy" placeholder="Search for medicine..." required>
        <input type="text" id="pharmacy_name" placeholder="Enter pharmacy name..." required>
        <button id="search_pharmacy_button">Search in Pharmacy</button>
    </div>
    >
    <!-- History Section -->
    <div class="card">
        <h3>History</h3>

        <?php if (!empty($history_orders)): ?>
            <button id="toggle-all-history" onclick="toggleAllHistory()"
                style="padding: 10px 15px; margin-bottom: 15px; background-color: #2196F3; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Show All Details
            </button>

            <div id="history-details-wrapper">
                <?php foreach ($history_orders as $order): ?>
                    <div class="history-detail">
                        <p><strong>Delivered:</strong> <?= htmlspecialchars($order['pharmacy_name']); ?></p>
                        <p><strong>Total Price: $</strong><?= number_format($order['total_price'], 2); ?></p>
                        <p><strong>Status Time:</strong> <?= htmlspecialchars($order['status_time']); ?></p>
                        <p><strong>Medicines:</strong> <?= getMedicineNamesForOrder($order['booking_id'], $conn); ?></p>

                        <?php if (!hasUserRated($user_id, $order['booking_id'], $conn)): ?>
                            <a href="rate.php?booking_id=<?= $order['booking_id']; ?>">⭐ Rate this order</a>
                        <?php else: ?>
                            <span>✅ You already rated this order.</span>
                        <?php endif; ?>

                        <hr>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <p>No delivered orders yet.</p>
        <?php endif; ?>
    </div>



    <!-- Order Tracker Section -->
    <div class="card">
        <h3>Order Tracker</h3>

        <?php if (!empty($active_orders)): ?>
            <button id="toggle-all-tracker" onclick="toggleAllTracker()"
                style="padding: 10px 15px; margin-bottom: 15px; background-color:rgb(33, 150, 243); color: white; border: none; border-radius: 5px; cursor: pointer;">
                Show All Details
            </button>

            <div id="tracker-details-wrapper">
                <?php foreach ($active_orders as $index => $order): ?>
                    <div class="tracker-detail" style="display: none; margin-bottom: 15px;">

                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                        <p><strong>Pharmacy: </strong><?php echo htmlspecialchars($order['pharmacy_name']); ?></p>
                        <p><strong>Total Price: $</strong><?php echo number_format($order['total_price'], 2); ?></p>
                        <p><strong>Medicines:</strong> <?php echo getMedicineNamesForOrder($order['booking_id'], $conn); ?>
                        </p>

                        <!-- Link to checkout page with relevant data -->
                        <a href="myCheck.php?booking_id=<?php echo $order['booking_id']; ?>&pharmacy_id=<?php echo $order['pharmacy_id']; ?>&pharmacy_name=<?php echo urlencode($order['pharmacy_name']); ?>&total_price=<?php echo $order['total_price']; ?>&user_id=<?php echo $user_id; ?>&medicine_names=<?php echo urlencode(getMedicineNamesForOrder($order['booking_id'], $conn)); ?>"
                            class="view-checkout">View Checkout</a>
                        <hr>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No active orders.</p>
        <?php endif; ?>
    </div>


    <script>
        // Search by location
        document.getElementById('search_button').addEventListener('click', function () {
            var medicineName = document.getElementById('medicine_name').value.trim();
            var location = document.getElementById('location').value.trim();

            if (medicineName !== "" && location !== "") {
                window.location.href = "search_result.php?medicine=" + encodeURIComponent(medicineName) + "&location=" + encodeURIComponent(location);
            } else {
                alert("Please enter both medicine and location!");
            }
        });

        // Search in a specific pharmacy
        document.getElementById('search_pharmacy_button').addEventListener('click', function () {
            var medicineName = document.getElementById('medicine_name_pharmacy').value.trim();
            var pharmacyName = document.getElementById('pharmacy_name').value.trim();

            if (medicineName !== "" && pharmacyName !== "") {
                window.location.href = "search_pharmacy_result.php?medicine=" + encodeURIComponent(medicineName) + "&pharmacy=" + encodeURIComponent(pharmacyName);
            } else {
                alert("Please enter both medicine and pharmacy name!");
            }
        });
    </script>


    <script>
        let allHistoryVisible = false;
        let allTrackerVisible = false;

        function toggleAllHistory() {
            const details = document.querySelectorAll('.history-detail');
            const toggleBtn = document.getElementById('toggle-all-history');

            allHistoryVisible = !allHistoryVisible;

            details.forEach(div => {
                div.style.display = allHistoryVisible ? 'block' : 'none';
            });

            toggleBtn.textContent = allHistoryVisible ? 'Hide All Details' : 'Show All Details';
        }

        function toggleAllTracker() {
            const details = document.querySelectorAll('.tracker-detail');
            const toggleBtn = document.getElementById('toggle-all-tracker');

            allTrackerVisible = !allTrackerVisible;

            details.forEach(div => {
                div.style.display = allTrackerVisible ? 'block' : 'none';
            });

            toggleBtn.textContent = allTrackerVisible ? 'Hide All Details' : 'Show All Details';
        }
    </script>


</body>

</html>

<?php
$conn->close();
?>