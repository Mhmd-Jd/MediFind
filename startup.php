<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Startup Page</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f1f1f1;
            overflow: hidden;
        }

        #startup-image {
            width: 100%;
            height: 100vh;
            background-image: url('images/start_bg.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            position: absolute;
            opacity: 1;
            animation: zoomIn 5s ease-in-out forwards;
        }

        @keyframes zoomIn {
            0% {
                transform: scale(0.5);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div id="startup-image">
        <p>Welcome to the App</p>
    </div>

    <script>
        setTimeout(function() {
            // Fade out the startup image
            document.getElementById('startup-image').style.opacity = '0';
            document.getElementById('startup-image').style.transition = 'opacity 1s';
            document.getElementById('startup-image').style.display = 'none';

            // Redirect to the next page (startup2.php) after the image fades out
            setTimeout(function() {
                window.location.href = 'startup2.php';  // Redirect to startup2.php
            }, 1000);  // Wait an additional 1 second to let the fade-out effect finish
        }, 5000); // 5 seconds timeout for image visibility
    </script>
</body>
</html>