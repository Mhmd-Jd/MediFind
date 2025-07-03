<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Startup Page 2</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            background: linear-gradient(135deg, #3498db, #8e44ad);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            overflow: hidden;
        }

        #icons-container {
            text-align: center;
            display: block;
            padding: 20px;
            border-radius: 12px;
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            font-weight: 600;
            color: #fff;
        }

        #icons-container button {
            background-color: #2980b9;
            color: white;
            border: none;
            padding: 18px;
            margin: 15px;
            border-radius: 12px;
            font-size: 1.2rem;
            width: 220px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        #icons-container button:hover {
            background-color: #1abc9c;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        #icons-container i {
            font-size: 1.8rem;
            margin-right: 10px;
            vertical-align: middle;
        }

        #icons-container button:active {
            transform: translateY(1px);
        }

        /* Add a responsive design for smaller screens */
        @media (max-width: 768px) {
            #icons-container button {
                width: 180px;
                font-size: 1rem;
            }

            h1 {
                font-size: 2rem;
            }
        }

    </style>
</head>
<body>
    <div id="icons-container">
        <h1>Welcome to Our App</h1>
        <button onclick="window.location.href='signup_pharmacy.php'">
            <i class="fa fa-building"></i> Sign Up Pharmacy
        </button>
        <button onclick="window.location.href='signup_user.php'">
            <i class="fa fa-user-plus"></i> Sign Up User
        </button>
        <button onclick="window.location.href='login.php'">
            <i class="fa fa-sign-in"></i> Login
        </button>
    </div>
</body>
</html>