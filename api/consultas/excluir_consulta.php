<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados da requisição
$data = json_decode(file_get_contents('php://input'), true);

// Validar ID da consulta
if (empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da consulta não fornecido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

try {
    // Verificar se a consulta existe
    $stmt = $pdo->prepare("SELECT id FROM consultas WHERE id = :id");
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Consulta não encontrada']);
        exit;
    }
    
    // Excluir consulta
    $stmt = $pdo->prepare("DELETE FROM consultas WHERE id = :id");
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Consulta excluída com sucesso'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao excluir consulta: ' . $e->getMessage()
    ]);
}
?>
