<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'conexao.php';

// Buscar foto de perfil do administrador
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $_SESSION['utilizador_id']);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg'; // Fallback
$stmt_foto->close();

// Apagar produto se enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_produto_id'])) {
    $produto_id = intval($_POST['delete_produto_id']);

    // Deleta a imagem se houver uma
    $stmt = $conn->prepare("SELECT imagem FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->bind_result($imagem_path);
    $stmt->fetch();
    $stmt->close();

    if ($imagem_path && file_exists($imagem_path)) {
        unlink($imagem_path); // Apaga o arquivo da imagem
    }

    $stmt = $conn->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Produto excluído com sucesso!'); window.location.href='admin_lista_produtos.php';</script>";
    exit();
}

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado!'); window.location.href='index.php';</script>";
    exit();
}

// Buscar categorias para o filtro
$sql_categorias = "SELECT id, nome FROM categorias ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);

// Receber a categoria filtrada, se houver
$categoria_filtro = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

$sql_produtos = "SELECT p.id, p.nome, p.preco, p.categoria_id, p.quantidade_estoque, p.imagem, c.nome AS categoria_nome 
                 FROM produtos p 
                 LEFT JOIN categorias c ON p.categoria_id = c.id";

if ($categoria_filtro > 0) {
    $sql_produtos .= " WHERE p.categoria_id = $categoria_filtro";
}

$sql_produtos .= " ORDER BY p.id DESC";

$result_produtos = $conn->query($sql_produtos);
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>
    <meta charset="UTF-8">
    <title>Lista de Produtos - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-lista-body">
    <div class="admin-lista-container">
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>
        <h2 class="admin-lista-title">Produtos Registados</h2>


        <!-- filtro por categoria -->
        <form method="GET" id="filtroCategoriaForm" class="filtro-categoria-form">
            <label for="categoria" class="filtro-categoria-label">Filtrar por Categoria:</label>
            <select name="categoria" id="categoria" class="filtro-categoria-select"
                onchange="document.getElementById('filtroCategoriaForm').submit()">
                <option value="0" <?= $categoria_filtro === 0 ? 'selected' : '' ?>>Todas</option>
                <?php while ($cat = $result_categorias->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" <?= $categoria_filtro === intval($cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </form>



        <?php if ($result_produtos->num_rows > 0): ?>
        <div class="admin-lista-grid">
            <?php while ($row = $result_produtos->fetch_assoc()): ?>
            <div class="admin-lista-card">
                <div class="admin-lista-image">
                    <?php if (!empty($row['imagem']) && file_exists($row['imagem'])): ?>
                    <img src="<?php echo htmlspecialchars($row['imagem']); ?>" alt="Imagem do Produto">
                    <?php else: ?>
                    <img src="img/default-product.jpg" alt="Sem imagem">
                    <?php endif; ?>
                </div>
                <div class="admin-lista-info">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($row['nome']); ?></p>
                    <p><strong>Preço:</strong> €<?php echo number_format($row['preco'], 2, ',', '.'); ?></p>
                    <p><strong>Estoque:</strong> <?php echo $row['quantidade_estoque']; ?></p>
                    <p><strong>Categoria:</strong>
                        <?php echo htmlspecialchars($row['categoria_nome'] ?: 'Sem categoria'); ?>
                    </p>
                </div>
                <div class="admin-lista-actions">
                    <a href="admin_editarProduto.php?id=<?php echo $row['id']; ?>"
                        class="admin-lista-btn editar">Editar</a>
                    <form method="POST" style="display:inline;"
                        onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                        <input type="hidden" name="delete_produto_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="admin-lista-btn excluir">Excluir</button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p class="admin-lista-empty">Nenhum produto encontrado.</p>
        <?php endif; ?>

        <div class="admin-lista-links">
            <a href="admin_produtos.php" class="admin-lista-link"> Adicionar Produto</a>
            <a href="admin_panel.php" class="admin-lista-link voltar">Voltar ao Painel</a>
        </div>
    </div>

    <footer class="footer-index">
        <p>© 2025 Mercado Bom Preço. Todos os direitos reservados.</p>
    </footer>
</body>

</html>

<?php $conn->close(); ?>