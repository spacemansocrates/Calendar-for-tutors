<?php
$password = "password"; // Replace with whatever password you want
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password hash: " . $hash;
?>