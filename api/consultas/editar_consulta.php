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

// Validar dados obrigatórios
if (empty($data['id']) || empty($data['pet_id']) || empty($data['tipo_consulta']) || 
    empty($data['data_hora']) || empty($data['veterinario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos obrigatórios devem ser preenchidos']);
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
    
    // Verificar disponibilidade do horário (excluindo a própria consulta)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM consultas 
        WHERE veterinario_id = :veterinario_id 
        AND data_hora = :data_hora 
        AND status != 'cancelada'
        AND id != :id
    ");
    
    $stmt->bindParam(':veterinario_id', $data['veterinario_id']);
    $stmt->bindParam(':data_hora', $data['data_hora']);
    $stmt->bindParam(':id', $data['id']);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($resultado['total'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Horário já ocupado para este veterinário']);
        exit;
    }
    
    // Atualizar consulta
    $stmt = $pdo->prepare("
        UPDATE consultas SET
            pet_id = :pet_id,
            tipo_consulta = :tipo_consulta,
            data_hora = :data_hora,
            veterinario_id = :veterinario_id,
            status = :status,
            observacoes = :observacoes,
            data_atualizacao = NOW()
        WHERE id = :id
    ");
    
    $stmt->bindParam(':id', $data['id']);
    $stmt->bindParam(':pet_id', $data['pet_id']);
    $stmt->bindParam(':tipo_consulta', $data['tipo_consulta']);
    $stmt->bindParam(':data_hora', $data['data_hora']);
    $stmt->bindParam(':veterinario_id', $data['veterinario_id']);
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':observacoes', $data['observacoes']);
    
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Consulta atualizada com sucesso'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar consulta: ' . $e->getMessage()
    ]);
}
?>
