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

    // Verifica o estoque do produto
    $sql = "SELECT quantidade_estoque, preco FROM produtos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $produto_id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();

    if ($produto && $nova_quantidade > 0 && $nova_quantidade <= $produto['quantidade_estoque']) {
        $sql = "UPDATE carrinho SET quantidade = ? WHERE id = ? AND utilizador_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $nova_quantidade, $item_id, $utilizador_id);
        if ($stmt->execute()) {
            // Calcula o novo subtotal da linha
            $subtotal_linha = $produto['preco'] * $nova_quantidade;

            // Recalcula o total do carrinho
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

            // Calcula o total com desconto
            $desconto = isset($_SESSION['cupao']) ? $_SESSION['cupao']['desconto'] : 0;
            $total_com_desconto = $total_carrinho - $desconto;
            if ($total_com_desconto < 0) {
                $total_com_desconto = 0;
            }

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
        $response['mensagem'] = $nova_quantidade <= 0 ? "A quantidade não pode ser menor que 1." : "Estoque insuficiente.";
    }
} else {
    $response['mensagem'] = "Requisição inválida.";
}

echo json_encode($response);
$conn->close();
?>