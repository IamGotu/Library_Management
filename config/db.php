<?php
$host = '127.0.0.1'; // or your database host
$dbname = 'library_system'; // replace with your database name
$username = 'root'; // replace with your database username
$password = ''; // replace with your database password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // For better error handling
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}
?>
