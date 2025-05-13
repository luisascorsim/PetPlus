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

// Parâmetros de paginação e filtro
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

try {
    // Construir consulta SQL com filtro
    $whereClause = '';
    $params = [];
    
    if (!empty($filter)) {
        $whereClause = "WHERE p.tipo = :filter";
        $params[':filter'] = $filter;
    }
    
    // Contar total de registros para paginação
    $countQuery = "SELECT COUNT(*) as total FROM pets p $whereClause";
    $stmt = $pdo->prepare($countQuery);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    // Consultar pets com paginação
    $query = "
        SELECT p.id, p.nome, p.tipo, p.raca, p.idade, p.foto, c.nome as proprietario, p.cliente_id
        FROM pets p
        LEFT JOIN clientes c ON p.cliente_id = c.id
        $whereClause
        ORDER BY p.nome
        LIMIT :limit OFFSET :offset
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pets' => $pets,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar pets: ' . $e->getMessage()]);
}
?>
