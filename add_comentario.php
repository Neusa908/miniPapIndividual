<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}


session_start();
include 'conexao.php'; // Arquivo com conexão ao banco

if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $utilizador_id = $_SESSION['utilizador_id'];
    $produto_id = $_POST['produto_id'];
    $avaliacao = $_POST['avaliacao'];
    $comentario = $_POST['comentario'];

    $sql = "INSERT INTO avaliacoes (utilizador_id, produto_id, avaliacao, comentario) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiis", $utilizador_id, $produto_id, $avaliacao, $comentario);

    if ($stmt->execute()) {
        echo "Comentário adicionado com sucesso!";
        header("Location: comentarios.php?produto_id=$produto_id");
    } else {
        echo "Erro ao adicionar comentário.";
    }
}
?>

<form method="POST" action="">
    <input type="hidden" name="produto_id" value="<?php echo $_GET['produto_id']; ?>">
    <label>Nota (1 a 5):</label>
    <input type="number" name="avaliacao" min="1" max="5" required>
    <label>Comentário:</label>
    <textarea name="comentario" required></textarea>
    <button type="submit">Enviar</button>
</form>