<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

$q = $_GET['q'] ?? '';
if (mb_strlen($q) < 2) { 
    echo json_encode([]); exit; 
}

$stmt = $pdo->prepare("
    SELECT prefix, first_name, last_name, job_position, agency, phone_number 
    FROM committee_members 
    WHERE first_name LIKE ? OR last_name LIKE ? 
    GROUP BY first_name, last_name 
    LIMIT 10
");
$stmt->execute(["%$q%", "%$q%"]);
echo json_encode($stmt->fetchAll());
?>