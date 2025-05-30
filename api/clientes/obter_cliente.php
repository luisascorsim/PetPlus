<?php
// Incluir arquivo de conexão
require_once '../conexao.php';

// Verificar se o ID foi fornecido
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validar ID
if ($id <= 0) {
    echo json_encode(['erro' => true, 'mensagem' => 'ID inválido']);
    exit;
}

try {
    // Buscar dados do cliente
    $stmt = $pdo->prepare("SELECT c.*, 
               (SELECT COUNT(*) FROM pets WHERE cliente_id = c.id) AS qtd_pets
        FROM clientes c
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['erro' => true, 'mensagem' => 'Cliente não encontrado']);
        exit;
    }
    
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Buscar pets do cliente
    $stmt = $pdo->prepare("
        SELECT * FROM pets WHERE cliente_id = ? ORDER BY nome
    ");
    $stmt->execute([$id]);
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar pets ao resultado
    $cliente['pets'] = $pets;
    
    echo json_encode(['erro' => false, 'cliente' => $cliente]);
} catch (PDOException $e) {
    // Log do erro
    error_log("Erro ao buscar cliente: " . $e->getMessage());
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao buscar dados do cliente']);
}
?>
