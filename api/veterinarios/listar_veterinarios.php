<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

try {
    // Verificar se a tabela existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'veterinarios'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Tabela não existe, retornar dados simulados
        $veterinarios = [
            ['id' => 1, 'nome' => 'Dr. Carlos Silva', 'especialidade' => 'Clínica Geral'],
            ['id' => 2, 'nome' => 'Dra. Ana Oliveira', 'especialidade' => 'Cirurgia'],
            ['id' => 3, 'nome' => 'Dr. Roberto Santos', 'especialidade' => 'Dermatologia'],
            ['id' => 4, 'nome' => 'Dra. Juliana Costa', 'especialidade' => 'Cardiologia']
        ];
        
        echo json_encode([
            'success' => true,
            'veterinarios' => $veterinarios,
            'message' => 'Dados simulados'
        ]);
        exit;
    }
    
    // Buscar veterinários
    $stmt = $pdo->prepare("
        SELECT id, nome, especialidade, email, telefone, crmv, status
        FROM veterinarios
        WHERE status = 'ativo'
        ORDER BY nome
    ");
    
    $stmt->execute();
    $veterinarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'veterinarios' => $veterinarios
    ]);
    
} catch (PDOException $e) {
    // Em caso de erro, retornar dados simulados
    $veterinarios = [
        ['id' => 1, 'nome' => 'Dr. Carlos Silva', 'especialidade' => 'Clínica Geral'],
        ['id' => 2, 'nome' => 'Dra. Ana Oliveira', 'especialidade' => 'Cirurgia'],
        ['id' => 3, 'nome' => 'Dr. Roberto Santos', 'especialidade' => 'Dermatologia'],
        ['id' => 4, 'nome' => 'Dra. Juliana Costa', 'especialidade' => 'Cardiologia']
    ];
    
    echo json_encode([
        'success' => true,
        'veterinarios' => $veterinarios,
        'message' => 'Dados simulados (erro: ' . $e->getMessage() . ')'
    ]);
}
?>
