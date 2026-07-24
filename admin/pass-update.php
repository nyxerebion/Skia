<?php
session_start();
$password = "201013";
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
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
</body>

</html>