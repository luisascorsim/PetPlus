<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID do pet não fornecido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

$id = intval($_GET['id']);

try {
    $query = "
        SELECT p.*, c.nome as proprietario
        FROM pets p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        WHERE p.id = :id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'Pet não encontrado']);
        exit;
    }
    
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($pet);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar pet: ' . $e->getMessage()]);
}
?>
