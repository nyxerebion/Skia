<?php
session_start();
$password = "201013";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$binary_hash = password_hash($password, PASSWORD_BCRYPT);
$bin2hex_hash = bin2hex(substr($binary_hash, 0, 30));
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <title>Create Pass</title>
</head>

<body>
    Pass: <code><?= htmlspecialchars($password) ?></code>
    <br>
    Hashed: <code><?= htmlspecialchars($hashed_password) ?></code>
    <br>
    Binary Hash: <code><?= htmlspecialchars($binary_hash) ?></code>
    <br>
    Bin2Hex Hash: <code><?= htmlspecialchars($bin2hex_hash) ?></code>
</body>

</html>