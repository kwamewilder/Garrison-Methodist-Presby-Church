<?php
require_once 'includes/auth.php';

logout();

header('Location: login.php?logout=1');
exit;
?>
