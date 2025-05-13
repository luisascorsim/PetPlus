<?php
header('Content-Type: application/json');
require_once '../conexao.php';

try {
    // Parâmetros de paginação
    $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;
    $offset = ($pagina - 1) * $limite;
    
    // Construir a consulta SQL base
    $sql = "SELECT c.id, c.data_hora, c.motivo, c.status, c.observacoes,
                   p.id as pet_id, p.nome as pet_nome,
                   cl.id as cliente_id, cl.nome as cliente_nome,
                   v.id as veterinario_id, v.nome as veterinario_nome
            FROM consultas c
            JOIN pets p ON c.pet_id = p.id
            JOIN clientes cl ON p.cliente_id = cl.id
            JOIN veterinarios v ON c.veterinario_id = v.id
            WHERE 1=1";
    
    // Adicionar filtros se fornecidos
    $params = [];
    
    // Filtro por data
    if (isset($_GET['data']) && !empty($_GET['data'])) {
        $data = $_GET['data'];
        $sql .= " AND DATE(c.data_hora) = :data";
        $params[':data'] = $data;
    }
    
    // Filtro por intervalo de datas
    if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
        $dataInicio = $_GET['data_inicio'];
        $sql .= " AND DATE(c.data_hora) >= :data_inicio";
        $params[':data_inicio'] = $dataInicio;
    }
    
    if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
        $dataFim = $_GET['data_fim'];
        $sql .= " AND DATE(c.data_hora) <= :data_fim";
        $params[':data_fim'] = $dataFim;
    }
    
    // Filtro por status
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $status = $_GET['status'];
        $sql .= " AND c.status = :status";
        $params[':status'] = $status;
    }
    
    // Filtro por veterinário
    if (isset($_GET['veterinario_id']) && !empty($_GET['veterinario_id'])) {
        $veterinarioId = intval($_GET['veterinario_id']);
        $sql .= " AND c.veterinario_id = :veterinario_id";
        $params[':veterinario_id'] = $veterinarioId;
    }
    
    // Filtro por cliente
    if (isset($_GET['cliente_id']) && !empty($_GET['cliente_id'])) {
        $clienteId = intval($_GET['cliente_id']);
        $sql .= " AND cl.id = :cliente_id";
        $params[':cliente_id'] = $clienteId;
    }
    
    // Filtro por pet
    if (isset($_GET['pet_id']) && !empty($_GET['pet_id'])) {
        $petId = intval($_GET['pet_id']);
        $sql .= " AND p.id = :pet_id";
        $params[':pet_id'] = $petId;
    }
    
    // Ordenação
    $sql .= " ORDER BY c.data_hora DESC";
    
    // Consulta para contar o total de registros
    $sqlCount = str_replace("SELECT c.id, c.data_hora, c.motivo, c.status, c.observacoes,
                   p.id as pet_id, p.nome as pet_nome,
                   cl.id as cliente_id, cl.nome as cliente_nome,
                   v.id as veterinario_id, v.nome as veterinario_nome", "SELECT COUNT(*) as total", $sql);
    
    $stmtCount = $pdo->prepare($sqlCount);
    foreach ($params as $key => $value) {
        $stmtCount->bindValue($key, $value);
    }
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Adicionar LIMIT e OFFSET para paginação
    $sql .= " LIMIT :limite OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular informações de paginação
    $totalPaginas = ceil($totalRegistros / $limite);
    
    // Formatar as datas para exibição
    foreach ($consultas as &$consulta) {
        if (isset($consulta['data_hora'])) {
            $dataHora = new DateTime($consulta['data_hora']);
            $consulta['data_formatada'] = $dataHora->format('d/m/Y');
            $consulta['hora_formatada'] = $dataHora->format('H:i');
        }
    }
    
    // Retornar os resultados
    echo json_encode([
        'success' => true,
        'data' => [
            'consultas' => $consultas,
            'paginacao' => [
                'pagina_atual' => $pagina,
                'total_paginas' => $totalPaginas,
                'total_registros' => $totalRegistros,
                'registros_por_pagina' => $limite
            ]
        ]
    ]);
    
} catch (PDOException $e) {
    // Em caso de erro, retornar mensagem de erro
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao listar consultas: ' . $e->getMessage()
    ]);
}
