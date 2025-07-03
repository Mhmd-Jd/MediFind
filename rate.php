<?php
session_start();
require_once 'db_connect.php';
require_once 'functions.php'; // This should contain hasUserRated function

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate booking_id from GET request
if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    echo "❌ Invalid booking ID.";
    exit();
}

$booking_id = intval($_GET['booking_id']); // Sanitize booking_id

// Prevent user from rating the same booking multiple times
if (hasUserRated($user_id, $booking_id, $conn)) {
    echo "✅ You have already rated this booking.";
    echo "<br><a href='user_dashboard.php'>Back to Dashboard</a>";
    exit();
}

// Handle the form submission when POST request is received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating_value = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating_value > 0) {
        // Update the query to use the correct column name 'rating'
        $stmt = $conn->prepare("INSERT INTO ratings (user_id, booking_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $user_id, $booking_id, $rating_value, $comment);
        $stmt->execute();

        // Set a flag for showing the "Thank you" message
        $_SESSION['thank_you_message'] = true;

        // Redirect after a short delay
        header("Refresh: 3; url=user_dashboard.php");
        exit();
    } else {
        echo "❌ Please select a star rating.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Booking</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('images/rate_bg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
            flex-direction: column;
        }

        .logo {
            font-size: 3em;
            color: #005b99; /* Blue */
            font-weight: bold;
        }

        .logo span {
            color: #ffffff; /* White */
        }

        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }

        .star-rating {
            direction: rtl;
            display: inline-block;
            font-size: 2em;
            unicode-bidi: bidi-override;
            margin-bottom: 10px; /* Reduced margin */
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            color: #ccc;
            cursor: pointer;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: gold;
        }

        form {
            padding: 20px;
        }

        /* Move the comment label below the rating stars */
        .comment-container {
            margin-top: 20px; /* Adjusted margin */
            text-align: left;
        }

        textarea {
            width: 100%;
            margin-top: 10px;
            border-radius: 6px;
            padding: 8px;
        }

        button {
            margin-top: 12px;
            padding: 10px 20px;
            background: teal;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: darkslategray;
        }

        /* Ensure responsive design */
        @media (max-width: 768px) {
            .logo {
                font-size: 2em;
            }

            .form-container {
                padding: 20px;
            }
        }

        /* Style for thank you message */
        .thank-you-message {
            font-size: 1.5em;
            color: green;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">MediFind <span>Logo</span></div>

    <div class="form-container">
        <?php if (isset($_SESSION['thank_you_message']) && $_SESSION['thank_you_message']): ?>
            <div class="thank-you-message">✅ Thank you for your rating! You will be redirected shortly...</div>
            <?php unset($_SESSION['thank_you_message']); ?>
        <?php else: ?>
            <form method="post" id="ratingForm">
                <h2>Rate This Order</h2>

                <!-- Star Rating -->
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>">
                        <label for="star<?= $i ?>" title="<?= $i ?> stars">★</label>
                    <?php endfor; ?>
                </div>

                <!-- Optional comment below the stars -->
                <div class="comment-container">
                    <label for="comment">Comment (optional):</label><br>
                    <textarea name="comment" id="comment" rows="4"></textarea><br>
                </div>

                <button type="submit">Submit Rating</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>