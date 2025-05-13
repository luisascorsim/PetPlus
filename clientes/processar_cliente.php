<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: clientes.php');
    exit;
}

// Obter dados do formulário
$cliente_id = isset($_POST['cliente_id']) ? trim($_POST['cliente_id']) : '';
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telefone = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
$endereco = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';
$cidade = isset($_POST['cidade']) ? trim($_POST['cidade']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$cep = isset($_POST['cep']) ? trim($_POST['cep']) : '';

// Validar dados
if (empty($nome) || empty($email) || empty($telefone) || empty($endereco) || empty($cidade) || empty($estado)) {
    $_SESSION['mensagem'] = 'Todos os campos obrigatórios devem ser preenchidos.';
    $_SESSION['tipo_mensagem'] = 'erro';
    header('Location: clientes.php');
    exit;
}

try {
    // Verificar se é uma edição ou um novo cadastro
    if (!empty($cliente_id)) {
        // Edição de cliente existente
        $sql = "UPDATE clientes SET 
                nome = :nome, 
                email = :email, 
                telefone = :telefone, 
                cpf = :cpf, 
                endereco = :endereco, 
                cidade = :cidade, 
                estado = :estado, 
                cep = :cep 
                WHERE id = :id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $cliente_id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':cep', $cep);
        $stmt->execute();
        
        $_SESSION['mensagem'] = 'Cliente atualizado com sucesso!';
    } else {
        // Novo cliente
        $sql = "INSERT INTO clientes (nome, email, telefone, cpf, endereco, cidade, estado, cep) 
                VALUES (:nome, :email, :telefone, :cpf, :endereco, :cidade, :estado, :cep)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':cpf', $cpf);
        $stmt->bindParam(':endereco', $endereco);
        $stmt->bindParam(':cidade', $cidade);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':cep', $cep);
        $stmt->execute();
        
        $_SESSION['mensagem'] = 'Cliente cadastrado com sucesso!';
    }
    
    $_SESSION['tipo_mensagem'] = 'sucesso';
} catch (PDOException $e) {
    $_SESSION['mensagem'] = 'Erro ao processar cliente: ' . $e->getMessage();
    $_SESSION['tipo_mensagem'] = 'erro';
}

// Redirecionar de volta para a página de clientes
header('Location: clientes.php');
exit;
