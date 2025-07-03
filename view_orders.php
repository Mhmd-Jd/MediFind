<?php
session_start();
require_once 'db_connect.php';

// Check if pharmacy is logged in
if (!isset($_SESSION['pharmacy_id'])) {
    header('Location: login.php');
    exit();
}

// Get the pharmacy_id from session
$pharmacy_id = $_SESSION['pharmacy_id'];

// -------- Fetch pharmacy name from the database --------

// Prepare SQL query to get pharmacy name using pharmacy_id
$query = "SELECT pharmacy_name FROM pharmacy WHERE pharmacy_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pharmacy_id);
$stmt->execute();
$stmt->bind_result($pharmacy_name);
$stmt->fetch();
$stmt->close();

// -------- Handle order status update on form submission --------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     // Get booking ID and status from the form
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    $status_time = date('Y-m-d H:i:s');

    // Check if order_tracker record exists
    // Check if a status entry already exists for the booking
    $check_query = "SELECT * FROM order_tracker WHERE booking_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
         // Update the existing status record
        $update_query = "UPDATE order_tracker SET status = ?, status_time = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssi", $status, $status_time, $booking_id);
        $stmt->execute();
    } else {
        // Insert a new status record
        $insert_query = "INSERT INTO order_tracker (booking_id, status, status_time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iss", $booking_id, $status, $status_time);
        $stmt->execute();
    }
}
// -------- Fetch orders for the logged-in pharmacy --------

// This query joins several tables to collect detailed booking info
$query = "SELECT b.booking_id, u.first_name, m.medicine_name, b.user_id, b.total_price, ot.status, r.rating, r.comment
          FROM bookings b
          JOIN users u ON b.user_id = u.user_id
          JOIN medicines m ON b.medicine_ids = m.medicine_id
          LEFT JOIN order_tracker ot ON b.booking_id = ot.booking_id
          LEFT JOIN ratings r ON b.booking_id = r.booking_id
          JOIN pharmacy p ON b.pharmacy_id = p.pharmacy_id
          WHERE p.pharmacy_name = ?
          ORDER BY b.booking_id DESC";

// Prepare and execute the statement securely
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $pharmacy_name);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pharmacy Orders | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/orders_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #003366;
        }

        .container {
            max-width: 95%;
            margin: 40px auto;
            background-color: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        h2 {
            color: #003366;
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background-color: #007BFF;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f1f9ff;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
        }

        select,
        button {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            background-color: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
        }

        .back-button:hover {
            background-color: #003f8a;
        }

        @media (max-width: 768px) {

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            td {
                position: relative;
                padding-left: 50%;
                text-align: left;
            }

            td::before {
                position: absolute;
                top: 14px;
                left: 14px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                color: #003366;
            }

            td:nth-of-type(1)::before {
                content: "Booking ID";
            }

            td:nth-of-type(2)::before {
                content: "User Name";
            }

            td:nth-of-type(3)::before {
                content: "Medicine";
            }

            td:nth-of-type(4)::before {
                content: "Total Price ($)";
            }

            td:nth-of-type(5)::before {
                content: "Current Status";
            }

            td:nth-of-type(6)::before {
                content: "Update Status";
            }
        }
    </style>

</head>

<body>

    <div class="container">
        <h2>Orders for <?php echo htmlspecialchars($pharmacy_name); ?> Pharmacy</h2>

        <a href="pharmacy_dashboard.php" class="back-button">← Go Back to Dashboard</a>

        <table>
            <tr>
                <th>Booking ID</th>
                <th>User Name</th>
                <th>Medicine</th>
                <th>Total Price ($)</th>
                <th>Current Status</th>
                <th>Update Status</th>
                <th>Rating</th>

            </tr>

            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['booking_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                    <td><?php echo number_format($row['total_price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                            <select name="status">
                                <option value="Placed Order" <?php if ($row['status'] === 'Placed Order')
                                    echo 'selected'; ?>>
                                    Placed Order</option>
                                <option value="Out for Delivery" <?php if ($row['status'] === 'Out for Delivery')
                                    echo 'selected'; ?>>Out for Delivery</option>
                                <option value="Delivered" <?php if ($row['status'] === 'Delivered')
                                    echo 'selected'; ?>>
                                    Delivered</option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                    </td>
                    <td>
                        <?php
                        if (!empty($row['rating'])) {
                            echo "⭐ " . htmlspecialchars($row['rating']);
                            if (!empty($row['comment'])) {
                                echo "<br><em>" . htmlspecialchars($row['comment']) . "</em>";
                            }
                        } else {
                            echo "No rating yet";
                        }
                        ?>
                    </td>

                </tr>
            <?php endwhile; ?>

        </table>
    </div>

</body>

</html>

<?php $conn->close(); ?>