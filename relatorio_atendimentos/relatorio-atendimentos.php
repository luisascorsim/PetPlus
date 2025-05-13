<?php
require_once '../config/database.php';
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Definir uma sessão temporária para desenvolvimento
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Definir período padrão (último mês)
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Dados simulados para o relatório
$total_consultas = 45;
$total_vacinas = 28;
$total_banhos = 62;
$total_faturamento = 12850.00;

$consultas_por_dia = [
    ['data' => '2023-06-01', 'quantidade' => 3],
    ['data' => '2023-06-02', 'quantidade' => 5],
    ['data' => '2023-06-03', 'quantidade' => 2],
    ['data' => '2023-06-04', 'quantidade' => 4],
    ['data' => '2023-06-05', 'quantidade' => 6],
    ['data' => '2023-06-06', 'quantidade' => 3],
    ['data' => '2023-06-07', 'quantidade' => 2]
];

$servicos_populares = [
    ['servico' => 'Consulta Veterinária', 'quantidade' => 45, 'percentual' => 33],
    ['servico' => 'Vacinação', 'quantidade' => 28, 'percentual' => 21],
    ['servico' => 'Banho e Tosa', 'quantidade' => 62, 'percentual' => 46]
];

$faturamento_por_servico = [
    ['servico' => 'Consulta Veterinária', 'valor' => 6750.00],
    ['servico' => 'Vacinação', 'valor' => 3360.00],
    ['servico' => 'Banho e Tosa', 'valor' => 2740.00]
];
?>

<div class="container">
    <div class="card">
        <div class="header-card">
            <h1>Relatório de Atendimentos</h1>
            <div class="acoes-relatorio">
                <button class="btn-exportar" onclick="exportarPDF()">Exportar PDF</button>
                <button class="btn-exportar" onclick="exportarExcel()">Exportar Excel</button>
            </div>
        </div>
        
        <div class="filtro-container">
            <form action="" method="GET" class="form-filtro">
                <div class="form-group">
                    <label for="data_inicio">Data Inicial:</label>
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Final:</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                </div>
                <div class="form-group">
                    <label for="tipo_servico">Tipo de Serviço:</label>
                    <select id="tipo_servico" name="tipo_servico">
                        <option value="">Todos</option>
                        <option value="consulta">Consultas</option>
                        <option value="vacina">Vacinas</option>
                        <option value="banho">Banho e Tosa</option>
                    </select>
                </div>
                <button type="submit" class="btn-filtrar">Filtrar</button>
            </form>
        </div>
        
        <div class="resumo-container">
            <div class="card-resumo">
                <div class="resumo-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="resumo-info">
                    <h3>Total de Consultas</h3>
                    <p class="resumo-valor"><?php echo $total_consultas; ?></p>
                </div>
            </div>
            
            <div class="card-resumo">
                <div class="resumo-icon">
                    <i class="fas fa-syringe"></i>
                </div>
                <div class="resumo-info">
                    <h3>Total de Vacinas</h3>
                    <p class="resumo-valor"><?php echo $total_vacinas; ?></p>
                </div>
            </div>
            
            <div class="card-resumo">
                <div class="resumo-icon">
                    <i class="fas fa-shower"></i>
                </div>
                <div class="resumo-info">
                    <h3>Total de Banhos</h3>
                    <p class="resumo-valor"><?php echo $total_banhos; ?></p>
                </div>
            </div>
            
            <div class="card-resumo">
                <div class="resumo-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="resumo-info">
                    <h3>Faturamento</h3>
                    <p class="resumo-valor">R$ <?php echo number_format($total_faturamento, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="graficos-container">
            <div class="grafico-card">
                <h3>Consultas por Dia</h3>
                <div class="grafico" id="grafico-consultas">
                    <!-- Gráfico será renderizado via JavaScript -->
                    <div class="grafico-placeholder">
                        <p>Gráfico de consultas por dia será exibido aqui</p>
                    </div>
                </div>
            </div>
            
            <div class="grafico-card">
                <h3>Serviços Mais Populares</h3>
                <div class="grafico" id="grafico-servicos">
                    <!-- Gráfico será renderizado via JavaScript -->
                    <div class="grafico-placeholder">
                        <p>Gráfico de serviços populares será exibido aqui</p>
                    </div>
                </div>
            </div>
            
            <div class="grafico-card">
                <h3>Faturamento por Serviço</h3>
                <div class="grafico" id="grafico-faturamento">
                    <!-- Gráfico será renderizado via JavaScript -->
                    <div class="grafico-placeholder">
                        <p>Gráfico de faturamento por serviço será exibido aqui</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="tabela-container">
            <h3>Detalhamento de Atendimentos</h3>
            <table class="tabela-relatorio">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Pet</th>
                        <th>Serviço</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>01/06/2023</td>
                        <td>João Silva</td>
                        <td>Rex</td>
                        <td>Consulta Veterinária</td>
                        <td>R$ 150,00</td>
                    </tr>
                    <tr>
                        <td>01/06/2023</td>
                        <td>Maria Oliveira</td>
                        <td>Mel</td>
                        <td>Vacinação</td>
                        <td>R$ 120,00</td>
                    </tr>
                    <tr>
                        <td>02/06/2023</td>
                        <td>Carlos Santos</td>
                        <td>Thor</td>
                        <td>Banho e Tosa</td>
                        <td>R$ 80,00</td>
                    </tr>
                    <tr>
                        <td>02/06/2023</td>
                        <td>João Silva</td>
                        <td>Luna</td>
                        <td>Consulta Veterinária</td>
                        <td>R$ 150,00</td>
                    </tr>
                    <tr>
                        <td>03/06/2023</td>
                        <td>Maria Oliveira</td>
                        <td>Mel</td>
                        <td>Banho e Tosa</td>
                        <td>R$ 80,00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .container {
        margin-left: 220px;
        padding: 80px 30px 30px;
        transition: margin-left 0.3s;
    }
    
    .card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .header-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    h1 {
        color: #0b3556;
        margin: 0;
    }
    
    .acoes-relatorio {
        display: flex;
        gap: 10px;
    }
    
    .btn-exportar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        cursor: pointer;
    }
    
    .filtro-container {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }
    
    .form-filtro {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    
    .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-filtrar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        cursor: pointer;
        height: 36px;
    }
    
    .resumo-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .card-resumo {
        display: flex;
        align-items: center;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .resumo-icon {
        width: 50px;
        height: 50px;
        background-color: #0b3556;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 20px;
    }
    
    .resumo-info h3 {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: #666;
    }
    
    .resumo-valor {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        color: #0b3556;
    }
    
    .graficos-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .grafico-card {
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .grafico-card h3 {
        margin: 0 0 15px 0;
        color: #0b3556;
        font-size: 16px;
    }
    
    .grafico {
        height: 250px;
        background-color: #fff;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .grafico-placeholder {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        text-align: center;
        padding: 20px;
    }
    
    .tabela-container {
        margin-top: 20px;
    }
    
    .tabela-container h3 {
        margin: 0 0 15px 0;
        color: #0b3556;
        font-size: 16px;
    }
    
    .tabela-relatorio {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tabela-relatorio th,
    .tabela-relatorio td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    
    .tabela-relatorio th {
        background-color: #f2f2f2;
        font-weight: 600;
        color: #0b3556;
    }
    
    .tabela-relatorio tr:hover {
        background-color: #f9f9f9;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .container {
            margin-left: 60px;
            padding: 70px 15px 15px;
        }
        
        .resumo-container,
        .graficos-container {
            grid-template-columns: 1fr;
        }
        
        .tabela-relatorio {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<script>
    // Funções para exportação
    function exportarPDF() {
        alert('Exportação para PDF será implementada aqui');
    }
    
    function exportarExcel() {
        alert('Exportação para Excel será implementada aqui');
    }
    
    // Simulação de renderização de gráficos
    document.addEventListener('DOMContentLoaded', function() {
        // Aqui você implementaria a renderização real dos gráficos
        // usando bibliotecas como Chart.js, ApexCharts, etc.
        
        // Exemplo de simulação visual
        const graficos = document.querySelectorAll('.grafico-placeholder');
        graficos.forEach(grafico => {
            grafico.innerHTML = '<div style="width: 100%; height: 100%; background: linear-gradient(45deg, #0b3556, #0d4371); display: flex; align-items: center; justify-content: center; color: white;">Gráfico simulado</div>';
        });
    });
</script>

</body>
</html>
