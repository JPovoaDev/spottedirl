<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registar – SpottedIRL</title>
</head>
<body>
    <h1>Registar</h1>
    <form method="POST" action="../controllers/register_action.php">
        <label>Username: <input type="text" name="username" required></label><br>
        <label>Email: <input type="email" name="email" required></label><br>
        <label>Password: <input type="password" name="password" required></label><br>
        <button type="submit">Registar</button>
    </form>
    <a href="login.php">Já tenho conta</a>
</body>
</html>