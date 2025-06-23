<?php
session_start();
include 'conexao.php'; // Arquivo com conexão ao banco

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Excluir comentário
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    $sql = "DELETE FROM avaliacoes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_comentarios.php");
}

// Listar comentários
$sql = "SELECT a.*, u.nome AS usuario_nome, p.nome AS produto_nome 
        FROM avaliacoes a 
        JOIN usuarios u ON a.usuario_id = u.id 
        JOIN produtos p ON a.produto_id = p.id";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    echo "<div>";
    echo "<strong>Usuário:</strong> " . htmlspecialchars($row['usuario_nome']) . "<br>";
    echo "<strong>Produto:</strong> " . htmlspecialchars($row['produto_nome']) . "<br>";
    echo "<strong>Nota:</strong> " . $row['avaliacao'] . "<br>";
    echo "<p>" . htmlspecialchars($row['comentario']) . "</p>";
    echo "<small>" . $row['data_avaliacao'] . "</small>";
    echo "<br><a href='admin_comentarios.php?excluir=" . $row['id'] . "'>Excluir</a>";
    echo "</div><hr>";
}
?>