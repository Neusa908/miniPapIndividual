<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o usuário é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Processa a edição de um produto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['editar_produto'])) {
    $produto_id = intval($_POST['produto_id']);
    $nome = trim($_POST['nome']);
    $preco = floatval($_POST['preco']);
    $estoque = intval($_POST['estoque']);
    $categoria_id = intval($_POST['categoria_id']);
    $imagem = trim($_POST['imagem']);

    $sql = "UPDATE produtos SET nome = ?, preco = ?, estoque = ?, categoria_id = ?, imagem = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdiisi", $nome, $preco, $estoque, $categoria_id, $imagem, $produto_id);
    if ($stmt->execute()) {
        echo "<script>alert('Produto atualizado com sucesso!'); window.location.href='editar_produto.php';</script>";
    } else {
        echo "<script>alert('Erro ao atualizar o produto. Tente novamente.');</script>";
    }
    $stmt->close();
}

// Processa a exclusão de um produto
if (isset($_GET['delete_produto'])) {
    $produto_id = intval($_GET['delete_produto']);
    $sql = "DELETE FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    if ($stmt->execute()) {
        echo "<script>alert('Produto excluído com sucesso!'); window.location.href='editar_produto.php';</script>";
    } else {
        echo "<script>alert('Erro ao excluir o produto. Tente novamente.');</script>";
    }
    $stmt->close();
}

// Busca todos os produtos
$sql_produtos = "SELECT p.id, p.nome, p.preco, p.estoque, p.categoria_id, p.imagem, c.nome AS categoria_nome 
                 FROM produtos p 
                 LEFT JOIN categorias c ON p.categoria_id = c.id 
                 ORDER BY p.id DESC";
$result_produtos = $conn->query($sql_produtos);

// Busca todas as categorias para o formulário de edição
$sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);

// Busca os dados do produto para edição, se um ID for fornecido
$produto = null;
if (isset($_GET['id'])) {
    $produto_id = intval($_GET['id']);
    $sql = "SELECT p.id, p.nome, p.preco, p.estoque, p.categoria_id, p.imagem, c.nome AS categoria_nome 
            FROM produtos p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produtos - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="support-body">
    <div class="admin-container edit-products-container">
        <h2 class="admin-title">Editar Produtos</h2>

        <!-- Formulário de Edição (aparece apenas se um produto for selecionado) -->
        <?php if ($produto): ?>
        <div class="section edit-product-section">
            <h3 class="section-title">Editar Produto</h3>
            <form class="form-container edit-product-form" method="POST" action="editar_produto.php">
                <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                <input type="text" name="nome" class="form-input"
                    value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
                <input type="number" name="preco" class="form-input" value="<?php echo $produto['preco']; ?>"
                    step="0.01" required>
                <input type="number" name="estoque" class="form-input" value="<?php echo $produto['estoque']; ?>"
                    required>
                <select name="categoria_id" class="form-select" required>
                    <option value="">Selecione uma Categoria</option>
                    <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>"
                        <?php echo $cat['id'] == $produto['categoria_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['nome']); ?>
                    </option>
                    <?php endwhile; ?>
                    <?php $result_categorias->data_seek(0); // Reseta o ponteiro para usar novamente ?>
                </select>
                <input type="text" name="imagem" class="form-input"
                    value="<?php echo htmlspecialchars($produto['imagem']); ?>" required>
                <button type="submit" name="editar_produto" class="form-button">Salvar Alterações</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Lista de Produtos -->
        <div class="section product-list-section">
            <h3 class="section-title">Lista de Produtos</h3>
            <div class="product-items">
                <?php if ($result_produtos->num_rows > 0): ?>
                <?php while ($row = $result_produtos->fetch_assoc()): ?>
                <div class="product-item">
                    <p class="product-detail"><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></p>
                    <p class="product-detail"><strong>Preço:</strong> €
                        <?php echo number_format($row['preco'], 2, ',', '.'); ?></p>
                    <p class="product-detail"><strong>Estoque:</strong> <?php echo $row['estoque']; ?></p>
                    <p class="product-detail"><strong>Categoria:</strong>
                        <?php echo htmlspecialchars($row['categoria_nome'] ?: ''); ?></p>
                    <p class="product-detail"><strong>Imagem:</strong> <?php echo htmlspecialchars($row['imagem']); ?>
                    </p>
                    <div class="action-buttons">
                        <a href="admin_editarProduto.php?id=<?php echo $row['id']; ?>" class="edit-link">Editar</a>
                        <a href="admin_editarProduto.php?delete_produto=<?php echo $row['id']; ?>" class="delete-link"
                            onclick="return confirm('Tem certeza que deseja excluir este produto?');">Excluir</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p class="no-products">Nenhum produto encontrado.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-links">
            <br><a href="admin_produtos.php" class="admin-link">Adicionar Novo Produto</a>
            <br><a href="admin_panel.php" class="admin-link">Voltar para o Painel Administrativo</a>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>