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

// Verificar se o ID foi fornecido
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pet não fornecido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

$id = intval($data['id']);

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se o pet existe e obter foto
    $stmt = $pdo->prepare("SELECT foto FROM pets WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Pet não encontrado']);
        exit;
    }
    
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Excluir pet do banco de dados
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    // Remover foto se existir
    if ($pet['foto'] && file_exists('../../' . $pet['foto'])) {
        unlink('../../' . $pet['foto']);
    }
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Pet excluído com sucesso!']);
    
} catch (PDOException $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir pet: ' . $e->getMessage()]);
}
?>
