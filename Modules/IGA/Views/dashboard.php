<!DOCTYPE html>
<html>

<head>
    <title>Dashboard - IGA</title>
</head>

<body>
    <h1>IGA Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['iga_username']) ?>!</p>
    <a href="?page=login&action=logout">Logout</a>
</body>

</html>