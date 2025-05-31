<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../conecta_db.php');
$conn = conecta_db();


if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {

}

// PROCESSAMENTO DE FORMULÁRIO POST (QUE PODE REDIRECIONAR)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = $_POST['data'];
    $hora = $_POST['hora'];
    $data_hora = $data . ' ' . $hora;
    $id_pet = $_POST['id_pet'];
    $id_tutor = $_POST['id_tutor'];
    $id_servico = $_POST['id_servico'];
    $observacoes = $_POST['observacoes'];
    $id_agendamento_edit = isset($_POST['id_agendamento']) ? (int)$_POST['id_agendamento'] : 0;

    // Para manter a semana atual no redirecionamento
    $data_original_semana_post = isset($_POST['data_original_semana_post']) ? $_POST['data_original_semana_post'] : (isset($_GET['data']) ? $_GET['data'] : null);


    if ($id_agendamento_edit > 0) {
        $status_a_update = 'agendado'; // ou qualquer status que você use para edição
        $stmt = $conn->prepare("UPDATE Agendamentos SET id_pet = ?, id_tutor = ?, id_servico = ?, data_hora = ?, observacoes = ?, status_a = ? WHERE id_agendamento = ?");
        $stmt->bind_param("iiisssi", $id_pet, $id_tutor, $id_servico, $data_hora, $observacoes, $status_a_update, $id_agendamento_edit);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Agendamento atualizado com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso"; // Use 'sucesso' ou 'erro'
        } else {
            $_SESSION['mensagem'] = "Erro ao atualizar agendamento: " . $stmt->error;
            $_SESSION['tipo_mensagem'] = "erro";
        }
    } else {
        $status_a_insert = 'agendado';
        $stmt = $conn->prepare("INSERT INTO Agendamentos (id_pet, id_tutor, id_servico, data_hora, status_a, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $id_pet, $id_tutor, $id_servico, $data_hora, $status_a_insert, $observacoes);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "Agendamento realizado com sucesso!";
            $_SESSION['tipo_mensagem'] = "sucesso";
        } else {
            $_SESSION['mensagem'] = "Erro ao agendar: " . $stmt->error;
            $_SESSION['tipo_mensagem'] = "erro";
        }
    }
    $stmt->close();

    $redirect_url = "agenda.php";
    if ($data_original_semana_post) {
        $redirect_url .= "?data=" . urlencode($data_original_semana_post);
    }
    header("Location: " . $redirect_url);
    exit(); // Essencial após o header Location
}

// 4. LÓGICA DE EXCLUSÃO (QUE PODE REDIRECIONAR)
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_agendamento = (int)$_GET['excluir'];
    $data_original_semana = isset($_GET['data_original_semana']) ? $_GET['data_original_semana'] : null;

    $conn->begin_transaction();
    try {
        $sql = "DELETE FROM Agendamentos WHERE id_agendamento = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_agendamento);
        if (!$stmt->execute()) {
            throw new Exception("Falha ao executar a exclusão: " . $stmt->error);
        }
        $stmt->close();
        $conn->commit();
        $_SESSION['mensagem'] = "Agendamento excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir agendamento: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
    
    $redirect_url = "agenda.php";
    if ($data_original_semana) {
        $redirect_url .= "?data=" . urlencode($data_original_semana);
    }
    header("Location: " . $redirect_url);
    exit(); // Essencial
}

// 5. PREPARAR MENSAGENS DA SESSÃO (NÃO GERA SAÍDA)
$mensagem_sessao = null;
$tipo_mensagem_sessao = null;
if (isset($_SESSION['mensagem'])) {
    $mensagem_sessao = $_SESSION['mensagem'];
    $tipo_mensagem_sessao = $_SESSION['tipo_mensagem'];
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

// 6. DEFINIR CAMINHO BASE E INCLUIR ARQUIVOS QUE GERAM HTML
$caminhoBase = '../'; // Defina o caminho base se necessário para os includes
require_once('../includes/header.php'); // Assumindo que este arquivo inicia o HTML e o <head>
require_once('../includes/sidebar.php');

// 7. RESTANTE DA LÓGICA PARA BUSCAR DADOS E EXIBIR A PÁGINA
$dataAtual = isset($_GET['data']) ? new DateTime($_GET['data']) : new DateTime();
$inicioSemana = clone $dataAtual;
$inicioSemana->modify('monday this week');
$fimSemana = clone $inicioSemana;
$fimSemana->modify('+6 days');

// Configurar local para português do Brasil
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    setlocale(LC_TIME, 'Portuguese_Brazil.1252'); // Windows
} else {
    setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR.UTF-8', 'Portuguese'); // Linux/Mac
}
$mesAno = strftime('%B de %Y', $dataAtual->getTimestamp());

$dataAnterior = clone $inicioSemana;
$dataAnterior->modify('-7 days');
$dataProxima = clone $inicioSemana;
$dataProxima->modify('+7 days');

$diasSemana = [
    'Mon' => 'Seg', 'Tue' => 'Ter', 'Wed' => 'Qua',
    'Thu' => 'Qui', 'Fri' => 'Sex', 'Sat' => 'Sáb', 'Sun' => 'Dom'
];
$horarios = [
    '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'
];

$dataInicioFormatada = $inicioSemana->format('Y-m-d');
$dataFimFormatada = $fimSemana->format('Y-m-d');
$sqlAgendamentos = "SELECT 
                        A.id_agendamento as id, A.data_hora, 
                        T.nome as tutor, P.nome as pet, S.nome as servico,
                        A.id_tutor, A.id_pet as pet_id_db, A.id_servico as id_servico_db, A.observacoes as observacoes_db
                    FROM Agendamentos A
                    LEFT JOIN Tutor T ON A.id_tutor = T.id_tutor
                    LEFT JOIN Pets P ON A.id_pet = P.id_pet
                    LEFT JOIN Servicos S ON A.id_servico = S.id_servico
                    WHERE DATE(A.data_hora) BETWEEN ? AND ?";
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
            'servico' => $row['servico'],
            'id_tutor' => $row['id_tutor'],
            'pet_id' => $row['pet_id_db'],
            'id_servico' => $row['id_servico_db'],
            'observacoes' => $row['observacoes_db']
        ];
    }
}
$stmt->close();

$sqlTutores = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$resultTutores = $conn->query($sqlTutores);
$tutores = [];
if ($resultTutores && $resultTutores->num_rows > 0) {
    while ($row = $resultTutores->fetch_assoc()) {
        $tutores[] = $row;
    }
}

$sqlServicos = "SELECT id_servico, nome FROM Servicos ORDER BY nome";
$resultServicos = $conn->query($sqlServicos);
$servicos_dropdown = []; // Renomeado para evitar conflito com $agendamento['servico']
if ($resultServicos && $resultServicos->num_rows > 0) {
    while ($row = $resultServicos->fetch_assoc()) {
        $servicos_dropdown[] = $row;
    }
}
$conn->close(); // Fechar a conexão quando não for mais necessária
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
                $diaSemanaIter = clone $inicioSemana;
                for ($i = 0; $i < 7; $i++) {
                    $nomeDia = $diasSemana[$diaSemanaIter->format('D')];
                    echo '<div class="dia-header"><span class="dia-nome">' . $nomeDia . '</span><span class="dia-numero">' . $diaSemanaIter->format('d') . '</span></div>';
                    $diaSemanaIter->modify('+1 day');
                }
                ?>
            </div>
            
            <div class="horarios-grid">
                <?php foreach ($horarios as $horario_slot): ?>
                    <div class="horario-celula"><?php echo $horario_slot; ?></div>
                    <?php
                    $diaSemanaIter = clone $inicioSemana;
                    for ($i = 0; $i < 7; $i++) {
                        $dataAtualLoop = $diaSemanaIter->format('Y-m-d');
                        $agendamentosHorario = array_filter($agendamentos, function($a) use ($dataAtualLoop, $horario_slot) {
                            return $a['data'] == $dataAtualLoop && $a['hora'] == $horario_slot;
                        });
                        
                        echo '<div class="agenda-celula" onclick="abrirModalAgendamento(\'' . $dataAtualLoop . '\', \'' . $horario_slot . '\')">';
                        foreach ($agendamentosHorario as $agendamento_item) {
                            echo '<div class="agendamento">';
                            echo '<strong>' . htmlspecialchars($agendamento_item['tutor']) . '</strong><br>';
                            echo 'Pet: ' . htmlspecialchars($agendamento_item['pet']) . '<br>';
                            echo htmlspecialchars($agendamento_item['servico']);
                            echo '<div class="botoes-agendamento">';
                            echo '<button class="btn-alterar" onclick="event.stopPropagation(); abrirModalEdicao(' . $agendamento_item['id'] . ')">Alterar</button>';
                            echo '<button class="btn-excluir" onclick="event.stopPropagation(); excluirAgendamento(' . $agendamento_item['id'] . ', \'' . $dataAtual->format('Y-m-d') . '\')">Excluir</button>';
                            echo '</div></div>';
                        }
                        echo '</div>';
                        $diaSemanaIter->modify('+1 day');
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
        <h2 id="modalTitulo">Novo Agendamento</h2>
        <form id="formAgendamento" method="POST" action="agenda.php<?php echo isset($_GET['data']) ? '?data=' . urlencode($_GET['data']) : ''; ?>">
            <input type="hidden" id="id_agendamento" name="id_agendamento" value="">
            <input type="hidden" name="data_original_semana_post" value="<?php echo isset($_GET['data']) ? htmlspecialchars($_GET['data']) : ($dataAtual ? htmlspecialchars($dataAtual->format('Y-m-d')) : ''); ?>">

            <div class="form-group">
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="hora">Horário:</label>
                <select id="hora" name="hora" required>
                    <?php foreach ($horarios as $h_option): ?>
                        <option value="<?php echo $h_option; ?>"><?php echo $h_option; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="cliente">Cliente:</label>
                <select id="cliente" name="id_tutor" required>
                    <option value="">Selecione um cliente</option>
                    <?php foreach ($tutores as $tutor_option): ?>
                        <option value="<?= htmlspecialchars($tutor_option['id_tutor']) ?>"><?= htmlspecialchars($tutor_option['nome']) ?></option>
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
                <label for="servico_form">Serviço:</label> <select id="servico_form" name="id_servico" required>
                    <option value="">Selecione um serviço</option>
                    <?php foreach ($servicos_dropdown as $servico_option): ?>
                        <option value="<?= htmlspecialchars($servico_option['id_servico']) ?>"><?= htmlspecialchars($servico_option['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-cancelar" onclick="fecharModalAgendamento()">Cancelar</button>
                <button type="submit" class="btn-salvar" id="btnSalvarForm">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .container { margin-left: 220px; padding: 80px 30px 30px; transition: margin-left 0.3s; }
    .card { background-color: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
    .header-card { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    h1 { color: #0b3556; margin: 0; }
    .btn-novo { background-color: #0b3556; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; font-weight: 500; }
    .btn-novo:hover { background-color: #0d4371; }
    .calendario-navegacao { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .btn-nav { background-color: #f0f0f0; color: #333; border: none; border-radius: 4px; padding: 8px 12px; text-decoration: none; font-size: 14px; }
    .btn-nav:hover { background-color: #e0e0e0; }
    .calendario { border: 1px solid #ddd; border-radius: 5px; overflow: hidden; }
    .dias-semana { display: grid; grid-template-columns: 80px repeat(7, 1fr); background-color: #f5f5f5; border-bottom: 1px solid #ddd; }
    .horario-header { border-right: 1px solid #ddd; }
    .dia-header { padding: 10px; text-align: center; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
    .dia-nome { font-weight: bold; font-size: 14px; }
    .dia-numero { font-size: 18px; margin-top: 5px; }
    .horarios-grid { display: grid; grid-template-columns: 80px repeat(7, 1fr); }
    .horario-celula { padding: 10px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; background-color: #f9f9f9; font-size: 14px; text-align: center; }
    .agenda-celula { min-height: 60px; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 5px; cursor: pointer; }
    .agenda-celula:hover { background-color: #f0f8ff; }
    .agendamento { background-color: #e1f5fe; border-left: 3px solid #03a9f4; padding: 5px; margin-bottom: 5px; border-radius: 3px; font-size: 12px; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
    .modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 600px; border-radius: 8px; position: relative; }
    .fechar { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    .fechar:hover { color: black; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn-cancelar { background-color: #f0f0f0; color: #333; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; }
    .btn-salvar { background-color: #0b3556; color: white; border: none; border-radius: 4px; padding: 10px 15px; cursor: pointer; }
    @media (max-width: 768px) {
        .container { margin-left: 60px; padding: 70px 15px 15px; }
        .calendario { overflow-x: auto; }
        .modal-content { width: 95%; margin: 10% auto; }
    }
    .botoes-agendamento { margin-top: 5px; display: flex; gap: 5px; }
    .btn-alterar, .btn-excluir { padding: 4px 6px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-alterar { background-color: #f0ad4e; color: white; }
    .btn-excluir { background-color: #d9534f; color: white; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let agendamentoOriginalPetId = null;

    function exibirModalAgendamento() {
        document.getElementById('modalAgendamento').style.display = 'block';
    }

    function abrirModalAgendamento(dataParam, horaParam) {
        document.getElementById('formAgendamento').reset();
        document.getElementById('id_agendamento').value = '';
        document.getElementById('modalTitulo').innerText = 'Novo Agendamento';
        document.getElementById('btnSalvarForm').innerText = 'Salvar';
        document.getElementById('id_pet').innerHTML = '<option value="">Selecione um pet</option>'; // Limpar pets
        agendamentoOriginalPetId = null; // Resetar ID do pet original

        if (dataParam && horaParam) {
            document.getElementById('data').value = dataParam;
            document.getElementById('hora').value = horaParam;
        }

        exibirModalAgendamento();
    }

    function fecharModalAgendamento() {
        document.getElementById('formAgendamento').reset();
        document.getElementById('id_agendamento').value = '';
        document.getElementById('modalTitulo').innerText = 'Novo Agendamento';
        document.getElementById('btnSalvarForm').innerText = 'Salvar';
        document.getElementById('id_pet').innerHTML = '<option value="">Selecione um pet</option>';
        agendamentoOriginalPetId = null;
        document.getElementById('modalAgendamento').style.display = 'none';
    }

    function abrirModalEdicao(idAgendamento) {
        document.getElementById('modalTitulo').innerText = 'Editar Agendamento';
        document.getElementById('btnSalvarForm').innerText = 'Atualizar';
        document.getElementById('id_agendamento').value = idAgendamento;

        fetch(`get_agendamento_details.php?id_agendamento=${idAgendamento}`)
            .then(response => {
                if (!response.ok) {
                    // Tentar ler o corpo do erro como JSON se possível
                    return response.json().then(errData => {
                        throw new Error(errData.error || `Network response was not ok: ${response.statusText}`);
                    }).catch(() => { // Se o corpo não for JSON ou houver outro erro
                        throw new Error(`Network response was not ok: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    Swal.fire('Erro', data.error, 'error');
                    return;
                }
                document.getElementById('data').value = data.data;
                document.getElementById('hora').value = data.hora.substring(0, 5); // HH:MM
                document.getElementById('cliente').value = data.id_tutor;
                document.getElementById('servico_form').value = data.id_servico; 
                document.getElementById('observacoes').value = data.observacoes;
                agendamentoOriginalPetId = data.id_pet; // Armazena o ID do pet original

                // Dispara o evento 'change' no select de cliente para carregar os pets e selecionar o pet correto
                const clienteSelect = document.getElementById('cliente');
                clienteSelect.dispatchEvent(new Event('change', { bubbles: true })); 
                // O pet será selecionado dentro do listener de 'change' do cliente após os pets serem carregados
                
                exibirModalAgendamento();
            })
            .catch(error => {
                console.error('Erro ao buscar detalhes do agendamento:', error);
                Swal.fire('Erro', `Não foi possível carregar dados para edição. ${error.message}`, 'error');
            });
    }

    document.getElementById('cliente').addEventListener('change', function() {
        const idTutor = this.value;
        const petSelect = document.getElementById('id_pet');
        petSelect.innerHTML = '<option value="">Carregando pets...</option>';
        petSelect.disabled = true;

        if (idTutor) {
            fetch("get_pets.php?id_tutor=" + idTutor)
                .then(response => {
                    if (!response.ok) {
                         return response.json().then(errData => {
                            throw new Error(errData.error || `Network response was not ok: ${response.statusText}`);
                        }).catch(() => {
                            throw new Error(`Network response was not ok: ${response.statusText}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    petSelect.innerHTML = '<option value="">Selecione um pet</option>'; // Opção padrão
                    if (data.error) { // Verifica se o backend retornou um erro específico
                        console.error('Erro retornado pelo get_pets.php:', data.error);
                        petSelect.innerHTML = `<option value="">${data.error}</option>`;
                    } else if (data.length > 0) {
                        data.forEach(pet => {
                            const option = document.createElement('option');
                            option.value = pet.id_pet;
                            option.textContent = pet.nome;
                            petSelect.appendChild(option);
                        });
                        // Se estiver editando e o pet original pertencer a este tutor, selecione-o
                        if (agendamentoOriginalPetId && data.some(pet => pet.id_pet == agendamentoOriginalPetId)) {
                            petSelect.value = agendamentoOriginalPetId;
                        } else if (agendamentoOriginalPetId) {
                            console.warn("Pet original ("+ agendamentoOriginalPetId +") não encontrado para o tutor selecionado ou não mais existe.");
                            // Não definir valor para petSelect, usuário precisará selecionar um novo.
                        }
                    } else {
                        petSelect.innerHTML = '<option value="">Nenhum pet encontrado</option>';
                    }
                    petSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Erro ao buscar pets:', error);
                    petSelect.innerHTML = `<option value="">Erro ao carregar pets (${error.message})</option>`;
                    petSelect.disabled = false;
                });
        } else {
            petSelect.innerHTML = '<option value="">Selecione um pet</option>'; // Limpa se nenhum tutor for selecionado
            petSelect.disabled = false; // Habilita, mas sem opções úteis
        }
    });

    function excluirAgendamento(id, dataAtualSemana) {
        Swal.fire({
            title: 'Tem certeza?',
            text: "Não poderá reverter esta ação!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // dataAtualSemana é a data de referência da semana atual para redirecionamento
                window.location.href = `agenda.php?excluir=${id}&data_original_semana=${encodeURIComponent(dataAtualSemana)}`;
            }
        });
    }
    
    // Fechar modal ao clicar fora dele
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('modalAgendamento')) {
            fecharModalAgendamento();
        }
    });

    // Exibir mensagens da sessão com SweetAlert
    <?php if (isset($mensagem_sessao) && isset($tipo_mensagem_sessao)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: '<?php echo ($tipo_mensagem_sessao == 'sucesso' ? 'Sucesso!' : 'Erro!'); ?>',
            text: '<?php echo addslashes(htmlspecialchars($mensagem_sessao)); // Adicionado htmlspecialchars para segurança ?>',
            icon: '<?php echo ($tipo_mensagem_sessao == 'sucesso' ? 'success' : 'error'); // CORRIGIDO AQUI ?>',
            confirmButtonText: 'Ok'
        });
    });
    <?php endif; ?>
</script>
</body>
</html>