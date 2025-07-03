<?php
// Include database connection file
require_once 'db_connect.php';  // This file establishes a connection to your MySQL database

// Check if the request method is POST (i.e., form was submitted)
if ($_SERVER['REQUEST_METHOD'] ?? '' == 'POST') {
    
    // Retrieve and sanitize form inputs (fallback to empty string if not set)
    $pharmacy_name = $_POST['pharmacy_name'] ?? '';
    $location = $_POST['location'] ?? '';
    $opening_hours = $_POST['opening_hours'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Securely hash the password for storage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if this contact number or email already exists in the database
    $stmt = $conn->prepare("SELECT * FROM pharmacy WHERE contact = ? OR email = ?");
    $stmt->bind_param("ss", $contact, $email);  // Bind contact and email to the query
    $stmt->execute();                           // Execute the query
    $result = $stmt->get_result();              // Fetch result set

    // If record found, inform user about duplication
    if ($result->num_rows > 0) {
        $error_message = "This phone number or email is already registered!";
    } else {
        // Prepare SQL to insert new pharmacy with 'pending' status
        $stmt = $conn->prepare("INSERT INTO pharmacy (pharmacy_name, location, opening_hours, contact, email, password, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssss", $pharmacy_name, $location, $opening_hours, $contact, $email, $hashed_password);

        // Execute insert statement and return success or error message
        if ($stmt->execute()) {
            $success_message = "Pharmacy sign-up successful! You can now log in.";
        } else {
            $error_message = "Error: " . $conn->error;  // Output any database error
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Sign-Up | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: url('images/signphar_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 30px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
            overflow: hidden;
        }

        .logo {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            animation: fadeIn 1s ease-out;
            color: white;
            text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.5);
            /* Added shadow for better visibility */
        }

        .logo span {
            color: #2196F3;
            /* blue */
        }

        .form-container {
            background: white;
            padding: 35px;
            width: 100%;
            max-width: 500px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideIn 0.7s ease-out;
            max-height: 80vh;
            /* Allow the form to take up to 80% of the viewport height */
            overflow-y: auto;
            /* Make content scrollable inside the container */
        }


        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        }

        .form-container h2 {
            color: #0D47A1;
            /* dark blue */
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            font-size: 14px;
            font-weight: 500;
            color: #0D47A1;
            /* dark blue */
            margin-bottom: 5px;
            display: block;
        }

        .input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #0D47A1;
            font-size: 16px;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            padding: 12px 15px 12px 35px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }

        .input-group input:focus,
        .input-group select:focus {
            border-color: #2196F3;
            box-shadow: 0 0 8px rgba(33, 150, 243, 0.3);
            outline: none;
        }

        .submit-btn {
            background: #2196F3;
            /* blue */
            color: white;
            padding: 16px;
            font-size: 18px;
            width: 100%;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .submit-btn:hover {
            background: #1976D2;
            /* darker blue */
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            .form-container {
                padding: 30px;
                max-width: 90%;
            }

            .logo {
                font-size: 36px;
            }
        }
    </style>
</head>

<body>
    <div class="logo">Medi<span>Find</span></div>
    <div class="form-container">
        <h2>Pharmacy Sign-Up</h2>
        <form action="signup_pharmacy.php" method="post">
            <div class="input-group">
                <label>Pharmacy Name</label>
                <i class="fas fa-prescription-bottle-alt"></i>
                <input type="text" name="pharmacy_name" placeholder="Enter pharmacy name" required>
            </div>
            <div class="input-group">
                <label>City</label>
                <i class="fas fa-city"></i>
                <input type="text" name="location" placeholder="Enter your location" required>
            </div>
            <div class="input-group">
                <label>Opening Hours</label>
                <i class="fas fa-clock"></i>
                <input type="text" name="opening_hours" placeholder="e.g. 9 AM - 9 PM" required>
            </div>
            <div class="input-group">
                <label>Phone Number</label>
                <i class="fas fa-phone-alt"></i>
                <input type="text" name="contact" placeholder="Enter phone number" required>
            </div>
            <div class="input-group">
                <label>Email</label>
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Enter email address" required>
            </div>
            <div class="input-group">
                <label>Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="submit-btn">Submit Request</button>
        </form>
    </div>
</body>

</html>