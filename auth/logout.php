<?php
session_start();

session_unset();
session_destroy();

header("Location: /BFC-dev2/auth/login.php");
exit;
?>