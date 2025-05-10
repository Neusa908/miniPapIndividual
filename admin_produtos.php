<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Processa a adição de um novo produto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['adicionar_produto'])) {
    $nome = trim($_POST['nome']);
    $preco = floatval($_POST['preco']);
    $estoque = intval($_POST['estoque']);
    $categoria_id = intval($_POST['categoria_id']);
    $imagem = trim($_POST['imagem']);

    $sql = "INSERT INTO produtos (nome, preco, estoque, categoria_id, imagem) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdiis", $nome, $preco, $estoque, $categoria_id, $imagem);
    if ($stmt->execute()) {
        echo "<script>alert('Produto adicionado com sucesso!'); window.location.href='admin_produtos.php';</script>";
    } else {
        echo "<script>alert('Erro ao adicionar o produto. Tente novamente.');</script>";
    }
    $stmt->close();
}

// Busca todas as categorias para o formulário
$sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Produto - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="admin-container">
        <h2 class="admin-title">Adicionar Produto</h2>

        <!-- Formulário de Adição de Produto -->
        <div class="section add-product-section">
            <h3 class="section-title">Novo Produto</h3>
            <form class="form-container" method="POST" action="admin_produtos.php">
                <input type="text" name="nome" class="form-input" placeholder="Nome do Produto" required>
                <input type="number" name="preco" class="form-input" placeholder="Preço (€)" step="0.01" required>
                <input type="number" name="estoque" class="form-input" placeholder="Estoque" required>
                <select name="categoria_id" class="form-select" required>
                    <option value="">Selecione uma Categoria</option>
                    <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="imagem" class="form-input" placeholder="URL da Imagem" required>
                <button type="submit" name="adicionar_produto" class="form-button">Adicionar Produto</button>
            </form>
        </div>

        <div class="admin-links">
            <a href="admin_lista_produtos.php" class="admin-link">Ver Lista de Produtos</a>
            <a href="admin_panel.php" class="admin-link">Voltar para o Painel Administrativo</a>
        </div>
    </div>

    <footer>
        <p>© 2025 Mercado Bom Preço. Todos os direitos reservados.</p>
    </footer>
</body>

</html>

<?php
$conn->close();
?>