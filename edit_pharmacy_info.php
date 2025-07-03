<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['pharmacy_id'])) {
    header('Location: login.php');
    exit();
}

$pharmacy_id = $_SESSION['pharmacy_id'];
$success_message = '';
$error_message = '';


// Step 1: Fetch current pharmacy information
$query = "SELECT location, opening_hours, contact, email FROM pharmacy WHERE pharmacy_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pharmacy_id);
$stmt->execute();
$stmt->bind_result($location, $opening_hours, $contact, $email);
$stmt->fetch();
$stmt->close();

// Step 2: Handle form submission for updating profile
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Trim inputs to remove unnecessary whitespace
    $new_location = trim($_POST['location']);
    $new_opening_hours = trim($_POST['opening_hours']);
    $new_contact = trim($_POST['contact']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password']; // plain input
    $update_password = !empty($new_password); // check if password should be updated

    // Validate required fields (excluding password)
    if (empty($new_location) || empty($new_opening_hours) || empty($new_contact) || empty($new_email)) {
        $error_message = "All fields except password are required.";
    } else {
        // Step 3: Build appropriate SQL query based on whether password should be updated
        if ($update_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);// Hash the new password securely
            $update_sql = "UPDATE pharmacy SET location=?, opening_hours=?, contact=?, email=?, password=? WHERE pharmacy_id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssi", $new_location, $new_opening_hours, $new_contact, $new_email, $hashed_password, $pharmacy_id);
        } else {
            // Prepare SQL statement without password update
            $update_sql = "UPDATE pharmacy SET location=?, opening_hours=?, contact=?, email=? WHERE pharmacy_id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssi", $new_location, $new_opening_hours, $new_contact, $new_email, $pharmacy_id);
        }

        // Execute the update statement and check result
        if ($stmt->execute()) {
            $success_message = "Information updated successfully.";
            // Refresh values
            $location = $new_location;
            $opening_hours = $new_opening_hours;
            $contact = $new_contact;
            $email = $new_email;
        } else {
            $error_message = "Failed to update. Please try again.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Pharmacy Info</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            /* Prevent scrolling */
            font-family: 'Poppins', sans-serif;
            background: url('images/editinfo_bg.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            width: 100%;
            max-width: 500px;
            height: auto;
            margin: auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        h2 {
            text-align: center;
            color: #2196F3;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        button {
            background: #2196F3;
            color: white;
            padding: 12px;
            width: 100%;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .message {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #2196F3;
            text-decoration: none;
        }
    </style>


</head>

<body>
    <div class="container">
        <h2>Edit Pharmacy Info</h2>

        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
            </div>

            <div class="form-group">
                <label for="opening_hours">Opening Hours</label>
                <input type="text" name="opening_hours" value="<?php echo htmlspecialchars($opening_hours); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="contact">Contact</label>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($contact); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">New Password (leave blank to keep current)</label>
                <input type="password" name="password">
            </div>

            <button type="submit">Update Info</button>
        </form>

        <a class="back-link" href="pharmacy_dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>

</html>