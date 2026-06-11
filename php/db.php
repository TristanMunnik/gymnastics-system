<?php
$host = 'localhost';
$db   = 'gymnastics_db';  
$user = 'root';
$pass = 'mysql';
$chrs = 'utf8mb4';

$attr = "mysql:host=$host;dbname=$db;charset=$chrs";

$opts = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($attr, $user, $pass, $opts);
} catch (PDOException $e) {
    echo "Connection failed";
}
?>