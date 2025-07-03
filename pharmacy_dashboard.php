<?php
session_start();
require_once 'db_connect.php';

// Handle logout action
if (isset($_GET['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to the login page
    exit();
}

if (!isset($_SESSION['pharmacy_id'])) {
    header('Location: login.php');
    exit();
}

$pharmacy_id = $_SESSION['pharmacy_id'];


// Prepare a SQL query to fetch the pharmacy's name using the pharmacy_id
$query = "SELECT pharmacy_name FROM pharmacy WHERE pharmacy_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $pharmacy_id);
$stmt->execute();
$stmt->bind_result($pharmacy_name);
$stmt->fetch();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Dashboard | MediFind</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --green: #4CAF50;
            --blue: #2196F3;
            --dark-blue: rgb(11, 64, 62);
            --danger: #FF4B2B;
            --white: #ffffff;
            --light: #f5f7fa;
            --gray: #333;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #006699;
            margin: 0;
            padding: 0;
            color: var(--gray);
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid rgb(126, 146, 177);
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1000;
        }

        .topbar-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .settings-btn {
            color: var(--white);
            font-size: 22px;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .settings-btn:hover {
            color: var(--blue);
        }

        .logout-btn {
            background: var(--danger);
            color: #fff !important;
            padding: 8px 16px;
            font-size: 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn i {
            color: #fff;
        }

        .logout-btn:hover {
            background: #D32F2F;
        }

        .logo {
            font-size: 26px;
            font-weight: 600;
            color: var(--white);
        }

        .logo span {
            color: var(--blue);
            background-color: var(--white);
            padding: 2px 8px;
            border-radius: 5px;
        }

        .dashboard-container {
            max-width: 900px;
            width: 100%;
            height: 90%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-top: 60px;
        }

        .header-image {
            width: 100%;
            height: 220px;
            background: url('images/dashboard_bg.jpg') no-repeat center center/cover;
        }

        .dashboard-content {
            padding: 40px 30px;
            text-align: center;
            flex-grow: 1;
        }

        .dashboard-content h2 {
            font-size: 22px;
            color: var(--blue);
            margin-bottom: 30px;
        }

        .dashboard-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
        }

        .dashboard-buttons a {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            background: var(--white);
            color: var(--gray);
            padding: 20px 10px;
            font-size: 16px;
            border-radius: 16px;
            width: 130px;
            height: 130px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .dashboard-buttons a:hover {
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
            transform: translateY(-3px);
        }

        .dashboard-buttons a i {
            font-size: 28px;
            color: var(--blue);
            margin-bottom: 10px;
        }

        .dashboard-buttons a span {
            font-weight: 500;
            text-align: center;
        }

        .notification-count {
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 5px 10px;
            font-size: 14px;
            position: absolute;
            top: 5px;
            right: 5px;
        }

        @media screen and (max-width: 600px) {
            .dashboard-buttons a {
                width: 100px;
                height: 100px;
                font-size: 14px;
            }

            .dashboard-content h2 {
                font-size: 18px;
            }

            .logo {
                font-size: 20px;
            }

            .logout-btn {
                font-size: 14px;
                padding: 10px 18px;
            }
        }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="logo">Medi<span>Find</span></div>
        <div class="topbar-icons">
            <a href="edit_pharmacy_info.php" class="settings-btn" title="Edit Info">
                <i class="fas fa-cog"></i>
            </a>
            <a href="?logout=true" class="logout-btn" title="Logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="header-image"></div>
        <div class="dashboard-content">
            <h2><?php echo htmlspecialchars($pharmacy_name ?? ''); ?> Pharmacy</h2>

            <div class="dashboard-buttons">
                <a href="add_medicine.php">
                    <i class="fas fa-pills"></i>
                    <span>Add Medicine</span>
                </a>
                <a href="view_medicine.php">
                    <i class="fas fa-capsules"></i>
                    <span>View Medicine</span>
                </a>
                <a href="view_orders.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span>View Orders</span>
                </a>
                <a href="out_of_stock.php">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Out of Stock</span>
                </a>
            </div>
        </div>
    </div>

    <?php $conn->close(); ?>

</body>

</html>