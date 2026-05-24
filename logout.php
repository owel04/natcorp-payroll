<?php
require_once 'config.php';
require_once 'includes/Auth.php';

$auth = new Auth($conn);
$auth->logout();

header('Location: index.php');
exit;
?>
