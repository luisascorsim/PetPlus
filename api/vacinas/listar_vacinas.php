<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once('../conecta_db.php');

// Parâmetros de paginação e filtro
$pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$itens_por_pagina = 10;
$offset = ($pagina - 1) * $itens_por_pagina;

$pet_id = isset($_GET['pet_id']) ? intval($_GET['pet_id']) : 0;
$tipo_vacina = isset($_GET['tipo_vacina']) ? $_GET['tipo_vacina'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';

try {
    // Verificar se a tabela existe
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'vacinas'");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Tabela não existe, retornar dados simulados
        $vacinas = [
            [
                'id' => 1,
                'pet_id' => 1,
                'pet_nome' => 'Rex',
                'tipo_vacina' => 'v8',
                'data_aplicacao' => '2023-01-15',
                'proxima_dose' => '2024-01-15',
                'veterinario_id' => 1,
                'veterinario_nome' => 'Dr. Carlos Silva'
            ],
            [
                'id' => 2,
                'pet_id' => 2,
                'pet_nome' => 'Luna',
                'tipo_vacina' => 'antirrabica',
                'data_aplicacao' => '2023-03-20',
                'proxima_dose' => '2024-03-20',
                'veterinario_id' => 2,
                'veterinario_nome' => 'Dra. Ana Oliveira'
            ],
            [
                'id' => 3,
                'pet_id' => 3,
                'pet_nome' => 'Max',
                'tipo_vacina' => 'v10',
                'data_aplicacao' => '2023-05-10',
                'proxima_dose' => '2024-05-10',
                'veterinario_id' => 1,
                'veterinario_nome' => 'Dr. Carlos Silva'
            ],
            [
                'id' => 4,
                'pet_id' => 4,
                'pet_nome' => 'Bella',
                'tipo_vacina' => 'giardíase',
                'data_aplicacao' => '2023-06-05',
                'proxima_dose' => '2024-06-05',
                'veterinario_id' => 3,
                'veterinario_nome' => 'Dr. Roberto Mendes'
            ],
            [
                'id' => 5,
                'pet_id' => 1,
                'pet_nome' => 'Rex',
                'tipo_vacina' => 'antirrabica',
                'data_aplicacao' => '2023-07-20',
                'proxima_dose' => '2024-07-20',
                'veterinario_id' => 2,
                'veterinario_nome' => 'Dra. Ana Oliveira'
            ]
        ];
        
        // Aplicar filtros nos dados simulados
        $vacinas_filtradas = [];
        foreach ($vacinas as $vacina) {
            $incluir = true;
            
            if ($pet_id > 0 && $vacina['pet_id'] != $pet_id) {
                $incluir = false;
            }
            
            if (!empty($tipo_vacina) && $vacina['tipo_vacina'] != $tipo_vacina) {
                $incluir = false;
            }
            
            if (!empty($data_inicio) && strtotime($vacina['data_aplicacao']) < strtotime($data_inicio)) {
                $incluir = false;
            }
            
            if (!empty($data_fim) && strtotime($vacina['data_aplicacao']) > strtotime($data_fim)) {
                $incluir = false;
            }
            
            if ($incluir) {
                $vacinas_filtradas[] = $vacina;
            }
        }
        
        // Paginação manual
        $total_registros = count($vacinas_filtradas);
        $total_paginas = ceil($total_registros / $itens_por_pagina);
        
        // Obter apenas os registros da página atual
        $vacinas_paginadas = array_slice($vacinas_filtradas, $offset, $itens_por_pagina);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'vacinas' => $vacinas_paginadas,
                'paginacao' => [
                    'pagina_atual' => $pagina,
                    'total_paginas' => $total_paginas,
                    'total_registros' => $total_registros,
                    'registros_por_pagina' => $itens_por_pagina
                ]
            ]
        ]);
    } else {
        // A tabela existe, buscar dados reais
        // Construir a consulta SQL base
        $sql = "SELECT v.id, v.pet_id, p.nome as pet_nome, v.tipo_vacina, 
                       v.data_aplicacao, v.proxima_dose, v.veterinario_id, 
                       vet.nome as veterinario_nome
                FROM vacinas v
                JOIN pets p ON v.pet_id = p.id
                JOIN veterinarios vet ON v.veterinario_id = vet.id
                WHERE 1=1";
        
        $params = [];
        
        // Adicionar filtros
        if ($pet_id > 0) {
            $sql .= " AND v.pet_id = :pet_id";
            $params[':pet_id'] = $pet_id;
        }
        
        if (!empty($tipo_vacina)) {
            $sql .= " AND v.tipo_vacina = :tipo_vacina";
            $params[':tipo_vacina'] = $tipo_vacina;
        }
        
        if (!empty($data_inicio)) {
            $sql .= " AND v.data_aplicacao >= :data_inicio";
            $params[':data_inicio'] = $data_inicio;
        }
        
        if (!empty($data_fim)) {
            $sql .= " AND v.data_aplicacao <= :data_fim";
            $params[':data_fim'] = $data_fim;
        }
        
        // Consulta para contar o total de registros
        $sqlCount = str_replace("SELECT v.id, v.pet_id, p.nome as pet_nome, v.tipo_vacina, 
                       v.data_aplicacao, v.proxima_dose, v.veterinario_id, 
                       vet.nome as veterinario_nome", "SELECT COUNT(*) as total", $sql);
        
        $stmtCount = $pdo->prepare($sqlCount);
        foreach ($params as $key => $value) {
            $stmtCount->bindValue($key, $value);
        }
        $stmtCount->execute();
        $totalRegistros = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Ordenação e paginação
        $sql .= " ORDER BY v.data_aplicacao DESC LIMIT :limite OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limite', $itens_por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $vacinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular informações de paginação
        $totalPaginas = ceil($totalRegistros / $itens_por_pagina);
        
        // Formatar as datas para exibição
        foreach ($vacinas as &$vacina) {
            if (isset($vacina['data_aplicacao'])) {
                $dataAplicacao = new DateTime($vacina['data_aplicacao']);
                $vacina['data_aplicacao_formatada'] = $dataAplicacao->format('d/m/Y');
            }
            
            if (isset($vacina['proxima_dose'])) {
                $proximaDose = new DateTime($vacina['proxima_dose']);
                $vacina['proxima_dose_formatada'] = $proximaDose->format('d/m/Y');
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'vacinas' => $vacinas,
                'paginacao' => [
                    'pagina_atual' => $pagina,
                    'total_paginas' => $totalPaginas,
                    'total_registros' => $totalRegistros,
                    'registros_por_pagina' => $itens_por_pagina
                ]
            ]
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao listar vacinas: ' . $e->getMessage()
    ]);
}
