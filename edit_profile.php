<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID from the session

// Fetch current user data
function getUserDetails($user_id, $conn)
{
    $stmt = $conn->prepare("SELECT city, street, building, floor, phoneNb, email FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Handle form submission when the request method is post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $city = trim($_POST['city']);
    $street = trim($_POST['street']);
    $building = trim($_POST['building']);
    $floor = trim($_POST['floor']);
    $phoneNb = trim($_POST['phoneNb']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Perform basic validation to ensure required fields are not empty
    if (!empty($city) && !empty($street) && !empty($building) && !empty($floor) && !empty($phoneNb) && !empty($email)) {

        // Check if the user wants to change their password
        if (!empty($password)) {
            // Hash the new password for secure storage
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // Prepare an update query including the password field
            $stmt = $conn->prepare("UPDATE users SET city = ?, street = ?, building = ?, floor = ?, phoneNb = ?, email = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("sssssssi", $city, $street, $building, $floor, $phoneNb, $email, $hashed_password, $user_id);
        } else {
            // If no password change, omit password in the update
            $stmt = $conn->prepare("UPDATE users SET city = ?, street = ?, building = ?, floor = ?, phoneNb = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param("ssssssi", $city, $street, $building, $floor, $phoneNb, $email, $user_id);
        }

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Something went wrong. Please try again.";
        }
    } else {
        $error_message = "All fields are required.";
    }
}

// Get updated user details
$user = getUserDetails($user_id, $conn);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Profile</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f23;
            background-image:
                radial-gradient(circle at 25% 25%, #1a1a3e 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, #2d1b69 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, #1e3a8a 0%, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                linear-gradient(45deg, transparent 30%, rgba(59, 130, 246, 0.05) 50%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(147, 51, 234, 0.05) 50%, transparent 70%);
            animation: shimmer 8s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes shimmer {

            0%,
            100% {
                opacity: 0.3;
            }

            50% {
                opacity: 0.7;
            }
        }

        .form-container {
            background: rgba(15, 15, 35, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 50px;
            width: 100%;
            max-width: 520px;
            box-shadow:
                0 32px 64px rgba(0, 0, 0, 0.4),
                0 16px 32px rgba(59, 130, 246, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            z-index: 10;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg,
                    #3b82f6, #8b5cf6, #ec4899, #f59e0b, #10b981, #3b82f6);
            background-size: 400% 100%;
            animation: rainbowFlow 4s linear infinite;
        }

        @keyframes rainbowFlow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 400% 50%;
            }
        }

        .form-container::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(from 0deg, transparent 0deg, rgba(59, 130, 246, 0.03) 60deg, transparent 120deg);
            animation: rotate 10s linear infinite;
            pointer-events: none;
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        h2 {
            text-align: center;
            color: #ffffff;
            margin-bottom: 40px;
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 2;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 2px;
        }

        .form-group {
            position: relative;
            margin-bottom: 28px;
        }

        .form-group::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(-50%);
            border-radius: 1px;
        }

        .form-group:focus-within::after {
            width: 100%;
        }

        .form-group:focus-within {
            transform: scale(1.02);
        }

        .floating-label {
            position: relative;
            margin-bottom: 28px;
        }

        .floating-label input {
            width: 100%;
            padding: 28px 24px 12px 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            font-size: 16px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
            position: relative;
        }

        .floating-label input::placeholder {
            color: transparent;
        }

        .floating-label label {
            position: absolute;
            left: 24px;
            top: 18px;
            color: #6b7280;
            font-size: 16px;
            font-weight: 400;
            text-transform: none;
            letter-spacing: normal;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            pointer-events: none;
            margin: 0;
        }

        .floating-label input:focus+label,
        .floating-label input:not(:placeholder-shown)+label,
        .floating-label input:valid+label {
            top: 8px;
            font-size: 12px;
            color: #3b82f6;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .floating-label input:focus {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.08);
            box-shadow:
                0 0 0 1px #3b82f6,
                0 0 30px rgba(59, 130, 246, 0.3),
                0 8px 32px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .floating-label input:hover {
            border-color: rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.08);
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #ec4899 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow:
                0 16px 40px rgba(59, 130, 246, 0.4),
                0 8px 16px rgba(139, 92, 246, 0.2);
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 50%, #db2777 100%);
        }

        .submit-btn:active {
            transform: translateY(-1px) scale(1.01);
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
            }

            50% {
                box-shadow: 0 8px 40px rgba(59, 130, 246, 0.5);
            }

            100% {
                box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
            }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            margin-top: 30px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 12px 0;
            position: relative;
            z-index: 2;
        }

        .back-link:hover {
            color: #8b5cf6;
            transform: translateX(-8px);
        }

        .back-link::before {
            content: '←';
            margin-right: 12px;
            font-size: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .back-link:hover::before {
            transform: translateX(-6px) scale(1.2);
            color: #ec4899;
        }

        .success {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 500;
            box-shadow:
                0 8px 32px rgba(16, 185, 129, 0.3),
                0 4px 12px rgba(0, 0, 0, 0.1);
            animation: successSlide 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            position: relative;
            z-index: 2;
        }

        .error {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 500;
            box-shadow:
                0 8px 32px rgba(239, 68, 68, 0.3),
                0 4px 12px rgba(0, 0, 0, 0.1);
            animation: errorShake 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            position: relative;
            z-index: 2;
        }

        @keyframes successSlide {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes errorShake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        /* Enhanced particle effects */
        .particle {
            position: absolute;
            background: radial-gradient(circle, #3b82f6, transparent);
            border-radius: 50%;
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
            z-index: 1;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }

            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 0.3;
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 600px) {
            .form-container {
                padding: 40px 30px;
                margin: 15px;
                border-radius: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            h2 {
                font-size: 28px;
            }

            body {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Edit Profile</h2>

        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group floating-label">
                <input type="text" name="city" id="city" value="<?php echo htmlspecialchars($user['city']); ?>"
                    placeholder=" " required>
                <label for="city">City</label>
            </div>

            <div class="form-group floating-label">
                <input type="text" name="street" id="street" value="<?php echo htmlspecialchars($user['street']); ?>"
                    placeholder=" " required>
                <label for="street">Street</label>
            </div>

            <div class="form-row">
                <div class="form-group floating-label">
                    <input type="text" name="building" id="building"
                        value="<?php echo htmlspecialchars($user['building']); ?>" placeholder=" " required>
                    <label for="building">Building</label>
                </div>

                <div class="form-group floating-label">
                    <input type="text" name="floor" id="floor" value="<?php echo htmlspecialchars($user['floor']); ?>"
                        placeholder=" " required>
                    <label for="floor">Floor</label>
                </div>
            </div>

            <div class="form-group floating-label">
                <input type="text" name="phoneNb" id="phoneNb" value="<?php echo htmlspecialchars($user['phoneNb']); ?>"
                    placeholder=" " required>
                <label for="phoneNb">Phone Number</label>
            </div>

            <div class="form-group floating-label">
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                    placeholder=" " required>
                <label for="email">Email Address</label>
            </div>

            <div class="form-group floating-label">
                <input type="password" name="password" id="password" placeholder="Leave empty if not changing">
                <label for="password">New Password (Optional)</label>
            </div>

            <input type="submit" value="Update Profile" class="submit-btn">
        </form>

        <a href="user_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>

    <script>
        // Wait until the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function () {
            // Select all text, email, and password input fields
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
            const container = document.querySelector('.form-container'); // The container where floating particles will appear

            /**
             * Creates a small animated particle and adds it to the container.
             * The particle is styled randomly for visual variation.
             */
            function createParticle() {
                const particle = document.createElement('div');
                particle.className = 'particle';

                // Set random size between 2px and 6px
                particle.style.width = Math.random() * 4 + 2 + 'px';
                particle.style.height = particle.style.width;

                // Set random position within container
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';

                // Delay animation randomly up to 6 seconds
                particle.style.animationDelay = Math.random() * 6 + 's';

                container.appendChild(particle);

                // Remove the particle after 6 seconds to keep DOM clean
                setTimeout(() => particle.remove(), 6000);
            }

            // Create a new particle every 2 seconds to continuously enhance background effect
            setInterval(createParticle, 2000);

            // Enhance input field interactions
            inputs.forEach(input => {
                // Add focus effect with transformation and particles
                input.addEventListener('focus', function () {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                    this.parentElement.style.transform = 'scale(1.02)';

                    // Create a few particles for a dynamic effect on focus
                    for (let i = 0; i < 3; i++) {
                        setTimeout(() => createParticle(), i * 100);
                    }
                });

                // Revert focus effect on blur
                input.addEventListener('blur', function () {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.parentElement.style.transform = 'scale(1)';
                });

                // Special handling for phone number formatting
                if (input.name === 'phoneNb') {
                    input.addEventListener('input', function () {
                        // Remove non-digit characters
                        let value = this.value.replace(/\D/g, '');

                        // Format as (123) 456-7890 depending on length
                        if (value.length >= 10) {
                            value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                        } else if (value.length >= 6) {
                            value = value.replace(/(\d{3})(\d{3})/, '($1) $2-');
                        } else if (value.length >= 3) {
                            value = value.replace(/(\d{3})/, '($1) ');
                        }

                        this.value = value;
                    });
                }

                // Live email validation with visual feedback
                if (input.type === 'email') {
                    input.addEventListener('input', function () {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                        // If input exists and is invalid, highlight in red
                        if (this.value && !emailRegex.test(this.value)) {
                            this.style.borderColor = '#ef4444'; // red
                            this.style.boxShadow = '0 0 0 1px #ef4444, 0 0 20px rgba(239, 68, 68, 0.2)';
                        }
                        // If valid, highlight in green
                        else if (this.value) {
                            this.style.borderColor = '#10b981'; // green
                            this.style.boxShadow = '0 0 0 1px #10b981, 0 0 20px rgba(16, 185, 129, 0.2)';
                        }
                        // If empty, reset styles
                        else {
                            this.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                            this.style.boxShadow = 'none';
                        }
                    });
                }
            });

            // Enhance form submission with loading animation and feedback
            const form = document.querySelector('form');
            const submitBtn = document.querySelector('.submit-btn');

            form.addEventListener('submit', function (e) {
                // Replace button content with spinner and change style
                submitBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⟳</span> Updating...';
                submitBtn.style.background = 'linear-gradient(135deg, #6366f1, #8b5cf6)'; // Indigo to purple
                submitBtn.style.transform = 'translateY(-2px) scale(0.98)';
                submitBtn.disabled = true;

                // Create a few particles for a success-like visual cue
                for (let i = 0; i < 5; i++) {
                    setTimeout(() => createParticle(), i * 200);
                }
            });

            // Add hover effects on form groups for a subtle interactive feel
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach(group => {
                group.addEventListener('mouseenter', function () {
                    this.style.transform = 'scale(1.01)';
                });

                group.addEventListener('mouseleave', function () {
                    // Reset only if input inside the group is not focused
                    if (!this.querySelector('input:focus')) {
                        this.style.transform = 'scale(1)';
                    }
                });
            });

            // Trigger a few particles when the page first loads
            for (let i = 0; i < 3; i++) {
                setTimeout(() => createParticle(), i * 1000);
            }
        });
    </script>

</body>

</html>