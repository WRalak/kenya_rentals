<?php
session_start();
session_destroy();
header("Location: /kenya_rentals/auth/login.php");
exit();
?>