<?php
// Incluir arquivo de conexão
require_once '../conexao.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => true, 'mensagem' => 'Método não permitido']);
    exit;
}

// Obter dados do formulário
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
$endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
$cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';

// Validar campos obrigatórios
if ($id <= 0 || empty($nome) || empty($email) || empty($telefone)) {
    echo json_encode(['erro' => true, 'mensagem' => 'ID, nome, email e telefone são obrigatórios']);
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['erro' => true, 'mensagem' => 'Email inválido']);
    exit;
}

try {
    // Verificar se o cliente existe
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['erro' => true, 'mensagem' => 'Cliente não encontrado']);
        exit;
    }
    
    // Verificar se o email já está sendo usado por outro cliente
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['erro' => true, 'mensagem' => 'Este email já está sendo usado por outro cliente']);
        exit;
    }
    
    // Atualizar cliente no banco de dados
    $stmt = $pdo->prepare("
        UPDATE clientes 
        SET nome = ?, email = ?, telefone = ?, cpf = ?, endereco = ?, cidade = ?, estado = ?, cep = ?, data_atualizacao = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$nome, $email, $telefone, $cpf, $endereco, $cidade, $estado, $cep, $id]);
    
    echo json_encode(['erro' => false, 'mensagem' => 'Cliente atualizado com sucesso']);
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao atualizar cliente: " . $e->getMessage());
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao atualizar cliente']);
}
?>
