<?php
$host = "localhost"; // Database server host
$user = "root"; // MySQL username 
$password = ""; // MySQL password 
$database = "medifind"; // Name of the database to connect to


// --------- Establish MySQL Database Connection ---------

// Create a new MySQLi connection using the provided credentials
$conn = new mysqli($host, $user, $password, $database);

// Check if the connection was successful
if ($conn->connect_error) {
    // If there's a connection error, stop the script and display the error message
    die("❌ Connection failed: " . $conn->connect_error);
}

?>