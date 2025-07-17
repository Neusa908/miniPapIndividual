<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$response = ['sucesso' => false, 'mensagem' => '', 'classe' => 'mensagem-erro'];

if (!isset($_SESSION['utilizador_id'])) {
    $response['mensagem'] = "Utilizador não autenticado.";
    echo json_encode($response);
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $produto_id = intval($_POST['produto_id']);
    $current_quantidade = intval($_POST['current_quantidade']);
    $acao = $_POST['acao'];
    $nova_quantidade = $acao === 'aumentar' ? $current_quantidade + 1 : $current_quantidade - 1;

    if ($nova_quantidade < 1) {
        $response['mensagem'] = "A quantidade não pode ser menor que 1.";
        echo json_encode($response);
        exit();
    }

    // Verifica estoque
    $sql = "SELECT quantidade_estoque, preco FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();

    if (!$produto) {
        $response['mensagem'] = "Produto não encontrado.";
        echo json_encode($response);
        exit();
    }

    if ($nova_quantidade > $produto['quantidade_estoque']) {
        $response['mensagem'] = "Estoque insuficiente.";
        echo json_encode($response);
        exit();
    }

    // Atualiza a quantidade no carrinho
    $sql = "UPDATE carrinho SET quantidade = ? WHERE id = ? AND utilizador_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $nova_quantidade, $item_id, $utilizador_id);

    if ($stmt->execute()) {
        $subtotal_linha = $produto['preco'] * $nova_quantidade;

        // Calcula total carrinho
        $sql = "SELECT c.quantidade, p.preco 
                FROM carrinho c 
                JOIN produtos p ON c.produto_id = p.id 
                WHERE c.utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $utilizador_id);
        $stmt->execute();
        $carrinho = $stmt->get_result();

        $total_carrinho = 0;
        while ($item = $carrinho->fetch_assoc()) {
            $total_carrinho += $item['preco'] * $item['quantidade'];
        }

        $desconto = isset($_SESSION['cupao']) ? $_SESSION['cupao']['desconto'] : 0;
        $total_com_desconto = max(0, $total_carrinho - $desconto);

        $response['sucesso'] = true;
        $response['mensagem'] = "Quantidade atualizada com sucesso.";
        $response['classe'] = 'mensagem-sucesso';
        $response['nova_quantidade'] = $nova_quantidade;
        $response['subtotal_linha'] = $subtotal_linha;
        $response['total_carrinho'] = $total_carrinho;
        $response['total_com_desconto'] = $total_com_desconto;
    } else {
        $response['mensagem'] = "Erro ao atualizar quantidade.";
    }
} else {
    $response['mensagem'] = "Requisição inválida.";
}

echo json_encode($response);
$conn->close();