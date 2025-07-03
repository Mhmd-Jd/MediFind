<?php
// Include database connection
require_once 'db_connect.php';
session_start();

$error = ''; // To store error messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {// Check if the request method is POST (i.e., form was submitted)
    $phone_number = $conn->real_escape_string($_POST['phone_number']);// Sanitize phone number input to prevent SQL injection
    $password = $_POST['password']; // Don't escape password (not used in query)

    // First, attempt to find the user in the `pharmacy` table using the provided phone number
    $pharmacy_query = "SELECT * FROM pharmacy WHERE contact = '$phone_number'";
    $pharmacy_result = $conn->query($pharmacy_query);

    // Check if a matching pharmacy was found
    if ($pharmacy_result && $pharmacy_result->num_rows > 0) {
        $pharmacy = $pharmacy_result->fetch_assoc(); // Fetch pharmacy details

        if (password_verify($password, $pharmacy['password'])) {  // Verify that the password entered matches the hashed password in the database

            // Handle different pharmacy statuses
            if ($pharmacy['status'] === 'approved') {
                // If approved, set session variables and redirect to pharmacy dashboard
                $_SESSION['pharmacy_id'] = $pharmacy['pharmacy_id'];
                $_SESSION['pharmacy_name'] = $pharmacy['pharmacy_name'];
                header("Location: pharmacy_dashboard.php");
                exit();

                // If account is pending, show message
            } elseif ($pharmacy['status'] === 'pending') {
                $error = "Your application is still pending. Please wait for admin approval.";

                // If account was rejected, show message
            } elseif ($pharmacy['status'] === 'rejected') {
                $error = "Your application has been rejected.";

                // Unknown status value
            } else {
                $error = "Unknown pharmacy status.";
            }

            // Password does not match
        } else {
            $error = "Invalid phone number or password.";
        }
    } else {
        // No matching pharmacy, check in users table
        $user_query = "SELECT * FROM users WHERE phoneNb = '$phone_number'";
        $user_result = $conn->query($user_query);

        if ($user_result && $user_result->num_rows > 0) {
            $user = $user_result->fetch_assoc();

            // Verify user password
            if (password_verify($password, $user['password'])) {
                // Set session variables for the logged-in user
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];

                // Admin login
                if ($user['phoneNb'] === '71225851' && $password === 'admin') {
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: user_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid phone number or password.";
            }
        } else {
            $error = "Invalid phone number or password.";
        }
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('images/login_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-10px);
        }

        .form-container h2 {
            color: #004D99;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 24px;
        }

        .logo {
            font-size: 36px;
            font-weight: 700;
            color: #004D99;
        }

        .logo .green {
            color: #00BFFF;
            /* Blue accent color for "Find" */
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group input {
            width: 100%;
            padding: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            border-color: #00BFFF;
            box-shadow: 0 0 5px rgba(0, 191, 255, 0.3);
            outline: none;
        }

        .submit-btn {
            background: #004D99;
            color: white;
            padding: 14px;
            font-size: 18px;
            width: 100%;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .submit-btn:hover {
            background: #003366;
            transform: scale(1.05);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .error {
            color: #FF4B2B;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .link {
            margin-top: 15px;
            font-size: 14px;
            color: #555;
        }

        .link a {
            text-decoration: none;
            color: #004D99;
            font-weight: 600;
        }

        .link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 30px;
                width: 90%;
            }
        }
    </style>

</head>

<body>

    <div class="form-container">
        <div class="logo">
            <span>Medi</span><span class="green">Find</span>
        </div>
        <h2>Login to MediFind</h2>

        <!-- Display error message if there's any -->
        <?php if (!empty($error))
            echo "<p class='error'>$error</p>"; ?>

        <form action="login.php" method="post" autocomplete="off">
            <div class="input-group">
                <input type="text" name="phone_number" placeholder="Phone Number" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>

        <div class="link">
            <p>Don't have an account? <a href="signup_user.php">Sign Up</a></p>
        </div>
    </div>

</body>

</html>