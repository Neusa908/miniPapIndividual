<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php'; // Inclui a conexão com o banco de dados

// Verifica se o utilizador é administrador
if (!isset($_SESSION['utilizador_id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem acessar esta página.'); window.location.href='index.php';</script>";
    exit();
}

// Busca todos os produtos
$sql_produtos = "SELECT p.id, p.nome, p.preco, p.estoque, p.categoria_id, p.imagem, c.nome AS categoria_nome 
                 FROM produtos p 
                 LEFT JOIN categorias c ON p.categoria_id = c.id 
                 ORDER BY p.id DESC";
$result_produtos = $conn->query($sql_produtos);

?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="admin-container">
        <h2 class="admin-title">Lista de Produtos</h2>
        <div class="section product-list-section">
            <h3 class="section-title">Produtos Registados</h3>
            <div class="product-items">
                <?php if ($result_produtos->num_rows > 0): ?>
                <?php while ($row = $result_produtos->fetch_assoc()): ?>
                <div class="product-item">
                    <p class="product-detail"><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></p>
                    <p class="product-detail"><strong>Preço:</strong>
                        €<?php echo number_format($row['preco'], 2, ',', '.'); ?></p>
                    <p class="product-detail"><strong>Estoque:</strong> <?php echo $row['estoque']; ?></p>
                    <p class="product-detail"><strong>Categoria:</strong>
                        <?php echo htmlspecialchars($row['categoria_nome'] ?: 'Sem categoria'); ?></p>
                    <p class="product-detail"><strong>Imagem:</strong> <?php echo htmlspecialchars($row['imagem']); ?>
                    </p>
                    <div class="action-buttons">
                        <a href="editar_produto.php?id=<?php echo $row['id']; ?>" class="edit-link">Editar</a>
                        <a href="editar_produto.php?delete_produto=<?php echo $row['id']; ?>" class="delete-link"
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
            <a href="admin_produtos.php" class="admin-link">Adicionar Novo Produto</a>
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