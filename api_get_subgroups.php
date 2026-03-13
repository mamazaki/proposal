<?php
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

$main_topic_id = $_GET['main_id'] ?? 0;
$stmt = $pdo->prepare("SELECT id, title FROM sub_groups WHERE main_topic_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->execute([$main_topic_id]);
echo json_encode($stmt->fetchAll());
?>