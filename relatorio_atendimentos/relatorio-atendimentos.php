<?php
require_once('../conecta_db.php');
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Definir uma sessão temporária para desenvolvimento
    $_SESSION['usuario_id'] = 1;

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

<div class="container" id="relatorio-container">
    <div class="card">
        <div class="header-card">
            <h1>Relatório de Atendimentos</h1>
            <div class="acoes-relatorio">
                <button class="btn-exportar" onclick="exportarPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn-exportar" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
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
                <div class="grafico">
                    <canvas id="grafico-consultas"></canvas>
                </div>
            </div>
            
            <div class="grafico-card">
                <h3>Serviços Mais Populares</h3>
                <div class="grafico">
                    <canvas id="grafico-servicos"></canvas>
                </div>
            </div>
            
            <div class="grafico-card">
                <h3>Faturamento por Serviço</h3>
                <div class="grafico">
                    <canvas id="grafico-faturamento"></canvas>
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
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background-color 0.3s;
    }
    
    .btn-exportar:hover {
        background-color: #0d4371;
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
        transition: background-color 0.3s;
    }
    
    .btn-filtrar:hover {
        background-color: #0d4371;
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
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .card-resumo:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .grafico-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        padding: 10px;
        box-shadow: inset 0 0 5px rgba(0,0,0,0.05);
    }

    .grafico canvas {
        width: 100% !important;
        height: 100% !important;
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

        .grafico {
            height: 300px;
        }
        
        .tabela-relatorio {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<!-- Bibliotecas para exportação -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    // Funções para exportação
    function exportarPDF() {
        // Mostrar mensagem de carregamento
        const loadingMsg = document.createElement('div');
        loadingMsg.style.position = 'fixed';
        loadingMsg.style.top = '50%';
        loadingMsg.style.left = '50%';
        loadingMsg.style.transform = 'translate(-50%, -50%)';
        loadingMsg.style.padding = '20px';
        loadingMsg.style.background = 'rgba(0,0,0,0.7)';
        loadingMsg.style.color = 'white';
        loadingMsg.style.borderRadius = '5px';
        loadingMsg.style.zIndex = '9999';
        loadingMsg.innerHTML = 'Gerando PDF, aguarde...';
        document.body.appendChild(loadingMsg);
        
        // Usar window.jsPDF que é definido pelo script importado
        const { jsPDF } = window.jspdf;
        
        // Criar um novo documento PDF
        const doc = new jsPDF('p', 'mm', 'a4');
        const container = document.getElementById('relatorio-container');
        
        // Adicionar título
        doc.setFontSize(18);
        doc.setTextColor(11, 53, 86);
        doc.text('Relatório de Atendimentos', 105, 15, { align: 'center' });
        
        // Adicionar data do relatório
        doc.setFontSize(10);
        doc.setTextColor(100, 100, 100);
        doc.text(`Período: ${document.getElementById('data_inicio').value} a ${document.getElementById('data_fim').value}`, 105, 22, { align: 'center' });
        
        // Capturar os cards de resumo
        const resumoCards = document.querySelectorAll('.card-resumo');
        let yPos = 30;
        
        doc.setFontSize(14);
        doc.setTextColor(11, 53, 86);
        doc.text('Resumo', 20, yPos);
        yPos += 10;
        
        // Adicionar dados de resumo
        doc.setFontSize(10);
        doc.setTextColor(0, 0, 0);
        resumoCards.forEach((card, index) => {
            const titulo = card.querySelector('h3').textContent;
            const valor = card.querySelector('.resumo-valor').textContent;
            doc.text(`${titulo}: ${valor}`, 20, yPos);
            yPos += 7;
        });
        
        yPos += 10;
        
        // Adicionar tabela de detalhamento
        doc.setFontSize(14);
        doc.setTextColor(11, 53, 86);
        doc.text('Detalhamento de Atendimentos', 20, yPos);
        yPos += 10;
        
        // Capturar dados da tabela
        const tabela = document.querySelector('.tabela-relatorio');
        const cabecalhos = Array.from(tabela.querySelectorAll('th')).map(th => th.textContent);
        const linhas = Array.from(tabela.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td')).map(td => td.textContent)
        );
        
        // Desenhar tabela
        doc.setFontSize(8);
        doc.setTextColor(0, 0, 0);
        
        // Cabeçalhos
        const colWidths = [25, 35, 25, 35, 20]; // Larguras das colunas
        let xPos = 20;
        
        // Fundo dos cabeçalhos
        doc.setFillColor(242, 242, 242);
        doc.rect(xPos, yPos - 5, colWidths.reduce((a, b) => a + b, 0), 7, 'F');
        
        // Texto dos cabeçalhos
        cabecalhos.forEach((header, i) => {
            doc.setFont(undefined, 'bold');
            doc.text(header, xPos + 2, yPos);
            xPos += colWidths[i];
        });
        
        yPos += 7;
        
        // Linhas da tabela
        linhas.forEach((linha, rowIndex) => {
            xPos = 20;
            
            // Alternar cores de fundo das linhas
            if (rowIndex % 2 === 1) {
                doc.setFillColor(249, 249, 249);
                doc.rect(xPos, yPos - 5, colWidths.reduce((a, b) => a + b, 0), 7, 'F');
            }
            
            linha.forEach((celula, i) => {
                doc.setFont(undefined, 'normal');
                doc.text(celula, xPos + 2, yPos);
                xPos += colWidths[i];
            });
            
            yPos += 7;
            
            // Verificar se precisa de nova página
            if (yPos > 270) {
                doc.addPage();
                yPos = 20;
            }
        });
        
        // Adicionar gráficos como imagens
        setTimeout(() => {
            // Capturar os gráficos como imagens
            const graficos = document.querySelectorAll('.grafico canvas');
            let graficoPromises = Array.from(graficos).map(grafico => 
                html2canvas(grafico, {
                    scale: 2,
                    backgroundColor: null
                })
            );
            
            Promise.all(graficoPromises).then(canvases => {
                // Adicionar nova página para os gráficos
                doc.addPage();
                
                doc.setFontSize(18);
                doc.setTextColor(11, 53, 86);
                doc.text('Gráficos', 105, 15, { align: 'center' });
                
                let yPosGraficos = 25;
                
                // Adicionar cada gráfico
                canvases.forEach((canvas, index) => {
                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = 170;
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    
                    // Verificar se precisa de nova página
                    if (yPosGraficos + imgHeight > 270) {
                        doc.addPage();
                        yPosGraficos = 20;
                    }
                    
                    // Adicionar título do gráfico
                    const tituloGrafico = document.querySelectorAll('.grafico-card h3')[index].textContent;
                    doc.setFontSize(12);
                    doc.setTextColor(11, 53, 86);
                    doc.text(tituloGrafico, 20, yPosGraficos);
                    
                    // Adicionar o gráfico
                    doc.addImage(imgData, 'PNG', 20, yPosGraficos + 5, imgWidth, imgHeight);
                    
                    yPosGraficos += imgHeight + 20;
                });
                
                // Salvar o PDF
                doc.save('relatorio-atendimentos.pdf');
                
                // Remover mensagem de carregamento
                document.body.removeChild(loadingMsg);
            });
        }, 500);
    }
    
    function exportarExcel() {
        // Mostrar mensagem de carregamento
        const loadingMsg = document.createElement('div');
        loadingMsg.style.position = 'fixed';
        loadingMsg.style.top = '50%';
        loadingMsg.style.left = '50%';
        loadingMsg.style.transform = 'translate(-50%, -50%)';
        loadingMsg.style.padding = '20px';
        loadingMsg.style.background = 'rgba(0,0,0,0.7)';
        loadingMsg.style.color = 'white';
        loadingMsg.style.borderRadius = '5px';
        loadingMsg.style.zIndex = '9999';
        loadingMsg.innerHTML = 'Gerando Excel, aguarde...';
        document.body.appendChild(loadingMsg);
        
        // Criar um novo workbook
        const wb = XLSX.utils.book_new();
        
        // Adicionar uma planilha de resumo
        const resumoData = [
            ['Relatório de Atendimentos'],
            [`Período: ${document.getElementById('data_inicio').value} a ${document.getElementById('data_fim').value}`],
            [],
            ['Resumo'],
        ];
        
        // Adicionar dados de resumo
        const resumoCards = document.querySelectorAll('.card-resumo');
        resumoCards.forEach(card => {
            const titulo = card.querySelector('h3').textContent;
            const valor = card.querySelector('.resumo-valor').textContent;
            resumoData.push([titulo, valor]);
        });
        
        // Adicionar planilha de resumo
        const resumoWs = XLSX.utils.aoa_to_sheet(resumoData);
        XLSX.utils.book_append_sheet(wb, resumoWs, 'Resumo');
        
        // Adicionar uma planilha de detalhamento
        const tabela = document.querySelector('.tabela-relatorio');
        const cabecalhos = Array.from(tabela.querySelectorAll('th')).map(th => th.textContent);
        const linhas = Array.from(tabela.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td')).map(td => td.textContent)
        );
        
        const detalhamentoData = [cabecalhos];
        linhas.forEach(linha => detalhamentoData.push(linha));
        
        // Adicionar planilha de detalhamento
        const detalhamentoWs = XLSX.utils.aoa_to_sheet(detalhamentoData);
        XLSX.utils.book_append_sheet(wb, detalhamentoWs, 'Detalhamento');
        
        // Adicionar uma planilha para cada tipo de gráfico
        // Serviços Populares
        const servicosData = [
            ['Serviços Mais Populares'],
            [],
            ['Serviço', 'Quantidade', 'Percentual']
        ];
        
        // Dados simulados para serviços
        const servicosPopulares = [
            ['Consulta Veterinária', 45, '33%'],
            ['Vacinação', 28, '21%'],
            ['Banho e Tosa', 62, '46%']
        ];
        
        servicosPopulares.forEach(servico => servicosData.push(servico));
        
        // Adicionar planilha de serviços
        const servicosWs = XLSX.utils.aoa_to_sheet(servicosData);
        XLSX.utils.book_append_sheet(wb, servicosWs, 'Serviços Populares');
        
        // Faturamento por Serviço
        const faturamentoData = [
            ['Faturamento por Serviço'],
            [],
            ['Serviço', 'Valor']
        ];
        
        // Dados simulados para faturamento
        const faturamentoServicos = [
            ['Consulta Veterinária', 'R$ 6.750,00'],
            ['Vacinação', 'R$ 3.360,00'],
            ['Banho e Tosa', 'R$ 2.740,00']
        ];
        
        faturamentoServicos.forEach(faturamento => faturamentoData.push(faturamento));
        
        // Adicionar planilha de faturamento
        const faturamentoWs = XLSX.utils.aoa_to_sheet(faturamentoData);
        XLSX.utils.book_append_sheet(wb, faturamentoWs, 'Faturamento');
        
        // Salvar o arquivo Excel
        XLSX.writeFile(wb, 'relatorio-atendimentos.xlsx');
        
        // Remover mensagem de carregamento
        document.body.removeChild(loadingMsg);
    }
    
    // Renderização dos gráficos com Chart.js
    document.addEventListener('DOMContentLoaded', function() {
        // Gráfico de consultas por dia
        const ctxConsultas = document.getElementById('grafico-consultas').getContext('2d');
        const consultasChart = new Chart(ctxConsultas, {
            type: 'line',
            data: {
                labels: ['01/06', '02/06', '03/06', '04/06', '05/06', '06/06', '07/06'],
                datasets: [{
                    label: 'Consultas',
                    data: [3, 5, 2, 4, 6, 3, 2],
                    backgroundColor: 'rgba(11, 53, 86, 0.2)',
                    borderColor: 'rgba(11, 53, 86, 1)',
                    borderWidth: 2,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Consultas por Dia'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Gráfico de serviços mais populares (pizza)
        const ctxServicos = document.getElementById('grafico-servicos').getContext('2d');
        const servicosChart = new Chart(ctxServicos, {
            type: 'pie',
            data: {
                labels: ['Consulta Veterinária', 'Vacinação', 'Banho e Tosa'],
                datasets: [{
                    data: [45, 28, 62],
                    backgroundColor: [
                        'rgba(11, 53, 86, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderColor: [
                        'rgba(11, 53, 86, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Serviços Mais Populares'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Gráfico de faturamento por serviço (pizza)
        const ctxFaturamento = document.getElementById('grafico-faturamento').getContext('2d');
        const faturamentoChart = new Chart(ctxFaturamento, {
            type: 'pie',
            data: {
                labels: ['Consulta Veterinária', 'Vacinação', 'Banho e Tosa'],
                datasets: [{
                    data: [6750, 3360, 2740],
                    backgroundColor: [
                        'rgba(11, 53, 86, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderColor: [
                        'rgba(11, 53, 86, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Faturamento por Serviço'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: R$ ${value.toFixed(2).replace('.', ',')} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Adicionar um novo gráfico de pizza para distribuição por espécie
        const ctxEspecies = document.createElement('canvas');
        ctxEspecies.id = 'grafico-especies';
        
        const graficoCard = document.createElement('div');
        graficoCard.className = 'grafico-card';
        graficoCard.innerHTML = '<h3>Distribuição por Espécie</h3>';
        
        const graficoContainer = document.createElement('div');
        graficoContainer.className = 'grafico';
        graficoContainer.appendChild(ctxEspecies);
        
        graficoCard.appendChild(graficoContainer);
        document.querySelector('.graficos-container').appendChild(graficoCard);
        
        const especiesChart = new Chart(ctxEspecies, {
            type: 'pie',
            data: {
                labels: ['Cães', 'Gatos', 'Aves', 'Outros'],
                datasets: [{
                    data: [65, 25, 7, 3],
                    backgroundColor: [
                        'rgba(11, 53, 86, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderColor: [
                        'rgba(11, 53, 86, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Distribuição por Espécie'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>

</body>
</html>
