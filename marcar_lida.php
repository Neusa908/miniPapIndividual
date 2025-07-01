<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'cliente') {
    echo "<script>alert('Acesso negado! Apenas clientes podem acessar esta ação.'); window.location.href='index.php';</script>";
    exit();
}

if (isset($_GET['id'])) {
    $notificacao_id = (int)$_GET['id'];
    $utilizador_id = $_SESSION['utilizador_id'];

    error_log("Tentativa de marcar como lida para notificação ID: $notificacao_id, utilizador_id: $utilizador_id");

    $sql = "UPDATE notificacoes SET lida = 1 WHERE id = ? AND utilizador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notificacao_id, $utilizador_id);

    if ($stmt->execute()) {
        error_log("Notificação ID $notificacao_id marcada como lida com sucesso.");
        $stmt->close();
        $conn->close();
        header("Location: notificacoes.php");
        exit;
    } else {
        error_log("Erro ao marcar como lida: " . $conn->error);
        $stmt->close();
        $conn->close();
        echo "<script>alert('Erro ao marcar como lida.'); window.location.href='notificacoes.php';</script>";
        exit;
    }
} else {
    $conn->close();
    echo "<script>alert('ID da notificação não fornecido.'); window.location.href='notificacoes.php';</script>";
    exit;
}
?>