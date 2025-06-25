<?php
session_start(); // Inicia a sessão em todas as páginas

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "mercado_bom_preco";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]);
    exit;
}

// Receber dados do formulário
$data = json_decode(file_get_contents('php://input'), true);
$name = filter_var($data['name'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$message = filter_var($data['message'], FILTER_SANITIZE_STRING);

// Verificar se o usuário está logado (opcional)
session_start();
$utilizador_id = isset($_SESSION['utilizador_id']) ? $_SESSION['utilizador_id'] : null;

$sql = "INSERT INTO suporte (utilizador_id, nome, email, mensagem, data_envio, status) 
        VALUES (:utilizador_id, :nome, :email, :mensagem, NOW(), 'pendente')";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':utilizador_id' => $utilizador_id,
    ':nome' => $name,
    ':email' => $email,
    ':mensagem' => $message
]);

http_response_code(200);
echo json_encode(['success' => true]);
?>