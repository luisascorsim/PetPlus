<?php
// É crucial que o arquivo 'conecta_db.php' esteja funcionando corretamente
// e estabelecendo a conexão na variável $conn.
require_once('../conecta_db.php');
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1; // Exemplo de ID de usuário para desenvolvimento
}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

$conn = conecta_db();

if (!$conn) {
    die("Falha na conexão com o banco de dados. Verifique o arquivo conecta_db.php e as credenciais.");
}

// Definir período padrão (último mês)
$data_inicio = isset($_GET['data_inicio']) && !empty($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) && !empty($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
$tipo_servico = isset($_GET['tipo_servico']) ? $_GET['tipo_servico'] : '';

// Inicializar variáveis
$total_consultas = 0;
$total_vacinas_aplicadas = 0; // Nome alterado para clareza
$total_banhos = 0;
$total_faturamento = 0;
$consultas_por_dia = [];
$servicos_populares = [];
$faturamento_por_dia_faturas = []; // Nome alterado para clareza
$distribuicao_especies = [];

try {
    // Total de consultas
    $sql_consultas = "SELECT COUNT(*) as total FROM consultas WHERE DATE(data_consulta) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_consultas);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_total_consultas = $stmt->get_result()->fetch_assoc();
    $total_consultas = $result_total_consultas ? $result_total_consultas['total'] : 0;

    // Total de vacinas aplicadas (da tabela Vacinas)
    $sql_vacinas_aplicadas = "SELECT COUNT(*) as total FROM Vacinas WHERE DATE(data_aplicacao) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_vacinas_aplicadas);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_total_vacinas = $stmt->get_result()->fetch_assoc();
    $total_vacinas_aplicadas = $result_total_vacinas ? $result_total_vacinas['total'] : 0;

    // Total de banhos (da tabela consultas, baseado no motivo)
    $sql_banhos = "SELECT COUNT(*) as total FROM consultas WHERE (motivo LIKE '%banho%' OR motivo LIKE '%tosa%') AND DATE(data_consulta) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_banhos);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_total_banhos = $stmt->get_result()->fetch_assoc();
    $total_banhos = $result_total_banhos ? $result_total_banhos['total'] : 0;

    // Total de faturamento (da tabela Faturas)
    $sql_faturamento_geral = "SELECT COALESCE(SUM(valor), 0) as total FROM Faturas WHERE DATE(data_fatura) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_faturamento_geral);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_faturamento_geral = $stmt->get_result()->fetch_assoc();
    $total_faturamento = $result_faturamento_geral ? $result_faturamento_geral['total'] : 0;

    // Consultas por dia
    $sql_consultas_dia = "SELECT DATE(data_consulta) as data, COUNT(*) as quantidade
                          FROM consultas
                          WHERE DATE(data_consulta) BETWEEN ? AND ?
                          GROUP BY DATE(data_consulta)
                          ORDER BY data";
    $stmt = $conn->prepare($sql_consultas_dia);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_consultas_dia = $stmt->get_result();
    while ($row = $result_consultas_dia->fetch_assoc()) {
        $consultas_por_dia[] = $row;
    }

    // Serviços mais populares (baseado no motivo da consulta)
    $sql_servicos = "SELECT
                        CASE
                            WHEN motivo LIKE '%vacina%' THEN 'Vacinação (Consulta)'
                            WHEN motivo LIKE '%banho%' OR motivo LIKE '%tosa%' THEN 'Banho e Tosa (Consulta)'
                            WHEN motivo LIKE '%consulta%' OR motivo LIKE '%rotina%' OR motivo LIKE '%checkup%' THEN 'Consulta Veterinária'
                            ELSE 'Outros (Consulta)'
                        END as servico,
                        COUNT(*) as quantidade
                     FROM consultas
                     WHERE DATE(data_consulta) BETWEEN ? AND ?
                     GROUP BY servico
                     ORDER BY quantidade DESC";
    $stmt = $conn->prepare($sql_servicos);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_servicos = $stmt->get_result();
    $total_servicos_calc = 0;
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos_populares[] = $row;
        $total_servicos_calc += $row['quantidade'];
    }

    foreach ($servicos_populares as &$servico_item) {
        $servico_item['percentual'] = $total_servicos_calc > 0 ? round(($servico_item['quantidade'] / $total_servicos_calc) * 100) : 0;
    }
    unset($servico_item);

    // Faturamento por DATA (da tabela Faturas)
    $sql_faturamento_por_data = "SELECT
                                    DATE(f.data_fatura) as data_faturamento,
                                    COALESCE(SUM(f.valor), 0) as valor_total_dia
                                 FROM Faturas f
                                 WHERE DATE(f.data_fatura) BETWEEN ? AND ?
                                 GROUP BY DATE(f.data_fatura)
                                 HAVING SUM(f.valor) > 0
                                 ORDER BY data_faturamento ASC";
    $stmt = $conn->prepare($sql_faturamento_por_data);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_faturamento_por_data = $stmt->get_result();
    while ($row = $result_faturamento_por_data->fetch_assoc()) {
        $faturamento_por_dia_faturas[] = $row;
    }

    // Distribuição por espécie
    $sql_especies = "SELECT p.especie, COUNT(*) as quantidade
                     FROM consultas c
                     JOIN Pets p ON c.pet_id = p.id_pet
                     WHERE DATE(c.data_consulta) BETWEEN ? AND ?
                     GROUP BY p.especie
                     ORDER BY quantidade DESC";
    $stmt = $conn->prepare($sql_especies);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result_especies = $stmt->get_result();
    while ($row = $result_especies->fetch_assoc()) {
        $distribuicao_especies[] = $row;
    }

} catch (Exception $e) {
    error_log("Erro ao buscar dados para relatório: " . $e->getMessage());
}

if ($conn) {
    $conn->close();
}
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
                    <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>">
                </div>
                <div class="form-group">
                    <label for="data_fim">Data Final:</label>
                    <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>">
                </div>
                <div class="form-group">
                    <label for="tipo_servico">Tipo de Serviço (Filtro de Consultas):</label>
                    <select id="tipo_servico" name="tipo_servico">
                        <option value="">Todos</option>
                        <option value="consulta" <?php echo ($tipo_servico == 'consulta' ? 'selected' : ''); ?>>Consultas</option>
                        <option value="vacina" <?php echo ($tipo_servico == 'vacina' ? 'selected' : ''); ?>>Vacinas (Consultas)</option>
                        <option value="banho" <?php echo ($tipo_servico == 'banho' ? 'selected' : ''); ?>>Banho e Tosa (Consultas)</option>
                    </select>
                </div>
                <button type="submit" class="btn-filtrar">Filtrar</button>
            </form>
        </div>

        <div class="resumo-container">
            <div class="card-resumo">
                <div class="resumo-icon"><i class="fas fa-stethoscope"></i></div>
                <div class="resumo-info">
                    <h3>Total de Consultas</h3>
                    <p class="resumo-valor"><?php echo $total_consultas; ?></p>
                </div>
            </div>
            <div class="card-resumo">
                <div class="resumo-icon"><i class="fas fa-syringe"></i></div>
                <div class="resumo-info">
                    <h3>Total de Vacinas Aplicadas</h3>
                    <p class="resumo-valor"><?php echo $total_vacinas_aplicadas; ?></p>
                </div>
            </div>
            <div class="card-resumo">
                <div class="resumo-icon"><i class="fas fa-shower"></i></div>
                <div class="resumo-info">
                    <h3>Total de Banhos/Tosas (Consultas)</h3>
                    <p class="resumo-valor"><?php echo $total_banhos; ?></p>
                </div>
            </div>
            <div class="card-resumo">
                <div class="resumo-icon"><i class="fas fa-dollar-sign"></i></div>
                <div class="resumo-info">
                    <h3>Faturamento Total</h3>
                    <p class="resumo-valor">R$ <?php echo number_format($total_faturamento, 2, ',', '.'); ?></p>
                </div>
            </div>
        </div>

        <div class="graficos-container">
            <div class="grafico-card">
                <h3>Consultas por Dia</h3>
                <div class="grafico"><canvas id="grafico-consultas"></canvas></div>
            </div>
            <div class="grafico-card">
                <h3>Serviços Mais Populares (de Consultas)</h3>
                <div class="grafico"><canvas id="grafico-servicos"></canvas></div>
            </div>
            <div class="grafico-card">
                <h3>Faturamento por Data (de Faturas)</h3>
                <div class="grafico"><canvas id="grafico-faturamento-data"></canvas></div> </div>
            </div>
    </div>
</div>

<style>
    .container {
        margin-left: 220px; /* Ajuste conforme a largura da sua sidebar */
        padding: 80px 30px 30px; /* Ajuste o padding superior se o header for fixo */
        transition: margin-left 0.3s;
        font-family: 'Arial', sans-serif; /* Adicionando uma fonte padrão */
    }
    .card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* Sombra mais suave */
        padding: 25px;
        margin-bottom: 25px;
    }
    .header-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    h1 {
        color: #0b3556;
        margin: 0;
        font-size: 24px; /* Tamanho de fonte ajustado */
    }
    .acoes-relatorio {
        display: flex;
        gap: 12px;
    }
    .btn-exportar, .btn-filtrar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 5px; /* Bordas mais arredondadas */
        padding: 10px 18px; /* Padding ajustado */
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s, transform 0.2s;
        font-size: 14px;
        font-weight: 500;
    }
    .btn-exportar:hover, .btn-filtrar:hover {
        background-color: #0d4371;
        transform: translateY(-2px); /* Efeito de elevação no hover */
    }
    .filtro-container {
        margin-bottom: 25px;
        padding: 20px;
        background-color: #f8f9fa; /* Cor de fundo mais clara */
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    .form-filtro {
        display: flex;
        flex-wrap: wrap;
        gap: 20px; /* Espaçamento aumentado */
        align-items: flex-end;
    }
    .form-group {
        flex: 1;
        min-width: 220px; /* Largura mínima aumentada */
    }
    .form-group label {
        display: block;
        margin-bottom: 8px; /* Espaçamento aumentado */
        font-weight: 500;
        color: #495057; /* Cor do label */
    }
    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px; /* Padding aumentado */
        border: 1px solid #ced4da; /* Cor da borda */
        border-radius: 5px;
        box-sizing: border-box; /* Para incluir padding e borda na largura total */
    }
    .btn-filtrar {
      height: auto; /* Altura automática baseada no padding */
      align-self: flex-end; /* Alinha com a base dos inputs */
    }
    .resumo-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); /* Largura mínima aumentada */
        gap: 20px;
        margin-bottom: 25px;
    }
    .card-resumo {
        display: flex;
        align-items: center;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        transition: transform 0.3s, box-shadow 0.3s;
        border-left: 4px solid #0b3556; /* Destaque lateral */
    }
    .card-resumo:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
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
        margin-right: 20px;
        font-size: 22px;
        flex-shrink: 0; /* Impede que o ícone encolha */
    }
    .resumo-info h3 {
        margin: 0 0 6px 0;
        font-size: 14px;
        color: #6c757d; /* Cor mais suave */
        font-weight: 500;
    }
    .resumo-valor {
        margin: 0;
        font-size: 22px; /* Tamanho aumentado */
        font-weight: 600;
        color: #0b3556;
    }
    .graficos-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Ajuste para telas menores */
        gap: 25px;
        margin-bottom: 25px;
    }
    .grafico-card {
        padding: 20px;
        background-color: #fff; /* Fundo branco para destaque */
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        transition: transform 0.3s, box-shadow 0.3s;
    }
     .grafico-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1);
    }
    .grafico-card h3 {
        margin: 0 0 20px 0;
        color: #0b3556;
        font-size: 18px; /* Tamanho aumentado */
        text-align: center; /* Centralizar título do gráfico */
    }
    .grafico {
        height: 280px; /* Altura aumentada */
        border-radius: 5px;
        overflow: hidden;
    }
    .grafico canvas {
        width: 100% !important;
        height: 100% !important;
    }

    /* Responsividade */
    @media (max-width: 992px) { /* Ajuste do breakpoint */
        .container {
            margin-left: 0; /* Remover margem para sidebar recolhida/mobile */
            padding: 70px 15px 15px;
        }
        .header-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        .acoes-relatorio {
            width: 100%;
            justify-content: flex-start; /* Alinhar botões à esquerda */
        }
    }
    @media (max-width: 768px) {
        .form-filtro {
            flex-direction: column;
            gap: 15px;
            align-items: stretch; /* Esticar itens do formulário */
        }
        .btn-filtrar {
            width: 100%; /* Botão de filtro ocupa largura total */
        }
        .resumo-container,
        .graficos-container {
            grid-template-columns: 1fr; /* Uma coluna em telas menores */
        }
        .grafico {
            height: 250px; /* Altura ajustada para mobile */
        }
        .resumo-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
            margin-right: 15px;
        }
        .resumo-valor {
            font-size: 20px;
        }
        h1 {
            font-size: 20px;
        }
        .grafico-card h3 { /* Título do gráfico */
            font-size: 16px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>


<script>
    const consultasPorDia = <?php echo json_encode($consultas_por_dia); ?>;
    const servicosPopulares = <?php echo json_encode($servicos_populares); ?>;
    const faturamentoPorDiaFaturas = <?php echo json_encode($faturamento_por_dia_faturas); ?>; // Variável atualizada
    const distribuicaoEspecies = <?php echo json_encode($distribuicao_especies); ?>;

    function exportarPDF() {
        Swal.fire({
            title: 'Gerando PDF...',
            text: 'Por favor, aguarde',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        let yPos = 15;
        const pageMargin = 15;
        const pageWidth = doc.internal.pageSize.getWidth();
        const contentWidth = pageWidth - (2 * pageMargin);

        doc.setFontSize(18).setTextColor(11, 53, 86).text('Relatório de Atendimentos', pageWidth / 2, yPos, { align: 'center' });
        yPos += 8;
        doc.setFontSize(10).setTextColor(100, 100, 100).text(`Período: ${document.getElementById('data_inicio').value} a ${document.getElementById('data_fim').value}`, pageWidth / 2, yPos, { align: 'center' });
        yPos += 12;

        function checkNewPage(currentY, neededHeight = 20) {
            if (currentY + neededHeight > doc.internal.pageSize.getHeight() - pageMargin) {
                doc.addPage();
                return pageMargin;
            }
            return currentY;
        }

        yPos = checkNewPage(yPos);
        doc.setFontSize(14).setTextColor(11, 53, 86).text('Resumo Geral', pageMargin, yPos);
        yPos += 8;

        doc.setFontSize(10).setTextColor(50, 50, 50);
        const resumoCards = document.querySelectorAll('.card-resumo');
        let xPosResumo = pageMargin;
        const cardWidth = (contentWidth / 2) - 5;
        
        resumoCards.forEach((card, index) => {
            const titulo = card.querySelector('h3').textContent;
            const valor = card.querySelector('.resumo-valor').textContent;
            if (index > 0 && index % 2 === 0) {
                yPos += 15;
                xPosResumo = pageMargin;
            }
            yPos = checkNewPage(yPos, 15);
            doc.setFillColor(248, 249, 250);
            doc.roundedRect(xPosResumo, yPos - 4, cardWidth, 12, 2, 2, 'F');
            doc.text(`${titulo}:`, xPosResumo + 5, yPos);
            doc.text(valor, xPosResumo + 5, yPos + 6);
            xPosResumo += cardWidth + 10;
        });
        yPos += 20;


        async function addChartToPdf(chartId, title) {
            yPos = checkNewPage(yPos, 70); 
            doc.setFontSize(12).setTextColor(11, 53, 86).text(title, pageMargin, yPos);
            yPos += 7;
        
            const chartElement = document.getElementById(chartId);
            if (chartElement && chartElement.offsetParent !== null) { 
                try {
                    const chartCardElement = chartElement.closest('.grafico-card');
                    const canvas = await html2canvas(chartCardElement, { scale: 1.5, backgroundColor: null });
                    const imgData = canvas.toDataURL('image/png');
                    
                    const imgProps = doc.getImageProperties(imgData);
                    let pdfImgWidth = contentWidth;
                    if (contentWidth > 180) pdfImgWidth = 180; 
                    
                    const pdfImgHeight = (imgProps.height * pdfImgWidth) / imgProps.width;
                    
                    yPos = checkNewPage(yPos, pdfImgHeight + 5); 
                    
                    const imgX = (pageWidth - pdfImgWidth) / 2; 
                    doc.addImage(imgData, 'PNG', imgX, yPos, pdfImgWidth, pdfImgHeight);
                    yPos += pdfImgHeight + 7;
                } catch (error) {
                    console.error(`Erro ao renderizar gráfico ${chartId} para PDF:`, error);
                    yPos = checkNewPage(yPos);
                    doc.setTextColor(255,0,0).setFontSize(8).text(`Erro ao renderizar gráfico: ${title}.`, pageMargin, yPos);
                    yPos += 7;
                }
            } else {
                yPos = checkNewPage(yPos);
                doc.setFontSize(9).setTextColor(150,150,150).text(`Gráfico "${title}" sem dados ou não encontrado.`, pageMargin, yPos);
                yPos += 7;
            }
        }
        
        (async () => {
            await addChartToPdf('grafico-consultas', 'Consultas por Dia');
            await addChartToPdf('grafico-servicos', 'Serviços Mais Populares (de Consultas)');
            await addChartToPdf('grafico-faturamento-data', 'Faturamento por Data (de Faturas)'); // ID e título atualizados
            if (document.getElementById('grafico-especies')) { 
                 await addChartToPdf('grafico-especies', 'Distribuição por Espécie');
            }

            doc.save('relatorio-atendimentos.pdf');
            Swal.close();
        })();
    }

    function exportarExcel() {
        Swal.fire({
            title: 'Gerando Excel...',
            text: 'Por favor, aguarde',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const wb = XLSX.utils.book_new();
        const dataInicioVal = document.getElementById('data_inicio').value;
        const dataFimVal = document.getElementById('data_fim').value;

        const resumoData = [
            ['Relatório de Atendimentos'],
            [`Período: ${dataInicioVal} a ${dataFimVal}`],
            [],
            ['Item de Resumo', 'Total'],
            ['Total de Consultas', <?php echo $total_consultas; ?>],
            ['Total de Vacinas Aplicadas', <?php echo $total_vacinas_aplicadas; ?>], // Título atualizado
            ['Total de Banhos/Tosas (Consultas)', <?php echo $total_banhos; ?>],
            ['Faturamento Total (R$)', <?php echo $total_faturamento; ?>]
        ];
        const resumoWs = XLSX.utils.aoa_to_sheet(resumoData);
        XLSX.utils.book_append_sheet(wb, resumoWs, 'Resumo');
        
        if(consultasPorDia.length > 0) {
            const data = [["Data", "Quantidade"]];
            consultasPorDia.forEach(item => data.push([item.data, item.quantidade]));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), 'Consultas por Dia');
        }
        if(servicosPopulares.length > 0) {
            const data = [["Serviço (de Consultas)", "Quantidade", "Percentual (%)"]];
            servicosPopulares.forEach(item => data.push([item.servico, item.quantidade, item.percentual]));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), 'Serviços Populares');
        }
        // Atualizado para Faturamento por Data
        if(faturamentoPorDiaFaturas.length > 0) {
            const data = [["Data Faturamento", "Valor Total Dia (R$)"]];
            faturamentoPorDiaFaturas.forEach(item => data.push([item.data_faturamento, parseFloat(item.valor_total_dia)]));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), 'Faturamento por Data');
        }
        if(distribuicaoEspecies.length > 0) {
            const data = [["Espécie", "Quantidade"]];
            distribuicaoEspecies.forEach(item => data.push([item.especie, item.quantidade]));
            XLSX.utils.book_append_sheet(wb, XLSX.utils.aoa_to_sheet(data), 'Distribuição por Espécie');
        }

        XLSX.writeFile(wb, 'relatorio-atendimentos.xlsx');
        Swal.close();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const chartColors = [
            'rgba(11, 53, 86, 0.8)', 'rgba(54, 162, 235, 0.8)', 'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)', 'rgba(153, 102, 255, 0.8)', 'rgba(255, 159, 64, 0.8)',
            'rgba(255, 99, 132, 0.8)', 'rgba(101, 115, 130, 0.8)'
        ];
        const chartBorderColors = chartColors.map(color => color.replace('0.8', '1'));

        function renderChart(ctxId, chartType, labels, data, datasetLabel, optionsOverrides = {}) {
            const ctx = document.getElementById(ctxId);
            if (!ctx) {
                console.warn(`Elemento canvas com ID '${ctxId}' não encontrado.`);
                return; 
            }

            const parentElement = ctx.parentElement; 
            // Limpa o conteúdo anterior do gráfico (importante para re-filtragem)
            // Remove o canvas antigo e cria um novo para evitar problemas com Chart.js
            parentElement.innerHTML = `<canvas id="${ctxId}"></canvas>`;
            const newCtx = document.getElementById(ctxId).getContext('2d');


            if (!labels || labels.length === 0) {
                parentElement.innerHTML = `<p style="text-align:center; padding: 20% 10px 0;">Sem dados para exibir para o período selecionado.</p>`;
                return;
            }
            
            new Chart(newCtx, { // Usa o novo contexto
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: data,
                        backgroundColor: chartType === 'line' ? 'rgba(11, 53, 86, 0.2)' : (chartType === 'bar' ? 'rgba(54, 162, 235, 0.8)' : chartColors),
                        borderColor: chartType === 'line' ? 'rgba(11, 53, 86, 1)' : (chartType === 'bar' ? 'rgba(54, 162, 235, 1)' : chartBorderColors),
                        borderWidth: chartType === 'line' ? 2 : 1,
                        tension: chartType === 'line' ? 0.3 : undefined,
                        fill: chartType === 'line' ? true : undefined
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: (chartType !== 'line' && chartType !== 'bar'), position: 'top' },
                        title: { display: false }, 
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.raw || 0;
                                    if (chartType === 'pie' || chartType === 'doughnut') {
                                        const total = context.dataset.data.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
                                        const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                    // Para gráfico de faturamento (bar ou line)
                                    if (ctxId === 'grafico-faturamento-data') { 
                                       return `${label}: R$ ${Number(value).toFixed(2).replace('.', ',')}`;
                                    }
                                    return `${label}: ${value}`;
                                }
                            }
                        }
                    },
                    scales: (chartType === 'line' || chartType === 'bar') ? {
                        y: { 
                            beginAtZero: true, 
                            ticks: { 
                                precision: 0,
                                callback: function(value, index, values) { // Formatar eixo Y para R$ se for faturamento
                                    if (ctxId === 'grafico-faturamento-data') {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                    return value;
                                }
                            } 
                        },
                        x: { 
                            grid: { display: chartType !== 'bar' } ,
                            ticks: {
                                callback: function(value, index, values) { // Para formatar datas no eixo X se necessário
                                    // 'this.getLabelForValue(value)' pega o label original (data DD/MM)
                                    return this.getLabelForValue(value); 
                                }
                            }
                        } 
                    } : undefined,
                    indexAxis: chartType === 'bar' && optionsOverrides.indexAxis === 'y' ? 'y' : 'x',
                    ...optionsOverrides
                }
            });
        }

        renderChart('grafico-consultas', 'line', consultasPorDia.map(item => {
            const dateParts = item.data.split('-'); return `${dateParts[2]}/${dateParts[1]}`;
        }), consultasPorDia.map(item => item.quantidade), 'Consultas');

        renderChart('grafico-servicos', 'doughnut', servicosPopulares.map(item => item.servico), servicosPopulares.map(item => item.quantidade), 'Serviços');
        
        // Gráfico de Faturamento por Data
        renderChart('grafico-faturamento-data', 'bar', 
            faturamentoPorDiaFaturas.map(item => {
                const dateParts = item.data_faturamento.split('-'); // YYYY-MM-DD
                return `${dateParts[2]}/${dateParts[1]}`; // Formato DD/MM
            }), 
            faturamentoPorDiaFaturas.map(item => parseFloat(item.valor_total_dia)), 
            'Faturamento Diário'
        );

        if (distribuicaoEspecies && distribuicaoEspecies.length > 0) {
            const graficosContainer = document.querySelector('.graficos-container');
            let graficoEspeciesCard = document.getElementById('grafico-especies-card');
            if (!graficoEspeciesCard) {
                graficoEspeciesCard = document.createElement('div');
                graficoEspeciesCard.className = 'grafico-card';
                graficoEspeciesCard.id = 'grafico-especies-card';
                graficoEspeciesCard.innerHTML = '<h3>Distribuição por Espécie</h3><div class="grafico"><canvas id="grafico-especies"></canvas></div>';
                graficosContainer.appendChild(graficoEspeciesCard);
            }
            renderChart('grafico-especies', 'bar', distribuicaoEspecies.map(item => item.especie), distribuicaoEspecies.map(item => item.quantidade), 'Quantidade', { indexAxis: 'y' });
        } else {
            const graficoEspeciesCardExistente = document.getElementById('grafico-especies-card');
            if (graficoEspeciesCardExistente) {
                 graficoEspeciesCardExistente.querySelector('.grafico').innerHTML = '<p style="text-align:center; padding: 20% 10px 0;">Sem dados de distribuição por espécie para o período.</p>';
            }
        }
    });
</script>

</body>
</html>
