<?php
// เปลี่ยนค่าเหล่านี้ให้ตรงกับ Server ของคุณก่อนนำไปใช้งาน
$host = 'localhost';
$dbname = 'YOUR_DATABASE_NAME'; 
$user = 'YOUR_DATABASE_USER';        
$pass = 'YOUR_DATABASE_PASSWORD';        

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>