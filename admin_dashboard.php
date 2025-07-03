<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- FontAwesome for Icons -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background: #f0f4fa;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: #1E3A8A;
            /* Deep blue */
            padding: 20px;
            color: white;
            position: fixed;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Logo Styling */
        .logo {
            font-size: 32px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            color: #fff;
        }

        .logo span {
            color: #3B82F6;
            /* Lighter blue */
        }

        /* Sidebar Menu */
        .sidebar ul {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar ul li {
            padding: 15px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            border-radius: 8px;
        }

        .sidebar ul li i {
            margin-right: 10px;
        }

        .sidebar ul li:hover {
            background: #3B82F6;
        }

        /* Logout Button */
        .logout {
            background: #2563EB;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            width: 100%;
            transition: background 0.3s ease, transform 0.2s ease;
        }

        .logout:hover {
            background: #1D4ED8;
            transform: scale(1.05);
        }

        .logout a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 20px;
            flex: 1;
        }

        .card {
            background: white;
            padding: 25px;
            margin: 20px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            color: #1E3A8A;
            margin-bottom: 15px;
        }

        /* Button Styling */
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #2563EB;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #1D4ED8;
        }

        /* Table Styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        th {
            background-color: #1E3A8A;
            color: white;
        }

        tr:hover {
            background-color: #f0f8ff;
        }

        .approve {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background-color: #10B981;
            color: white;
            transition: background 0.3s;
        }

        .approve:hover {
            background-color: #059669;
        }

        .reject {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            background-color: #EF4444;
            color: white;
            transition: background 0.3s;
        }

        .reject:hover {
            background-color: #DC2626;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #ccc;
            width: 80%;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #888;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                flex-direction: row;
                padding: 10px;
            }

            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            .card {
                padding: 15px;
            }

            table th,
            table td {
                font-size: 14px;
            }
        }
    </style>

</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">Medi<span>Find</span></div>
        <ul>
            <li><i class="fas fa-clock"></i> Pending Pharmacies</li>
            <li><i class="fas fa-list"></i> Registered Pharmacies</li>
        </ul>
        <div class="logout">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="card">
            <h3>View List of Pending Pharmacies</h3>
            <table>
                <thead>
                    <tr>
                        <th>Pharmacy Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    require_once 'db_connect.php';
                    require_once 'send_email.php'; // Include the send_email function
                    
                    // Check if the form has been submitted via POST and contains required fields
                    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pharmacy_id'], $_POST['action'])) {
                        // Sanitize and retrieve form inputs
                        $pharmacy_id = intval($_POST['pharmacy_id']);
                        $action = $_POST['action'];

                        // Fetch the pharmacy data for email notification
                        $pharmacy_query = "SELECT * FROM pharmacy WHERE pharmacy_id = $pharmacy_id";
                        $pharmacy_result = $conn->query($pharmacy_query);

                        // If pharmacy is found
                        if ($pharmacy_result && $pharmacy_result->num_rows === 1) {
                            $pharmacy = $pharmacy_result->fetch_assoc(); // Fetch the row as an associative array
                            $status = $action === 'approve' ? 'approved' : 'rejected'; // Determine the new status
                    

                            // Update status in DB
                            $update_query = "UPDATE pharmacy SET status = '$status' WHERE pharmacy_id = $pharmacy_id";
                            if ($conn->query($update_query) === TRUE) {
                                // Prepare email
                                $to = $pharmacy['email'];
                                $subject = "Pharmacy Registration " . ucfirst($status) . " - MediFind";
                                $message =
                                    "<body style='font-family: Arial, sans-serif; color: #333;'>
                            <p>Dear {$pharmacy['pharmacy_name']},</p>
                             <p>Thank you for registering with <strong>MediFind</strong>.</p>
                           \nYour registration has been {$status} by the admin.\n
                            <p>Best regards,<br>  The MediFind Team</p>
                            </body>
                            </html>";

                                // Send the email and show appropriate message
                                if (sendEmailNotification($to, $subject, $message)) {
                                    echo "<script>alert('Pharmacy $status and email sent successfully.');</script>";
                                } else {
                                    echo "<script>alert('Pharmacy $status, but email sending failed.');</script>";
                                }
                            } else {
                                echo "<script>alert('Error updating status: {$conn->error}');</script>";
                            }
                        }
                    }

                    // Display pending pharmacies
                    $pending_query = "SELECT * FROM pharmacy WHERE status = 'pending'";
                    $pending_result = $conn->query($pending_query);

                    if ($pending_result && $pending_result->num_rows > 0) {
                        while ($row = $pending_result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['pharmacy_name']}</td>
                                <td>{$row['location']}</td>
                                <td>{$row['contact']}</td>
                                <td>{$row['email']}</td>
                                <td>
                                    <form method='post' style='display:inline;'>
                                        <input type='hidden' name='pharmacy_id' value='{$row['pharmacy_id']}'>
                                        <button type='submit' name='action' value='approve' class='approve'>Approve</button>
                                        <button type='submit' name='action' value='reject' class='reject'>Reject</button>
                                    </form>
                                </td>
                              </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5'>No pending pharmacies found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>View List of Registered Pharmacies</h3>
            <table>
                <thead>
                    <tr>
                        <th>Pharmacy Name</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $approved_query = "SELECT * FROM pharmacy WHERE status = 'approved'";
                    $approved_result = $conn->query($approved_query);

                    if ($approved_result && $approved_result->num_rows > 0) {
                        while ($row = $approved_result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['pharmacy_name']}</td>
                                <td>{$row['location']}</td>
                                <td>{$row['contact']}</td>
                                <td>{$row['email']}</td>
                              </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4'>No approved pharmacies found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>