<?php
require 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["password"];
    $confirmar_senha = $_POST["confirm-password"];
    $telefone = $_POST["telefone"];
    $morada = $_POST["morada"];

    // Verifica se as senhas coincidem
    if ($senha !== $confirmar_senha) {
        echo "<script>alert('As senhas não coincidem!'); window.location.href='registar.php';</script>";
        exit;
    }

    // Hash da senha para segurança
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o email já existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Este email já está registado!'); window.location.href='registar.php';</script>";
        exit;
    }

    // Insere o novo utilizador
    $sql = "INSERT INTO usuarios (nome, email, senha, telefone, morada) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nome, $email, $senha_hash, $telefone, $morada);

    if ($stmt->execute()) {
        echo "<script>alert('Registo concluído com sucesso!'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Erro ao registar!');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>