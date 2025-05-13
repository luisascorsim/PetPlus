<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
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

// Conexão com o banco de dados
require_once('../conecta_db.php');
$conn = conecta_db();

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

// Consulta para buscar os agendamentos da semana
$dataInicio = $inicioSemana->format('Y-m-d');
$dataFim = $fimSemana->format('Y-m-d');

// Simulação de dados de agendamentos (em um sistema real, viria do banco de dados)
$agendamentos = [
    [
        'id' => 1,
        'data' => '2025-05-02',
        'hora' => '09:00',
        'cliente' => 'Maria Silva',
        'pet' => 'Rex',
        'servico' => 'Consulta de Rotina'
    ],
    [
        'id' => 2,
        'data' => '2025-05-02',
        'hora' => '14:30',
        'cliente' => 'João Pereira',
        'pet' => 'Luna',
        'servico' => 'Vacinação'
    ],
    [
        'id' => 3,
        'data' => '2025-05-03',
        'hora' => '10:00',
        'cliente' => 'Ana Costa',
        'pet' => 'Mel',
        'servico' => 'Banho e Tosa'
    ]
];
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
                            echo '<strong>' . $agendamento['cliente'] . '</strong><br>';
                            echo 'Pet: ' . $agendamento['pet'] . '<br>';
                            echo $agendamento['servico'];
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
        <form id="formAgendamento">
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
                <select id="cliente" name="cliente" required>
                    <option value="">Selecione um cliente</option>
                    <option value="1">Maria Silva</option>
                    <option value="2">João Pereira</option>
                    <option value="3">Ana Costa</option>
                    <option value="4">Carlos Oliveira</option>
                </select>
            </div>
            <div class="form-group">
                <label for="pet">Pet:</label>
                <select id="pet" name="pet" required>
                    <option value="">Selecione um pet</option>
                    <!-- Será preenchido via JavaScript quando o cliente for selecionado -->
                </select>
            </div>
            <div class="form-group">
                <label for="servico">Serviço:</label>
                <select id="servico" name="servico" required>
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
            { id: 5, nome: 'Bob (  }
        ],
        '3': [
            { id: 4, nome: 'Mel (Golden Retriever)' },
            { id: 5, nome: 'Bob (Bulldog)' }
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
    
    // Manipula o envio do formulário
    document.getElementById('formAgendamento').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Aqui você implementaria a lógica para salvar o agendamento
        alert('Agendamento salvo com sucesso!');
        fecharModalAgendamento();
        
        // Em um sistema real, você recarregaria a página ou atualizaria a visualização
        // window.location.reload();
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
