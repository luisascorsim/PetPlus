<?php
// Incluir arquivo de conexão
require_once '../conexao.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => true, 'mensagem' => 'Método não permitido']);
    exit;
}

// Obter dados do formulário
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
$endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
$cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';

// Validar campos obrigatórios
if (empty($nome) || empty($email) || empty($telefone)) {
    echo json_encode(['erro' => true, 'mensagem' => 'Nome, email e telefone são obrigatórios']);
    exit;
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['erro' => true, 'mensagem' => 'Email inválido']);
    exit;
}

try {
    // Verificar se o email já está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['erro' => true, 'mensagem' => 'Este email já está cadastrado']);
        exit;
    }
    
    // Inserir cliente no banco de dados
    $stmt = $pdo->prepare("
        INSERT INTO clientes (nome, email, telefone, cpf, endereco, cidade, estado, cep, data_cadastro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$nome, $email, $telefone, $cpf, $endereco, $cidade, $estado, $cep]);
    
    // Verificar se o cliente foi inserido
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'erro' => false, 
            'mensagem' => 'Cliente cadastrado com sucesso',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode(['erro' => true, 'mensagem' => 'Erro ao cadastrar cliente']);
    }
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao cadastrar cliente: " . $e->getMessage());
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao cadastrar cliente']);
}
?>
