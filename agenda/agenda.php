<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    $_SESSION['usuario_id'] = 1;
   
}

$caminhoBase = '../';

require_once('../includes/header.php');
require_once('../includes/sidebar.php');

require_once('../conecta_db.php');
$conn = conecta_db();


$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $data_hora = $data . ' ' . $hora;

    $id_pet = $_POST['id_pet'];
    $id_tutor = $_POST['id_tutor'];
    $id_servico = $_POST['id_servico'];
    $status_a = 'agendado'; // ou $_POST['status_a'] se vier do form
    $observacoes = $_POST['observacoes'];

    $stmt = $conn->prepare("INSERT INTO Agendamentos (id_pet, id_tutor, id_servico, data_hora, status_a, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $id_pet, $id_tutor, $id_servico, $data_hora, $status_a, $observacoes);

    if ($stmt->execute()) {
        $mensagem = "Agendamento realizado com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao agendar: " . $stmt->error;
        $tipo_mensagem = "erro";
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

// ... (código de processamento do POST para inserir agendamento) ...

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
        // Separar data e hora se 'data_hora' for DATETIME
        $dateTime = new DateTime($row['data_hora']);
        $agendamentos[] = [
            'id' => $row['id'],
            'data' => $dateTime->format('Y-m-d'),
            'hora' => $dateTime->format('H:i'),
            'tutor' => $row['tutor'],       // agora nome do tutor
            'pet' => $row['pet'],           // agora nome do pet
            'servico' => $row['servico']    // agora nome do serviço
        ];
    }
}
$stmt->close();

// O array $agendamentos agora contém dados do banco para a semana atual
// A simulação $agendamentos = [ ... ]; deve ser removida ou comentada.
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_agendamento = (int)$_GET['excluir'];

    $conn->begin_transaction(); // Inicia uma transação

    try {
        // CORREÇÃO: Usar a tabela 'Agendamentos' (no plural)
        $sql = "DELETE FROM Agendamentos WHERE id_agendamento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_agendamento);

        if (!$stmt->execute()) {
            throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
        }

        $conn->commit(); // Confirma a transação

        $_SESSION['mensagem'] = "Agendamento excluído com sucesso!"; // Armazena em sessão
        $_SESSION['tipo_mensagem'] = "sucesso";
        } catch (Exception $e) {
            $conn->rollback(); // Reverte a transação em caso de erro

            $_SESSION['mensagem'] = "Erro ao excluir agendamento: " . $e->getMessage(); // Armazena em sessão
            $_SESSION['tipo_mensagem'] = "erro";
        }
        // Redireciona para evitar re-submissão e exibir mensagem
        //header("Location: agenda.php"); 
        //exit();
}

// Busca todos os tutores para o dropdown
$sql = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$result = $conn->query($sql);
$tutores = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tutores[] = $row;
    }
}


?>

<div class="container">
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
                            echo '<strong>' . $agendamento['tutor'] . '</strong><br>';
                            echo 'Pet: ' . $agendamento['pet'] . '<br>';
                            echo $agendamento['servico'];
                            
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

<!-- Modal de Agendamento -->
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
                <label for="tutor">Cliente:</label>
                <select id="cliente" name="id_tutor" required>
                    <?php foreach ($tutores as $tutor): ?>
                        <option value="<?= $tutor['id_tutor'] ?>"><?= htmlspecialchars($tutor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="pet">Pet:</label>
                <select id="pet" name="id_pet" required>
                    <option value="">Selecione um pet</option>
                    <!-- Será preenchido via JavaScript quando o cliente for selecionado -->
                </select>
            </div>
            <div class="form-group">
                <label for="servico">Serviço:</label>
                <select id="servico" name="id_servico" required>
                    <option value="">Selecione um serviço</option>
                    <option value="1">Consulta de Rotina</option>
                    <option value="2">Vacinação</option>
                    <option value="3">Banho e Tosa</option>
                    <option value="4">Exames Laboratoriais</option>
                    <option value="5">Cirurgia</option>
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
    }
    
    // Função para fechar o modal
    function fecharModalAgendamento() {
        const modal = document.getElementById('modalAgendamento');
        modal.style.display = 'none';
        document.getElementById('formAgendamento').reset();
    }
    
    // Simulação de dados de pets por cliente
    const petsPorCliente = {
        '1': [
            { id: 1, nome: 'Rex (Labrador)' },
            { id: 2, nome: 'Nina (Gato Persa)' }
        ],
        '2': [
            { id: 3, nome: 'Luna (Poodle)' }
        ],
        '3': [
            { id: 4, nome: 'Mel (Golden Retriever)' },
            { id: 5, nome: 'Bob (Bulldog)' } // Corrigido
        ],
        '4': [
            { id: 6, nome: 'Thor (Pastor Alemão)' }
        ]
    };
    
    // Atualiza a lista de pets quando o cliente é selecionado
    document.getElementById('cliente').addEventListener('change', function() {
        const clienteId = this.value;
        const petSelect = document.getElementById('pet');
        
        // Limpa as opções atuais
        petSelect.innerHTML = '<option value="">Selecione um pet</option>';
        
        // Se um cliente foi selecionado, adiciona seus pets
        if (clienteId && petsPorCliente[clienteId]) {
            petsPorCliente[clienteId].forEach(pet => {
                const option = document.createElement('option');
                option.value = pet.id;
                option.textContent = pet.nome;
                petSelect.appendChild(option);
            });
        }
    });

    function abrirModalEdicao(id) {
    // Aqui você pode carregar os dados via AJAX ou passar via PHP
    alert("Abrir modal para editar agendamento com ID: " + id);
    // Você pode preencher o modal com os dados do agendamento e mostrar
    }

    function excluirAgendamento(id) {
    if (confirm("Deseja realmente excluir este agendamento?")) {
        window.location.href = "agenda.php?excluir=" + id;
    }
}

    document.getElementById("cliente").addEventListener("change", function() {
    const idTutor = this.value;
    const petSelect = document.getElementById("pet");

    petSelect.innerHTML = '<option>Carregando...</option>';

    fetch("get_pets.php?id_tutor=" + idTutor)
        .then(response => response.json())
        .then(data => {
            petSelect.innerHTML = '<option value="">Selecione um pet</option>';
            data.forEach(pet => {
                const option = document.createElement("option");
                option.value = pet.id_pet;
                option.textContent = pet.nome;
                petSelect.appendChild(option);
            });
        })
        .catch(() => {
            petSelect.innerHTML = '<option>Erro ao carregar</option>';
            });
    });
    
    // Manipula o envio do formulário
    document.getElementById('formAgendamento').addEventListener('submit', function(e) {

    });
    
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