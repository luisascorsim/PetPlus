<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

try {
    // Parâmetros de paginação e busca
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    $por_pagina = isset($_GET['por_pagina']) ? (int)$_GET['por_pagina'] : 10;
    $busca = isset($_GET['busca']) ? $_GET['busca'] : '';
    
    $offset = ($pagina - 1) * $por_pagina;
    
    // Construir a consulta SQL
    $where = '';
    $params = [];
    
    if (!empty($busca)) {
        $where = "WHERE nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca";
        $params[':busca'] = "%{$busca}%";
    }
    
    // Consulta para contar o total de registros
    $sql_count = "SELECT COUNT(*) FROM clientes {$where}";
    $stmt_count = $pdo->prepare($sql_count);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt_count->bindValue($key, $value);
        }
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    $total_paginas = ceil($total_registros / $por_pagina);
    
    // Consulta para buscar os clientes
    $sql = "SELECT id, nome, email, telefone, endereco, cpf, cidade, estado, cep FROM clientes {$where} ORDER BY nome LIMIT :offset, :limit";
    $stmt = $pdo->prepare($sql);
    
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $por_pagina, PDO::PARAM_INT);
    $stmt->execute();
    
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada cliente, buscar seus pets
    foreach ($clientes as &$cliente) {
        $sql_pets = "SELECT id, nome, especie, raca, idade FROM pets WHERE cliente_id = :cliente_id";
        $stmt_pets = $pdo->prepare($sql_pets);
        $stmt_pets->bindParam(':cliente_id', $cliente['id'], PDO::PARAM_INT);
        $stmt_pets->execute();
        
        $cliente['pets'] = $stmt_pets->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Retornar os dados em formato JSON
    echo json_encode([
        'success' => true,
        'clientes' => $clientes,
        'paginacao' => [
            'total' => $total_registros,
            'por_pagina' => $por_pagina,
            'pagina_atual' => $pagina,
            'total_paginas' => $total_paginas
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar clientes: ' . $e->getMessage()
    ]);
}
?>
