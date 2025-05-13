<?php
// Incluir arquivos necessários
require_once '../config/database.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: clientes.php');
    exit;
}

$cliente_id = intval($_GET['id']);

// Obter dados do cliente
$pdo = Database::conexao();
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o cliente não existir, redirecionar
if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

// Título da página
$titulo = "Detalhes do Cliente - PetPlus";

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Detalhes do Cliente</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Detalhes do Cliente</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Informações do Cliente</h5>
                    <div>
                        <a href="editar_cliente.php?id=<?php echo $cliente_id; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nome:</strong> <?php echo htmlspecialchars($cliente['nome']); ?></p>
                            <p><strong>CPF:</strong> <?php echo htmlspecialchars($cliente['cpf']); ?></p>
                            <p><strong>RG:</strong> <?php echo htmlspecialchars($cliente['rg'] ?? 'Não informado'); ?></p>
                            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($cliente['endereco']); ?></p>
                            <p><strong>Cidade:</strong> <?php echo htmlspecialchars($cliente['cidade']); ?></p>
                            <p><strong>Estado:</strong> <?php echo htmlspecialchars($cliente['estado']); ?></p>
                            <p><strong>CEP:</strong> <?
