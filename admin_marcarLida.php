<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

if (isset($_GET['id'])) {
    $notificacao_id = (int)$_GET['id'];
    $admin_id = $_SESSION['usuario_id'];

    // Depuração
    error_log("Tentativa de marcar como lida para notificação ID: $notificacao_id, admin_id: $admin_id");

    $sql = "UPDATE notificacoes SET lida = 1 WHERE id = ? AND admin_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificacao_id, $admin_id);

    if ($stmt->execute()) {
        error_log("Notificação ID $notificacao_id marcada como lida com sucesso.");
        $stmt->close();
        $conn->close();
        header("Location: admin_notificacoes.php");
        exit;
    } else {
        error_log("Erro ao marcar como lida: " . $conn->error);
        $stmt->close();
        $conn->close();
        echo "<script>alert('Erro ao marcar como lida.'); window.location.href='admin_notificacoes.php';</script>";
        exit;
    }
} else {
    $conn->close();
    echo "<script>alert('ID da notificação não fornecido.'); window.location.href='admin_notificacoes.php';</script>";
    exit;
}
?>