<?php
session_start();

// Include database connection
require_once 'db_connect.php';

// Function to display alert messages
function displayAlert($message, $type = 'success') {
    echo "<div class='alert alert-$type' role='alert'>$message</div>";
}

// Handle status update actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['pharmacy_id']) && isset($_POST['action'])) {
        $pharmacy_id = $_POST['pharmacy_id'];
        $action = $_POST['action'];

        // Prepare the SQL statement based on the action
        if ($action === 'accept') {
            $status = 'accepted';
            $message = 'Pharmacy accepted successfully.';
        } elseif ($action === 'reject') {
            $status = 'rejected';
            $message = 'Pharmacy rejected successfully.';
        } else {
            $status = '';
            $message = 'Invalid action.';
        }

        if ($status) {
            // Update the pharmacy status
            $stmt = $conn->prepare("UPDATE pharmacy SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $pharmacy_id);

            if ($stmt->execute()) {
                displayAlert($message);
            } else {
                displayAlert('Error updating pharmacy status.', 'danger');
            }
            $stmt->close();
        } else {
            displayAlert($message, 'danger');
        }
    }
}

// Fetch pharmacies awaiting approval
$pharmacies_query = "SELECT * FROM pharmacy WHERE status = 'pending'";
$pharmacies_result = $conn->query($pharmacies_query);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Pharmacies | MediFind</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            margin-top: 80px;
        }
        h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 30px;
        }
        .dashboard-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        .dashboard-buttons a {
            width: 200px;
            text-align: center;
        }
        .logout-btn {
            position: fixed;
            top: 20px;
            right: 20px;
        }
        table {
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        tr:nth-child(even) {
            background-color: #f1f3f5;
        }
    </style>
</head>
<body>

<!-- Logout Button -->
<div class="logout-btn">
    <a href="?logout=true" class="btn btn-secondary">Logout</a>
</div>

<div class="container">
    <h1 class="text-center">Check Pharmacies</h1>

    <!-- Pharmacies Approval Section -->
    <div class="card">
        <div class="card-header">
            <h5>Pharmacies Awaiting Approval</h5>
        </div>
        <div class="card-body">
            <?php if ($pharmacies_result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pharmacy Name</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($pharmacy = $pharmacies_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pharmacy['id']); ?></td>
                                <td><?php echo htmlspecialchars($pharmacy['name']); ?></td>
                                <td><?php echo htmlspecialchars($pharmacy['location']); ?></td>
                                <td>
                                    <!-- Accept Button -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="pharmacy_id" value="<?php echo htmlspecialchars($pharmacy['id']); ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                    </form>
                                    <!-- Reject Button -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="pharmacy_id" value="<?php echo htmlspecialchars($pharmacy['id']); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pharmacies awaiting approval.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>