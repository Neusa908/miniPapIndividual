<?php
session_start();
require_once 'conexao.php';

// Verifica se há mensagem para exibir
$mensagem = isset($_SESSION['mensagem']) ? $_SESSION['mensagem'] : '';
$mensagem_classe = isset($_SESSION['mensagem_sucesso']) ? 'mensagem-sucesso' : 'mensagem';
unset($_SESSION['mensagem'], $_SESSION['mensagem_sucesso']);

// Obtém todas as categorias para o dropdown
$sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);

// Verifica se uma categoria foi selecionada
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

// Obtém os produtos com base na categoria selecionada
$sql = "SELECT p.id, p.nome, p.preco, p.descricao, p.quantidade_estoque, p.imagem 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id";
if ($categoria_id > 0) {
    $sql .= " WHERE p.categoria_id = ?";
}
$sql .= " LIMIT ?, ?";

// Configuração de paginação
$produtos_por_pagina = 3;
$offset = 0;
$total_paginas = 1;
$pagina_atual = 1;
if ($result_categorias->num_rows > 0) {
    $temp_sql = "SELECT COUNT(*) as total FROM produtos p";
    if ($categoria_id > 0) {
        $temp_sql .= " WHERE p.categoria_id = $categoria_id";
    }
    $result_temp = $conn->query($temp_sql);
    $total_produtos = $result_temp->fetch_assoc()['total'];
    $total_paginas = ceil($total_produtos / $produtos_por_pagina);
    $pagina_atual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $pagina_atual = max(1, min($pagina_atual, $total_paginas));
    $offset = ($pagina_atual - 1) * $produtos_por_pagina;
}

// Prepara e executa a query com paginação
$stmt = $conn->prepare($sql);
if ($categoria_id > 0) {
    $stmt->bind_param("iii", $categoria_id, $offset, $produtos_por_pagina);
} else {
    $stmt->bind_param("ii", $offset, $produtos_por_pagina);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Mercado Bom Preço</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="box-container">
        <h1 class="products-title">Produtos</h1>

        <?php if ($mensagem): ?>
        <div id="mensagem" class="<?php echo $mensagem_classe; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
        <script>
        setTimeout(() => {
            document.getElementById('mensagem').style.display = 'none';
        }, 2000);
        </script>
        <?php endif; ?>

        <!-- Filtro de Categorias -->
        <div class="filtro-categorias">
            <form method="GET" action="produtos.php" class="filtro-form">
                <select name="categoria_id" class="filtro-select" onchange="this.form.submit()">
                    <option value="0">Todas as Categorias</option>
                    <?php 
                    $result_categorias->data_seek(0); // Reset do ponteiro
                    while ($categoria = $result_categorias->fetch_assoc()): 
                    ?>
                    <option value="<?php echo $categoria['id']; ?>"
                        <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($categoria['nome']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>

        <div class="products-header">
            <div class="pagination">
                <a href="?pagina=<?php echo max(1, $pagina_atual - 1); ?>&categoria_id=<?php echo $categoria_id; ?>"
                    class="pagination-arrow <?php echo $pagina_atual == 1 ? 'disabled' : ''; ?>">
                    < <span class="pagination-info"><?php echo "$pagina_atual / $total_paginas"; ?></span>
                        <a href="?pagina=<?php echo min($total_paginas, $pagina_atual + 1); ?>&categoria_id=<?php echo $categoria_id; ?>"
                            class="pagination-arrow <?php echo $pagina_atual == $total_paginas ? 'disabled' : ''; ?>">></a>
            </div>
        </div>

        <div class="produto-lista">
            <?php if ($result->num_rows > 0): ?>
            <?php while ($produto = $result->fetch_assoc()): ?>
            <div class="produto-item">
                <img src="<?php echo htmlspecialchars($produto['imagem'] ?? 'img/default-product.jpg'); ?>"
                    alt="<?php echo htmlspecialchars($produto['nome']); ?>" class="produto-imagem"
                    title="Caminho: <?php echo htmlspecialchars($produto['imagem'] ?? 'default'); ?>">
                <div class="produto-detalhes">
                    <h3 class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></h3>
                    <p class="produto-descricao"><?php echo htmlspecialchars($produto['descricao']); ?></p>
                    <span class="preco">€<?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
                    <p class="produto-estoque">Em estoque: <?php echo $produto['quantidade_estoque']; ?> Unidades</p>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                    <form method="POST" action="carrinho.php" class="produto-form">
                        <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                        <div class="quantidade-container">
                            <label for="quantidade_<?php echo $produto['id']; ?>"
                                class="quantidade-label">Quantidade:</label>
                            <input type="number" name="quantidade" id="quantidade_<?php echo $produto['id']; ?>" min="1"
                                max="<?php echo $produto['quantidade_estoque']; ?>" value="1"
                                class="produto-quantidade">
                        </div>
                        <button class="produto-button" type="submit" name="adicionar_carrinho">Adicionar</button>
                    </form>
                    <?php else: ?>
                    <p class="erro">Faça login para adicionar ao carrinho.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
            <?php else: ?>
            <p class="sem-produtos">Nenhum produto disponível no momento.</p>
            <?php endif; ?>
        </div>
    </div>
    <footer>
        <p>© 2024-2025 Mercado Bom Preço, onde o preço é bom!</p>
    </footer>
</body>

</html>
<?php $stmt->close(); $conn->close(); ?>