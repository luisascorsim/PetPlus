<?php
// Incluir arquivo de conexão
require_once '../conexao.php';

// Verificar se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => true, 'mensagem' => 'Método não permitido']);
    exit;
}

// Obter ID do cliente
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

// Validar ID
if ($id <= 0) {
    echo json_encode(['erro' => true, 'mensagem' => 'ID inválido']);
    exit;
}

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se o cliente existe
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['erro' => true, 'mensagem' => 'Cliente não encontrado']);
        $pdo->rollBack();
        exit;
    }
    
    // Verificar se o cliente tem pets
    $stmt = $pdo->prepare("SELECT id FROM pets WHERE cliente_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        // Excluir pets do cliente
        $stmt = $pdo->prepare("DELETE FROM pets WHERE cliente_id = ?");
        $stmt->execute([$id]);
    }
    
    // Excluir cliente
    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode(['erro' => false, 'mensagem' => 'Cliente excluído com sucesso']);
} catch (PDOException $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    
    // Log do erro
    error_log("Erro ao excluir cliente: " . $e->getMessage());
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao excluir cliente']);
}
?>
