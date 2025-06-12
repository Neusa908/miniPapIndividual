<?php
session_start();
require 'conexao.php';
header('Content-Type: application/json');

if (!isset($_GET['admin_id'])) {
    echo json_encode(['novas' => false]);
    exit;
}

$admin_id = (int)$_GET['admin_id'];
$stmt = $conn->prepare("SELECT COUNT(*) as novas FROM notificacoes WHERE admin_id = ? AND lida = 0");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo json_encode(['novas' => $row['novas'] > 0]);

$stmt->close();
$conn->close();
?>