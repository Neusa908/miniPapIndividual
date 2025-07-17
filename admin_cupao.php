<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Foto de perfil
$sql_foto = "SELECT foto_perfil FROM utilizadores WHERE id = ?";
$stmt_foto = $conn->prepare($sql_foto);
$stmt_foto->bind_param("i", $_SESSION['utilizador_id']);
$stmt_foto->execute();
$result_foto = $stmt_foto->get_result();
$utilizador = $result_foto->fetch_assoc();
$foto_perfil = $utilizador['foto_perfil'] ?? 'img/perfil/default.jpg';
$stmt_foto->close();

$mensagem = '';

// Atualiza cupões vencidos
$conn->query("UPDATE promocoes SET ativa = 0 WHERE data_fim < NOW()");

// Exclusão
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir'], $_POST['id_excluir'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $id_excluir = intval($_POST['id_excluir']);
        $stmt = $conn->prepare("DELETE FROM promocoes WHERE id = ?");
        $stmt->bind_param("i", $id_excluir);
        $mensagem = $stmt->execute() ? "Cupão excluído com sucesso!" : "Erro ao excluir cupão.";
        $stmt->close();
    } else {
        $mensagem = "Token CSRF inválido.";
    }
}

// Criação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo'], $_POST['csrf_token']) && !isset($_POST['excluir'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $codigo = strtoupper(trim($_POST['codigo']));
        $desconto = floatval($_POST['desconto']);
        $valor_minimo = floatval($_POST['valor_minimo']);
        $data_inicio = $_POST['data_inicio'];
        $data_fim = $_POST['data_fim'];

        if (empty($codigo) || $desconto < 0.01 || $valor_minimo < 0 || empty($data_inicio) || empty($data_fim)) {
            $mensagem = "Preencha todos os campos corretamente.";
        } elseif (strtotime($data_fim) <= strtotime($data_inicio)) {
            $mensagem = "A data de validade deve ser posterior à data de início.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM promocoes WHERE codigo = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $mensagem = "Já existe um cupão com esse código.";
            } else {
                $sql = "INSERT INTO promocoes (codigo, desconto, data_inicio, data_fim, ativa, valor_minimo) 
                        VALUES (?, ?, ?, ?, 1, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sdssd", $codigo, $desconto, $data_inicio, $data_fim, $valor_minimo);
                $stmt->execute();
                $mensagem = $stmt->affected_rows > 0 ? "Cupão criado com sucesso!" : "Erro ao criar cupão.";
            }
            $stmt->close();
        }
    } else {
        $mensagem = "Token CSRF inválido.";
    }
}

$result = $conn->query("SELECT * FROM promocoes ORDER BY data_fim DESC");
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Cupões - Admin</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="admin-cupao-body">
    <div class="admin-cupao-container">
        <div class="usuario-foto-container">
            <img src="<?= htmlspecialchars($foto_perfil) ?>" alt="Foto de Perfil" class="usuario-foto">
        </div>

        <h1 class="admin-cupao-title">Gestão de Cupões</h1>

        <?php if ($mensagem): ?>
        <p class="admin-cupao-msg"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <form method="POST" class="admin-cupao-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <label for="codigo">Código do Cupão:</label>
            <input type="text" name="codigo" id="codigo" maxlength="20" required>

            <label for="desconto">Desconto (€):</label>
            <input type="number" step="0.01" name="desconto" id="desconto" min="0.01" required>

            <label for="valor_minimo">Valor Mínimo de Compra (€):</label>
            <input type="number" step="0.01" name="valor_minimo" id="valor_minimo" min="0.00" required>

            <label for="data_inicio">Data de Início:</label>
            <input type="datetime-local" name="data_inicio" id="data_inicio" required>

            <label for="data_fim">Data de Validade:</label>
            <input type="datetime-local" name="data_fim" id="data_fim" required>

            <button type="submit" class="admin-cupao-btn">Criar Cupão</button>
        </form>

        <hr class="admin-cupao-sep">

        <h2 class="admin-cupao-subtitle">Cupões Existentes</h2>
        <table class="admin-cupao-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Desconto (€)</th>
                    <th>Valor Mínimo (€)</th>
                    <th>Início</th>
                    <th>Validade</th>
                    <th>Ativo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($cupao = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($cupao['codigo']) ?></td>
                    <td>€<?= number_format($cupao['desconto'], 2, ',', '.') ?></td>
                    <td>€<?= number_format($cupao['valor_minimo'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($cupao['data_inicio'])) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($cupao['data_fim'])) ?></td>
                    <td><?= $cupao['ativa'] ? 'Sim' : 'Não' ?></td>
                    <td class="admin-cupao-acoes">
                        <button type="button" class="admin-cupao-btn-editar"
                            onclick="window.location.href='admin_editarCupao.php?codigo=<?= urlencode($cupao['codigo']) ?>'">
                            Editar
                        </button>
                        <form method="POST" style="display:inline;"
                            onsubmit="return confirm('Tem certeza que deseja excluir este cupão?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id_excluir" value="<?= intval($cupao['id']) ?>">
                            <button type="submit" name="excluir" class="admin-cupao-btn-excluir">Deletar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <a href="admin_panel.php" class="admin-cupao-voltar">Voltar ao Painel</a>
    </div>
</body>

</html>