<?php
session_start();
require_once 'db_connect.php';

// Check if pharmacy is logged in
if (!isset($_SESSION['pharmacy_id'])) {
    header('Location: login.php');
    exit();
}

// Retrieve the pharmacy_id from the session
$pharmacy_id = $_SESSION['pharmacy_id'];

// -------- Fetch out-of-stock medicines for this pharmacy --------
// Prepare SQL query to get medicines with zero quantity for the logged-in pharmacy
$query = "SELECT medicine_id, medicine_name, generic, price 
          FROM medicines 
          WHERE pharmacy_id = ? AND quantity_in_stock = 0 
          ORDER BY medicine_name ASC";

$stmt = $conn->prepare($query); // Prepare the SQL statement to prevent SQL injection
$stmt->bind_param("i", $pharmacy_id); // Bind the pharmacy_id parameter to the query (i = integer type)
$stmt->execute(); // Execute the prepared statement
$result = $stmt->get_result(); // Get the result set from the executed query
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Out of Stock Medicines | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: url('images/outofstock_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #fff;
        }

        .overlay {
            background-color: rgba(0, 51, 102, 0.85);
            /* dark blue overlay */
            min-height: 100vh;
            padding: 30px;
        }

        h2 {
            color: #ffffff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            margin: auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: center;
            color: #333;
        }

        th {
            background: #1976D2;
            color: white;
        }

        a {
            display: block;
            width: fit-content;
            margin: 30px auto 0;
            text-decoration: none;
            padding: 10px 25px;
            background: #2196F3;
            color: white;
            border-radius: 5px;
            text-align: center;
            transition: background 0.3s ease;
        }

        a:hover {
            background: #0d47a1;
        }

        p {
            text-align: center;
            font-size: 18px;
            color: #ffffff;
        }

        @media screen and (max-width: 768px) {

            table,
            th,
            td {
                font-size: 14px;
            }

            h2 {
                font-size: 22px;
            }

            a {
                padding: 8px 18px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="overlay">
        <h2>Out of Stock Medicines</h2>

        <?php if ($result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Medicine ID</th>
                    <th>Medicine Name</th>
                    <th>Generic Name</th>
                    <th>Price ($)</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['medicine_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['medicine_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['generic']); ?></td>
                        <td><?php echo number_format($row['price'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No out of stock medicines found.</p>
        <?php endif; ?>

        <a href="pharmacy_dashboard.php">← Back to Dashboard</a>
    </div>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>