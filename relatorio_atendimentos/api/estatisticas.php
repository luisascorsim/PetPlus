<?php
// Este arquivo seria usado para fornecer dados para os gráficos
require_once '../../config/database.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

// Parâmetros de filtro
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$tipo_servico = isset($_GET['tipo_servico']) ? $_GET['tipo_servico'] : '';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta para obter consultas por dia
    $sql_consultas_dia = "SELECT DATE(data_consulta) as data, COUNT(*) as quantidade 
                          FROM consultas 
                          WHERE data_consulta BETWEEN :data_inicio AND :data_fim
                          GROUP BY DATE(data_consulta)
                          ORDER BY data_consulta";
    
    // Consulta para obter serviços mais populares
    $sql_servicos = "SELECT tipo_servico, COUNT(*) as quantidade 
                     FROM consultas 
                     WHERE data_consulta BETWEEN :data_inicio AND :data_fim
                     GROUP BY tipo_servico
                     ORDER BY quantidade DESC";
    
    // Consulta para obter faturamento por serviço
    $sql_faturamento = "SELECT tipo_servico, SUM(valor) as valor_total 
                        FROM consultas 
                        WHERE data_consulta BETWEEN :data_inicio AND :data_fim
                        GROUP BY tipo_servico
                        ORDER BY valor_total DESC";
    
    // Consulta para obter distribuição por espécie
    $sql_especies = "SELECT p.especie, COUNT(*) as quantidade 
                     FROM consultas c
                     JOIN pets p ON c.pet_id = p.id
                     WHERE c.data_consulta BETWEEN :data_inicio AND :data_fim
                     GROUP BY p.especie
                     ORDER BY quantidade DESC";
    
    // Executar consultas e obter resultados
    // Aqui você executaria as consultas e formataria os resultados
    
    // Por enquanto, retornamos dados simulados
    $dados = [
        'consultas_por_dia' => [
            ['data' => '2023-06-01', 'quantidade' => 3],
            ['data' => '2023-06-02', 'quantidade' => 5],
            ['data' => '2023-06-03', 'quantidade' => 2],
            ['data' => '2023-06-04', 'quantidade' => 4],
            ['data' => '2023-06-05', 'quantidade' => 6],
            ['data' => '2023-06-06', 'quantidade' => 3],
            ['data' => '2023-06-07', 'quantidade' => 2]
        ],
        'servicos_populares' => [
            ['servico' => 'Consulta Veterinária', 'quantidade' => 45],
            ['servico' => 'Vacinação', 'quantidade' => 28],
            ['servico' => 'Banho e Tosa', 'quantidade' => 62]
        ],
        'faturamento_por_servico' => [
            ['servico' => 'Consulta Veterinária', 'valor' => 6750.00],
            ['servico' => 'Vacinação', 'valor' => 3360.00],
            ['servico' => 'Banho e Tosa', 'valor' => 2740.00]
        ],
        'distribuicao_especies' => [
            ['especie' => 'Cães', 'quantidade' => 65],
            ['especie' => 'Gatos', 'quantidade' => 25],
            ['especie' => 'Aves', 'quantidade' => 7],
            ['especie' => 'Outros', 'quantidade' => 3]
        ]
    ];
    
    // Retornar dados em formato JSON
    header('Content-Type: application/json');
    echo json_encode($dados);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao obter estatísticas: ' . $e->getMessage()]);
}
