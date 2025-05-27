<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Conexão com o banco de dados
require_once('../conecta_db.php');
$conn = conecta_db();

// --- Lógica de Mensagens da Sessão ---
$mensagem = '';
$tipo_mensagem = '';

if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];
    unset($_SESSION['mensagem']); // Limpa a mensagem da sessão
    unset($_SESSION['tipo_mensagem']); // Limpa o tipo da mensagem da sessão
}
// --- Fim Lógica de Mensagens da Sessão ---

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $data_hora = $data . ' ' . $hora;

    $id_pet = $_POST['id_pet'];
    $id_tutor = $_POST['id_tutor'];
    $id_servico = $_POST['id_servico'];
    $status_a = 'agendado'; 
    $observacoes = $_POST['observacoes'];

    $stmt = $conn->prepare("INSERT INTO Agendamentos (id_pet, id_tutor, id_servico, data_hora, status_a, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $id_pet, $id_tutor, $id_servico, $data_hora, $status_a, $observacoes);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = "Agendamento realizado com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } else {
        $_SESSION['mensagem'] = "Erro ao agendar: " . $stmt->error;
        $_SESSION['tipo_mensagem'] = "erro"; 
        header("Location: agenda.php"); // Redireciona após POST para evitar reenvio
    exit();
    }
   
    $stmt->close();

}

// Obtém a data atual e calcula o início e fim da semana
$dataAtual = isset($_GET['data']) ? new DateTime($_GET['data']) : new DateTime();
$inicioSemana = clone $dataAtual;
$inicioSemana->modify('monday this week');
$fimSemana = clone $inicioSemana;
$fimSemana->modify('+6 days');

// Formata as datas para exibição
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
$mesAno = strftime('%B de %Y', $dataAtual->getTimestamp());
$dataAnterior = clone $inicioSemana;
$dataAnterior->modify('-7 days');
$dataProxima = clone $inicioSemana;
$dataProxima->modify('+7 days');

// Tradução dos dias da semana
$diasSemana = [
    'Mon' => 'Seg',
    'Tue' => 'Ter',
    'Wed' => 'Qua',
    'Thu' => 'Qui',
    'Fri' => 'Sex',
    'Sat' => 'Sáb',
    'Sun' => 'Dom'
];

// Horários de funcionamento
$horarios = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'
];


// Consulta para buscar os agendamentos da semana DO BANCO DE DADOS
$dataInicioFormatada = $inicioSemana->format('Y-m-d');
$dataFimFormatada = $fimSemana->format('Y-m-d');

$sqlAgendamentos = "SELECT 
                        A.id_agendamento as id, 
                        A.data_hora, 
                        T.nome as tutor, 
                        P.nome as pet, 
                        S.nome as servico
                    FROM 
                        Agendamentos A
                    LEFT JOIN 
                        Tutor T ON A.id_tutor = T.id_tutor
                    LEFT JOIN 
                        Pets P ON A.id_pet = P.id_pet
                    LEFT JOIN 
                        Servicos S ON A.id_servico = S.id_servico
                    WHERE 
                        DATE(A.data_hora) BETWEEN ? AND ?";

$stmt = $conn->prepare($sqlAgendamentos);
$stmt->bind_param("ss", $dataInicioFormatada, $dataFimFormatada);
$stmt->execute();
$resultAgendamentos = $stmt->get_result();

$agendamentos = [];
if ($resultAgendamentos->num_rows > 0) {
    while ($row = $resultAgendamentos->fetch_assoc()) {
        $dateTime = new DateTime($row['data_hora']);
        $agendamentos[] = [
            'id' => $row['id'],
            'data' => $dateTime->format('Y-m-d'),
            'hora' => $dateTime->format('H:i'),
            'tutor' => $row['tutor'],
            'pet' => $row['pet'],
            'servico' => $row['servico']
        ];
    }
}
$stmt->close();

// Lógica de Exclusão
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_agendamento = (int)$_GET['excluir'];

    $conn->begin_transaction(); 

    try {
        $sql = "DELETE FROM Agendamentos WHERE id_agendamento = ?"; // CORREÇÃO: Tabela "Agendamentos"
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_agendamento);

        if (!$stmt->execute()) {
            throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
        }

        $conn->commit(); 

            $_SESSION['mensagem'] = "Agendamento excluído com sucesso!"; 
            $_SESSION['tipo_mensagem'] = "sucesso";
        } catch (Exception $e) {
            $conn->rollback(); 
            $_SESSION['mensagem'] = "Erro ao excluir agendamento: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "erro";

            header("Location: agenda.php"); // Redireciona após a exclusão
        exit();
    }
    
}

// Busca todos os tutores para o dropdown
$sqlTutores = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$resultTutores = $conn->query($sqlTutores);
$tutores = [];

if ($resultTutores && $resultTutores->num_rows > 0) {
    while ($row = $resultTutores->fetch_assoc()) {
        $tutores[] = $row;
    }
}

// Busca todos os serviços para o dropdown
$sqlServicos = "SELECT id_servico, nome FROM Servicos ORDER BY nome";
$resultServicos = $conn->query($sqlServicos);
$servicos = [];

if ($resultServicos && $resultServicos->num_rows > 0) {
    while ($row = $resultServicos->fetch_assoc()) {
        $servicos[] = $row;
    }
}

?>
<div class="container">
    <?php if (!empty($mensagem)): // Bloco para exibir mensagens ?>
        <div class="alerta <?php echo $tipo_mensagem; ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="header-card">
            <h1>Agenda</h1>
            <button class="btn-novo" onclick="abrirModalAgendamento()">Novo Agendamento</button>
        </div>
        
        <div class="calendario-navegacao">
            <a href="?data=<?php echo $dataAnterior->format('Y-m-d'); ?>" class="btn-nav">&lt; Semana Anterior</a>
            <h2><?php echo ucfirst($mesAno); ?></h2>
            <a href="?data=<?php echo $dataProxima->format('Y-m-d'); ?>" class="btn-nav">Próxima Semana &gt;</a>
        </div>
        
        <div class="calendario">
            <div class="dias-semana">
                <div class="horario-header"></div>
                <?php
                $diaSemana = clone $inicioSemana;
                for ($i = 0; $i < 7; $i++) {
                    $nomeDia = $diasSemana[$diaSemana->format('D')];
                    echo '<div class="dia-header">';
                    echo '<span class="dia-nome">' . $nomeDia . '</span>';
                    echo '<span class="dia-numero">' . $diaSemana->format('d') . '</span>';
                    echo '</div>';
                    $diaSemana->modify('+1 day');
                }
                ?>
            </div>
            
            <div class="horarios-grid">
                <?php foreach ($horarios as $horario): ?>
                    <div class="horario-celula"><?php echo $horario; ?></div>
                    <?php
                    $diaSemana = clone $inicioSemana;
                    for ($i = 0; $i < 7; $i++) {
                        $dataAtualLoop = $diaSemana->format('Y-m-d');
                        $agendamentosHorario = array_filter($agendamentos, function($a) use ($dataAtualLoop, $horario) {
                            return $a['data'] == $dataAtualLoop && $a['hora'] == $horario;
                        });
                        
                        echo '<div class="agenda-celula" onclick="abrirModalAgendamento(\'' . $dataAtualLoop . '\', \'' . $horario . '\')">';
                        
                        foreach ($agendamentosHorario as $agendamento) {
                            echo '<div class="agendamento">';
                            echo '<strong>' . htmlspecialchars($agendamento['tutor']) . '</strong><br>';
                            echo 'Pet: ' . htmlspecialchars($agendamento['pet']) . '<br>';
                            echo htmlspecialchars($agendamento['servico']);
                            
                            echo '<div class="botoes-agendamento">';
                            echo '<button class="btn-alterar" onclick="abrirModalEdicao(' . $agendamento['id'] . ')">Alterar</button>';
                            echo '<button class="btn-excluir" onclick="excluirAgendamento(' . $agendamento['id'] . ')">Excluir</button>';
                            echo '</div>';

                            echo '</div>';
                        }
                        
                        echo '</div>';
                        $diaSemana->modify('+1 day');
                    }
                    ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div id="modalAgendamento" class="modal">
    <div class="modal-content">
        <span class="fechar" onclick="fecharModalAgendamento()">&times;</span>
        <h2>Novo Agendamento</h2>
        <form id="formAgendamento" method="POST" action="">
            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="hora">Horário:</label>
                <select id="hora" name="hora" required>
                    <?php foreach ($horarios as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="cliente">Cliente:</label>
                <select id="cliente" name="id_tutor" required>
                    <option value="">Selecione um cliente</option> <?php foreach ($tutores as $tutor): ?>
                        <option value="<?= htmlspecialchars($tutor['id_tutor']) ?>"><?= htmlspecialchars($tutor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_pet">Pet:</label>
                <select id="id_pet" name="id_pet" required>
                    <option value="">Selecione um pet</option>
                    </select>
            </div>
            <div class="form-group">
                <label for="servico">Serviço:</label>
                <select id="servico" name="id_servico" required>
                    <option value="">Selecione um serviço</option>
                    <?php foreach ($servicos as $servico): ?>
                        <option value="<?= htmlspecialchars($servico['id_servico']) ?>"><?= htmlspecialchars($servico['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancelar" onclick="fecharModalAgendamento()">Cancelar</button>
                <button type="submit" class="btn-salvar">Salvar</button>
            </div>
        </form>
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
    
    .btn-novo {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .btn-novo:hover {
        background-color: #0d4371;
    }
    
    .calendario-navegacao {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .btn-nav {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 8px 12px;
        text-decoration: none;
        font-size: 14px;
    }
    
    .btn-nav:hover {
        background-color: #e0e0e0;
    }
    
    .calendario {
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .dias-semana {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
    }
    
    .horario-header {
        border-right: 1px solid #ddd;
    }
    
    .dia-header {
        padding: 10px;
        text-align: center;
        border-right: 1px solid #ddd;
        display: flex;
        flex-direction: column;
    }
    
    .dia-nome {
        font-weight: bold;
        font-size: 14px;
    }
    
    .dia-numero {
        font-size: 18px;
        margin-top: 5px;
    }
    
    .horarios-grid {
        display: grid;
        grid-template-columns: 80px repeat(7, 1fr);
    }
    
    .horario-celula {
        padding: 10px;
        border-right: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        background-color: #f9f9f9;
        font-size: 14px;
        text-align: center;
    }
    
    .agenda-celula {
        min-height: 60px;
        border-right: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        padding: 5px;
        cursor: pointer;
    }
    
    .agenda-celula:hover {
        background-color: #f0f8ff;
    }
    
    .agendamento {
        background-color: #e1f5fe;
        border-left: 3px solid #03a9f4;
        padding: 5px;
        margin-bottom: 5px;
        border-radius: 3px;
        font-size: 12px;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 8px;
        position: relative;
    }
    
    .fechar {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .fechar:hover {
        color: black;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-cancelar {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    .btn-salvar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .container {
            margin-left: 60px;
            padding: 70px 15px 15px;
        }
        
        .calendario {
            overflow-x: auto;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
    .botoes-agendamento {
    margin-top: 5px;
    display: flex;
    gap: 5px;
    }

    .btn-alterar, .btn-excluir {
        padding: 4px 6px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-alterar {
        background-color: #f0ad4e;
        color: white;
    }

    .btn-excluir {
        background-color: #d9534f;
        color: white;
    }

</style>

<script>
    // Função para abrir o modal de agendamento
    function abrirModalAgendamento(data, hora) {
        const modal = document.getElementById('modalAgendamento');
        modal.style.display = 'block';
        
        if (data && hora) {
            document.getElementById('data').value = data;
            document.getElementById('hora').value = hora;
        }
        // Quando o modal é aberto, dispare o evento 'change' no select de cliente
        // caso já tenha um valor pré-selecionado (se o usuário estiver editando, por exemplo)
        const clienteSelect = document.getElementById('cliente');
        if (clienteSelect.value) {
            clienteSelect.dispatchEvent(new Event('change'));
        }
    }
    
    // Função para fechar o modal
    function fecharModalAgendamento() {
        const modal = document.getElementById('modalAgendamento');
        modal.style.display = 'none';
        document.getElementById('formAgendamento').reset();
        // Limpa as opções de pets ao fechar o modal
        document.getElementById('id_pet').innerHTML = '<option value="">Selecione um pet</option>';
    }
    
    // --- Lógica AJAX para carregar pets ---
    // CORREÇÃO: Usar o ID correto 'cliente' e 'id_pet'
    document.getElementById('cliente').addEventListener('change', function() {
        const idTutor = this.value;
        const petSelect = document.getElementById('id_pet'); // CORREÇÃO: ID 'id_pet'
        
        petSelect.innerHTML = '<option value="">Carregando pets...</option>'; // Feedback ao usuário
        petSelect.disabled = true; // Desabilita enquanto carrega

        if (idTutor) {
            // CORREÇÃO: Chamada para get_pets.php
            fetch("get_pets.php?id_tutor=" + idTutor)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    petSelect.innerHTML = '<option value="">Selecione um pet</option>';
                    if (data.length > 0) {
                        data.forEach(pet => {
                            const option = document.createElement('option');
                            option.value = pet.id_pet; // Assumindo que get_pets.php retorna id_pet
                            option.textContent = pet.nome; // Assumindo que get_pets.php retorna nome
                            petSelect.appendChild(option);
                        });
                    } else {
                        petSelect.innerHTML = '<option value="">Nenhum pet encontrado para este tutor</option>';
                    }
                    petSelect.disabled = false; // Reabilita
                })
                .catch(error => {
                    console.error('Erro ao buscar pets:', error);
                    petSelect.innerHTML = '<option value="">Erro ao carregar pets</option>';
                    petSelect.disabled = false;
                });
        } else {
            petSelect.innerHTML = '<option value="">Selecione um pet</option>';
            petSelect.disabled = false;
        }
    });
    // --- Fim Lógica AJAX para carregar pets ---


    function abrirModalEdicao(id) {
        alert("Função de edição ainda não implementada. ID do agendamento: " + id);
        // Em um projeto real, aqui você faria uma requisição AJAX
        // para buscar os detalhes do agendamento com 'id' e preencher o modal.
        // Em seguida, abriria o modal.
    }

    function excluirAgendamento(id) {
        if (confirm("Tem certeza que deseja excluir este agendamento?")) {
            window.location.href = "agenda.php?excluir=" + id; // CORREÇÃO: Usar 'excluir'
        }
    }
    
    // O submit do formulário já é tratado pelo PHP no topo, não precisa de preventDefault aqui
    // document.getElementById('formAgendamento').addEventListener('submit', function(e) {
    //     //e.preventDefault(); 
    // });
    
    // Fecha o modal se o usuário clicar fora dele
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalAgendamento');
        if (event.target == modal) {
            fecharModalAgendamento();
        }
    });
</script>

</body>
</html>