<?php
// Include the database connection file
require_once 'db_connect.php';

// Start a session to store user data upon registration
session_start();

// Check if the form has been submitted using the POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form input values
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $city = $_POST['city'];
    $building = $_POST['building'];
    $street = $_POST['street'];
    $floor = $_POST['floor'];
    $password = $_POST['password'];

    // Sanitize all input values to prevent SQL injection
    $first_name = $conn->real_escape_string($first_name);
    $last_name = $conn->real_escape_string($last_name);
    $email = $conn->real_escape_string($email);
    $phone_number = $conn->real_escape_string($phone_number);
    $city = $conn->real_escape_string($city);
    $building = $conn->real_escape_string($building);
    $street = $conn->real_escape_string($street);
    $floor = $conn->real_escape_string($floor);
    $password = $conn->real_escape_string($password);

    // Hash the password securely before storing in the database
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if the email or phone number already exists in the database
    $check_query = "SELECT * FROM users WHERE phoneNb = '$phone_number' OR email = '$email'";
    $check_result = $conn->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // If user already exists, set an error message
        $error = "The phone number or email is already registered. Please use a different one.";
    } else {
        // If user is new, insert the data into the users table
        $insert_query = "INSERT INTO users (first_name, last_name, email, phoneNb, city, building, street, floor, password) 
                         VALUES ('$first_name', '$last_name', '$email', '$phone_number', '$city', '$building', '$street', '$floor', '$hashed_password')";

        if ($conn->query($insert_query)) {
            // On successful insert, store user ID and name in session (optional)
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['user_name'] = $first_name;

            // Redirect to login page after successful registration
            header("Location: login.php");
            exit();
        } else {
            // Handle any error that occurs during the insert
            $error = "There was an error registering your account. Please try again.";
        }
    }
}

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Sign-Up | MediFind</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
            background-image: url('images/signuser_bg.jpg');
            /* Set the background image */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            flex-direction: column;
            /* Stack elements vertically */
            color: white;
            /* White text */
            overflow: hidden;
            /* Prevent scrolling of the entire page */
        }

        .mediFind {
            width: 100%;
            background-color: #0000FF;
            /* Blue background for the mediFind */
            color: white;
            /* White text */
            padding: 10px 0;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .random-lines {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
        }

        .line {
            position: absolute;
            width: 100%;
            height: 2px;
            background-color: #4CAF50;
            opacity: 0.6;
            transform-origin: center;
        }

        /* Create random green lines */
        .line1 {
            top: 10%;
            transform: rotate(45deg);
            animation: flow1 10s linear infinite;
        }

        .line2 {
            top: 30%;
            transform: rotate(-45deg);
            animation: flow2 12s linear infinite;
        }

        .line3 {
            top: 50%;
            transform: rotate(60deg);
            animation: flow3 15s linear infinite;
        }

        .line4 {
            top: 70%;
            transform: rotate(-60deg);
            animation: flow4 20s linear infinite;
        }

        .line5 {
            top: 90%;
            transform: rotate(90deg);
            animation: flow5 25s linear infinite;
        }

        /* Animations for flowing lines */
        @keyframes flow1 {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes flow2 {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes flow3 {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes flow4 {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes flow5 {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        .logo {
            font-size: 40px;
            font-weight: 700;
            color: #333;
            margin-bottom: 40px;
            text-transform: uppercase;
            letter-spacing: 3px;
            animation: fadeIn 1s ease-out;
        }

        .logo span {
            color: rgb(76, 162, 175);
        }

        .form-container {
            background: rgba(255, 255, 255, 0.9);
            /* White background with transparency */
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            border-radius: 12px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideIn 0.7s ease-out;
            overflow-y: auto;
            /* Make form scrollable if content overflows */
            max-height: 80vh;
            /* Set a max height for the form */
        }

        .form-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .form-container h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 28px;
        }

        .form-container p {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group input {
            width: 100%;
            padding: 15px 20px;
            padding-left: 45px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s ease, box-shadow 0.3s ease;
        }

        .input-group input:focus {
            border-color: #0000FF;
            /* Blue border on focus */
            box-shadow: 0 0 8px rgba(0, 0, 255, 0.4);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        .submit-btn {
            background: #0000FF;
            /* Blue button */
            color: white;
            padding: 16px 0;
            font-size: 18px;
            width: 100%;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .submit-btn:hover {
            background: #0033cc;
            transform: scale(1.05);
        }

        .message {
            color: #ff6f61;
            font-size: 14px;
            margin-top: 15px;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(-30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="random-lines">
        <div class="line line1"></div>
        <div class="line line2"></div>
        <div class="line line3"></div>
        <div class="line line4"></div>
        <div class="line line5"></div>
    </div>
    <div class="logo">Medi<span>Find</span></div>
    <div class="form-container">
        <h2>Create Your Account</h2>
        <p>Please fill in the details below to sign up.</p>

        <form action="signup_user.php" method="post">
            <!-- First Name -->
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="first_name" placeholder="First Name" required>
            </div>

            <!-- Last Name -->
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="last_name" placeholder="Last Name" required>
            </div>

            <!-- Phone Number -->
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone_number" placeholder="Phone Number" required>
            </div>

            <!-- Email Address -->
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <!-- City -->
            <div class="input-group">
                <i class="fas fa-city"></i>
                <input type="text" name="city" placeholder="City" required>
            </div>

            <!-- Street -->
            <div class="input-group">
                <i class="fas fa-road"></i>
                <input type="text" name="street" placeholder="Street" required>
            </div>

            <!-- Building -->
            <div class="input-group">
                <i class="fas fa-building"></i>
                <input type="text" name="building" placeholder="Building" required>
            </div>

            <!-- Floor -->
            <div class="input-group">
                <i class="fas fa-layer-group"></i>
                <input type="text" name="floor" placeholder="Floor" required>
            </div>

            <!-- Password -->
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="submit-btn">Sign Up</button>
        </form>

        <!-- Display any messages (e.g., error/success messages) -->
        <div class="message">
            <?php if (isset($message))
                echo $message; ?>
        </div>
    </div>
</body>


</html>