<?php
/**
 * User Logout Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

logoutUser();

$_SESSION['flash_success'] = "You have been safely signed out. See you next time!";
header("Location: " . BASE_URL . "/index.php");
exit;
