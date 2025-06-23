<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    $_SESSION['mensagem'] = "Faça login para favoritar produtos.";
    header("Location: login.php");
    exit;
}

if (isset($_POST['adicionar_favorito']) && isset($_POST['produto_id'])) {
    $utilizador_id = $_SESSION['utilizador_id'];
    $produto_id = (int)$_POST['produto_id'];

    // Verifica se o produto já está favoritado
    $stmt = $conn->prepare("SELECT id FROM favoritos WHERE utilizador_id = ? AND produto_id = ?");
    $stmt->bind_param("ii", $utilizador_id, $produto_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Remove dos favoritos
        $stmt = $conn->prepare("DELETE FROM favoritos WHERE utilizador_id = ? AND produto_id = ?");
        $stmt->bind_param("ii", $utilizador_id, $produto_id);
        $stmt->execute();
        $_SESSION['mensagem'] = "Produto removido dos favoritos.";
        $_SESSION['mensagem_sucesso'] = true;
    } else {
        // Adiciona aos favoritos
        $stmt = $conn->prepare("INSERT INTO favoritos (utilizador_id, produto_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $utilizador_id, $produto_id);
        $stmt->execute();
        $_SESSION['mensagem'] = "Produto adicionado aos favoritos.";
        $_SESSION['mensagem_sucesso'] = true;
    }

    $stmt->close();
    header("Location: produtos.php");
    exit;
}
?>