<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['utilizador_id']) || $_SESSION['tipo'] !== 'admin') {
    echo "<script>alert('Acesso negado! Apenas administradores podem aceder.'); window.location.href='index.php';</script>";
    exit();
}

// Buscar entregas com info do pedido e utilizador
$sql = "SELECT 
            e.id AS entrega_id,
            e.status_entrega,
            e.data_entrega,
            p.id AS pedido_id,
            u.nome AS cliente_nome,
            en.rua, en.numero, en.freguesia, en.cidade, en.distrito, en.codigo_postal
        FROM entregas e
        JOIN pedidos p ON e.pedido_id = p.id
        JOIN utilizadores u ON p.utilizador_id = u.id
        JOIN enderecos en ON e.endereco_id = en.id
        ORDER BY e.status_entrega = 'entregue', e.data_entrega DESC";

$result = $conn->query($sql);
$entregas = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Entregas</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="entregas-page">
    <div class="entregas-container">
        <h1 class="entregas-titulo">Painel de Entregas</h1>

        <?php if (empty($entregas)): ?>
        <p class="entregas-sem-resultados">Nenhuma entrega encontrada.</p>
        <?php else: ?>
        <table class="entregas-tabela">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pedido</th>
                    <th>Cliente</th>
                    <th>Endereço</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entregas as $entrega): ?>
                <tr class="entrega-linha">
                    <td><?= $entrega['entrega_id'] ?></td>
                    <td>#<?= $entrega['pedido_id'] ?></td>
                    <td><?= htmlspecialchars($entrega['cliente_nome']) ?></td>
                    <td>
                        <?= htmlspecialchars("{$entrega['rua']}, {$entrega['numero']} - {$entrega['freguesia']}, {$entrega['cidade']} ({$entrega['codigo_postal']})") ?>
                    </td>
                    <td>
                        <span class="entrega-status entrega-status-<?= strtolower($entrega['status_entrega']) ?>">
                            <?= ucfirst($entrega['status_entrega']) ?>
                        </span>
                    </td>
                    <td><?= $entrega['data_entrega'] ?? '-' ?></td>
                    <td>
                        <?php if ($entrega['status_entrega'] !== 'entregue'): ?>
                        <form method="POST" action="atualizar_entrega.php" class="entrega-formulario">
                            <input type="hidden" name="entrega_id" value="<?= $entrega['entrega_id'] ?>">
                            <select name="novo_status" class="entrega-select">
                                <option value="preparando"
                                    <?= $entrega['status_entrega'] == 'preparando' ? 'selected' : '' ?>>Preparando
                                </option>
                                <option value="em trânsito"
                                    <?= $entrega['status_entrega'] == 'em trânsito' ? 'selected' : '' ?>>Em Trânsito
                                </option>
                                <option value="entregue">Entregue</option>
                            </select>
                            <button type="submit" class="entrega-botao">Atualizar</button>
                        </form>
                        <?php else: ?>
                        <span class="entrega-concluida">Concluída</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

        <a href="dashboard.php" class="entregas-voltar">← Voltar ao Painel</a>
    </div>
</body>

</html>