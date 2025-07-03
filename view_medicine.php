<?php
require_once 'db_connect.php';
require_once 'send_email.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['pharmacy_id'])) {
    die("Unauthorized. Please log in as a pharmacy.");
}

$pharmacy_id = $_SESSION['pharmacy_id']; // Get the logged-in pharmacy's ID
$message = "";// Initialize message variables
$message_type = "success"; // success, error, info

// Search functionality - FIXED
$search_query = "";
$search_term = "";

// Handle search request (if search form was submitted)
if (isset($_POST['search']) && !empty($_POST['search_term'])) {
    $search_term = trim($_POST['search_term']); // Remove whitespace
    $search_term_escaped = mysqli_real_escape_string($conn, $search_term);// Prevent SQL injection
    $search_query = "AND medicine_name LIKE '%$search_term_escaped%'";
}

// Fetch medicines for the logged-in pharmacy
$query = "SELECT * FROM medicines WHERE pharmacy_id = $pharmacy_id $search_query ORDER BY medicine_name";
$result = $conn->query($query);

// Handle delete operation (triggered via GET request with 'delete_id')
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Ensure medicine belongs to the logged-in pharmacy
    $check_query = "SELECT * FROM medicines WHERE medicine_id = $delete_id AND pharmacy_id = $pharmacy_id";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Proceed to delete the medicine
        $delete_query = "DELETE FROM medicines WHERE medicine_id = $delete_id";
        if ($conn->query($delete_query) === TRUE) {
            $message = "Medicine deleted successfully!";
            $message_type = "success";
            // Redirect to prevent re-deletion on refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $message = "Error deleting medicine: " . $conn->error;
            $message_type = "error";
        }
    } else {
        $message = "You are not authorized to delete this medicine.";
        $message_type = "error";
    }
}

// Handle update operation (submitted via POST)
if (isset($_POST['update_id']) && !empty($_POST['update_id'])) {
    $update_id = (int) $_POST['update_id'];

    // Confirm that the medicine belongs to the pharmacy
    $check_query = "SELECT * FROM medicines WHERE medicine_id = $update_id AND pharmacy_id = $pharmacy_id";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Fetch old quantity for stock tracking and email notification
        $old_quantity_query = "SELECT quantity_in_stock, medicine_name FROM medicines WHERE medicine_id = $update_id AND pharmacy_id = $pharmacy_id";
        $old_result = $conn->query($old_quantity_query);

        if ($old_result) {
            $old_row = $old_result->fetch_assoc();
            $old_quantity = (int) $old_row['quantity_in_stock'];
            $current_medicine_name = $old_row['medicine_name'];

            // Get updated medicine details from POST data
            $medicine_name = mysqli_real_escape_string($conn, trim($_POST['medicine_name']));
            $quantity_in_stock = (int) $_POST['quantity_in_stock'];
            $price = mysqli_real_escape_string($conn, trim($_POST['price']));
            $formulation = mysqli_real_escape_string($conn, trim($_POST['formulation']));
            $generic = mysqli_real_escape_string($conn, trim($_POST['generic']));
            $sideEffects = mysqli_real_escape_string($conn, trim($_POST['sideEffects']));
            $deliver_available = isset($_POST['deliver_available']) ? 1 : 0;

            // Update the medicine
            $update_query = "UPDATE medicines 
                             SET medicine_name = '$medicine_name', 
                                 quantity_in_stock = '$quantity_in_stock', 
                                 price = '$price', 
                                 formulation = '$formulation', 
                                 generic = '$generic', 
                                 sideEffects = '$sideEffects', 
                                 deliver_available = '$deliver_available'
                             WHERE medicine_id = $update_id AND pharmacy_id = $pharmacy_id";

            // Execute update
            if ($conn->query($update_query) === TRUE) {
                $message = "Medicine updated successfully!";
                $message_type = "success";

                // Email notification logic
                // If quantity changed from 0 to > 0, notify users who requested this medicine
                if ($old_quantity == 0 && $quantity_in_stock > 0) {
                    // Check if medicine_requests table exists
                    $table_check = "SHOW TABLES LIKE 'medicine_requests'";
                    $table_result = $conn->query($table_check);

                    if ($table_result && $table_result->num_rows > 0) {
                        // Get all users who requested this medicine
                        $email_query = "SELECT email FROM medicine_requests 
                                       WHERE medicine_id = $update_id AND pharmacy_id = $pharmacy_id";
                        $email_result = $conn->query($email_query);

                        if ($email_result && $email_result->num_rows > 0) {
                            $emails_sent = 0;

                            // Check if email function is available
                            if (function_exists('sendEmailNotification')) {
                                while ($email_row = $email_result->fetch_assoc()) {
                                    $user_email = $email_row['email'];

                                    // Validate email format
                                    if (filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
                                        $subject = "Medicine Now Available - MediFind";
                                        $email_body = "
                                        <html>
                                        <body style='font-family: Arial, sans-serif; color: #333;'>
                                            <h2>Good News! Your Requested Medicine is Now Available</h2>
                                            <p>Dear Customer,</p>
                                            <p>The medicine <strong>$medicine_name</strong> that you requested is now back in stock at the pharmacy.</p>
                                            <p><strong>Details:</strong></p>
                                            <ul>
                                                <li>Medicine: $medicine_name</li>
                                                <li>Price: $price</li>
                                            </ul>
                                            <p>Please visit the pharmacy or contact them to place your order.</p>
                                            <p>Best regards,<br>The MediFind Team</p>
                                        </body>
                                        </html>";

                                        // Send the email
                                        if (sendEmailNotification($user_email, $subject, $email_body)) {
                                            $emails_sent++;
                                        }
                                    }
                                }

                                // If emails were successfully sent, show message and clear requests
                                if ($emails_sent > 0) {
                                    $message .= " $emails_sent notification email(s) sent to customers.";
                                    // Clear fulfilled requests
                                    $delete_requests = "DELETE FROM medicine_requests 
                                                      WHERE medicine_id = $update_id AND pharmacy_id = $pharmacy_id";
                                    $conn->query($delete_requests);
                                }
                            }
                        }
                    }
                }
            } else {
                // Error updating medicine
                $message = "Error updating medicine: " . $conn->error;
                $message_type = "error";
            }
        }
    } else {
        // Unauthorized update attempt
        $message = "Unauthorized update attempt.";
        $message_type = "error";
    }
}

// Get total count for stats
$total_medicines = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE pharmacy_id = $pharmacy_id")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM medicines WHERE pharmacy_id = $pharmacy_id AND quantity_in_stock = 0")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Management | MediFind</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .navigation-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 30px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .back-to-dashboard:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
            color: white;
        }

        .back-to-dashboard i {
            font-size: 18px;
        }

        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            min-width: 150px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .container-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .container-header h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .search-section {
            padding: 30px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .search-controls {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
            gap: 10px;
            align-items: center;
        }

        .search-controls input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .search-controls input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .search-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .clear-btn {
            padding: 15px 20px;
            background: #6b7280;
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .clear-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .alert {
            margin: 20px 30px;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .table-container {
            padding: 30px;
            overflow-x: auto;
        }

        .medicines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }

        .medicine-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .medicine-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .medicine-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .stock-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stock-in {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-out {
            background: #fee2e2;
            color: #991b1b;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        .search-info {
            background: #e0f2fe;
            color: #006064;
            padding: 10px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .medicines-grid {
                grid-template-columns: 1fr;
            }

            .stats-container {
                flex-direction: column;
                align-items: center;
            }

            .search-controls {
                flex-direction: column;
                gap: 15px;
            }

            .search-controls input,
            .search-btn,
            .clear-btn {
                width: 100%;
                justify-content: center;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                justify-content: center;
            }

            .back-to-dashboard {
                padding: 12px 25px;
                font-size: 14px;
            }
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4f46e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1><i class="fas fa-pills"></i> MediFind</h1>
        <p>Advanced Medicine Management System</p>
    </div>

    <div class="navigation-section">
        <a href="pharmacy_dashboard.php" class="back-to-dashboard">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <i class="fas fa-capsules"></i>
            <div class="stat-number"><?= $total_medicines ?></div>
            <div>Total Medicines</div>
        </div>

        <div class="stat-card">
            <i class="fas fa-check-circle"></i>
            <div class="stat-number"><?= $total_medicines - $out_of_stock ?></div>
            <div>In Stock</div>
        </div>

    </div>

    <div class="container">
        <div class="container-header">
            <h2><i class="fas fa-cog"></i> Medicine Management Dashboard</h2>
            <p>Manage your pharmacy inventory with ease</p>
        </div>

        <div class="search-section">
            <form method="POST" action="">
                <div class="search-controls">
                    <input type="text" name="search_term" placeholder="Search medicines by name..."
                        value="<?= htmlspecialchars($search_term) ?>" autocomplete="off">
                    <button type="submit" name="search" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search_term)): ?>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if (!empty($search_term)): ?>
                <div class="search-info">
                    <i class="fas fa-info-circle"></i>
                    Showing results for: <strong>"<?= htmlspecialchars($search_term) ?>"</strong>
                    (<?= $result ? $result->num_rows : 0 ?> medicine(s) found)
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert <?= $message_type ?>">
                <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="medicines-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="medicine-card">
                            <form method="POST" action="">
                                <div class="medicine-header">
                                    <img src="<?= !empty($row['image']) ? htmlspecialchars($row['image']) : 'https://via.placeholder.com/80x80?text=Medicine' ?>"
                                        alt="Medicine" class="medicine-image">
                                    <span class="stock-badge <?= $row['quantity_in_stock'] == 0 ? 'stock-out' : 'stock-in' ?>">
                                        <?= $row['quantity_in_stock'] == 0 ? 'Out of Stock' : 'In Stock (' . $row['quantity_in_stock'] . ')' ?>
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-pills"></i> Medicine Name</label>
                                    <input type="text" name="medicine_name"
                                        value="<?= htmlspecialchars($row['medicine_name']) ?>" required>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label><i class="fas fa-cubes"></i> Quantity</label>
                                        <input type="number" name="quantity_in_stock" value="<?= $row['quantity_in_stock'] ?>"
                                            min="0" required>
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-dollar-sign"></i> Price</label>
                                        <input type="text" name="price" value="<?= htmlspecialchars($row['price']) ?>" required>
                                    </div>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label><i class="fas fa-flask"></i> Formulation</label>
                                        <input type="text" name="formulation"
                                            value="<?= htmlspecialchars($row['formulation']) ?>">
                                    </div>
                                    <div class="form-group">
                                        <label><i class="fas fa-atom"></i> Generic</label>
                                        <input type="text" name="generic" value="<?= htmlspecialchars($row['generic']) ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-exclamation-triangle"></i> Side Effects</label>
                                    <textarea name="sideEffects"
                                        placeholder="List any side effects..."><?= htmlspecialchars($row['sideEffects']) ?></textarea>
                                </div>

                                <div class="checkbox-group">
                                    <input type="checkbox" name="deliver_available" id="delivery_<?= $row['medicine_id'] ?>"
                                        <?= $row['deliver_available'] ? 'checked' : '' ?>>
                                    <label for="delivery_<?= $row['medicine_id'] ?>">
                                        <i class="fas fa-truck"></i> Delivery Available
                                    </label>
                                </div>

                                <div class="actions">
                                    <input type="hidden" name="update_id" value="<?= $row['medicine_id'] ?>">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Medicine
                                    </button>
                                    <a href="?delete_id=<?= $row['medicine_id'] ?>" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this medicine: <?= htmlspecialchars($row['medicine_name']) ?>?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-pills"></i>
                    <h3>No Medicines Found</h3>
                    <p>
                        <?php if (!empty($search_term)): ?>
                            No medicines match your search for "<strong><?= htmlspecialchars($search_term) ?></strong>".
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" style="color: #4f46e5;">Clear search</a> to see all medicines.
                        <?php else: ?>
                            Start by adding some medicines to your inventory.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Add loading states to buttons
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && submitBtn.name !== 'search') {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<div class="loading"></div> Processing...';
                        submitBtn.disabled = true;

                        // Re-enable after 5 seconds as fallback
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            });

            // Auto-focus search input if it has value
            const searchInput = document.querySelector('input[name="search_term"]');
            if (searchInput && searchInput.value) {
                searchInput.focus();
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }

            // Add Enter key support for search
            if (searchInput) {
                searchInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.closest('form').submit();
                    }
                });
            }

            // Add smooth scrolling for better UX
            if (window.location.hash) {
                document.querySelector(window.location.hash)?.scrollIntoView({
                    behavior: 'smooth'
                });
            }

            // Debug: Log form submission
            const searchForm = document.querySelector('form');
            if (searchForm) {
                searchForm.addEventListener('submit', function (e) {
                    console.log('Search form submitted');
                    console.log('Search term:', searchInput ? searchInput.value : 'No input found');
                });
            }
        });
    </script>

</body>

</html>