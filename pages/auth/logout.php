<?php
require_once '../../config/session.php';

// Perform logout
logoutUser();

// Redirect to login page with success message
header('Location: login.php?message=You have been successfully logged out');
exit();
?>