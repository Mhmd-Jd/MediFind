<?php
// Start or resume the current session
session_start();

// Unset all session variables (clears the session data)
session_unset();

// Destroy the session completely (removes session data from server)
session_destroy();

// Redirect the user to the login page
header('Location: login.php');

// Stop further script execution
exit();
?>