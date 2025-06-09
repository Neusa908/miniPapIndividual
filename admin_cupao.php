<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "<script>alert('Faça login para acessar esta página.'); window.location.href='login.php';</script>";
    exit();
}

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}