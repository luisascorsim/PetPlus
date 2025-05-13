<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID da consulta não fornecido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

$id = intval($_GET['id']);

try {
    // Buscar dados da consulta
    $stmt = $pdo->prepare("
        SELECT c.id, c.pet_id, c.tipo_consulta, c.data_hora, c.veterinario_id, c.status, c.observacoes,
               p.nome as pet_nome, cl.nome as cliente_nome, v.nome as veterinario_nome
        FROM consultas c
        JOIN pets p ON c.pet_id = p.id
        JOIN clientes cl ON p.cliente_id = cl.id
        LEFT JOIN veterinarios v ON c.veterinario_id = v.id
        WHERE c.id = :id
    ");
    
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Consulta não encontrada']);
        exit;
    }
    
    $consulta = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'consulta' => $consulta
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados da consulta: ' . $e->getMessage()
    ]);
}
?>
