<?php
$password = "Naho1386"; // Replace with whatever password you want
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password hash: " . $hash;
?>