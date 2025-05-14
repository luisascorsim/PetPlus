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

// Inclui o header e a barra lateral
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Busca todos os pets para o dropdown
$query = "SELECT p.id_pet, p.nome, t.nome as nome_tutor 
        FROM Pet p 
        JOIN Tutor t ON p.id_tutor = t.id_tutor 
        ORDER BY p.nome";
$result = $conn->query($query);
$pets = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica qual formulário foi enviado
    if (isset($_POST['form_tipo']) && $_POST['form_tipo'] == 'consulta') {
        // Formulário de consulta
        $pet_id = (int)$_POST['pet_id'];
        $data = $_POST['data'];
        $descricao = $_POST['descricao'];
        $status = $_POST['status'];
        $peso = isset($_POST['peso']) ? (float)$_POST['peso'] : null;
        
        // Validações
        if (empty($pet_id) || empty($data) || empty($descricao) || empty($status)) {
            $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
            $tipo_mensagem = "erro";
        } else {
            // Inicia uma transação
            $conn->begin_transaction();
            
            try {
                // Inserir consulta
                $sql = "INSERT INTO Consultas (pet_id, data, descricao, status) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $pet_id, $data, $descricao, $status);
                $stmt->execute();
                $consulta_id = $conn->insert_id;
                
                // Se tiver peso, registra também
                if ($peso !== null && $peso > 0) {
                    $sql_peso = "INSERT INTO pesos (pet_id, data, peso) VALUES (?, ?, ?)";
                    $stmt_peso = $conn->prepare($sql_peso);
                    $stmt_peso->bind_param("isd", $pet_id, $data, $peso);
                    $stmt_peso->execute();
                }
                
                // Se o status for "concluída", adiciona ao prontuário
                if ($status === 'concluída') {
                    $sql_prontuario = "INSERT INTO prontuarios (consulta_id, pet_id, data, descricao) VALUES (?, ?, ?, ?)";
                    $stmt_prontuario = $conn->prepare($sql_prontuario);
                    $stmt_prontuario->bind_param("iiss", $consulta_id, $pet_id, $data, $descricao);
                    $stmt_prontuario->execute();
                }
                
                // Confirma a transação
                $conn->commit();
                
                $mensagem = "Consulta registrada com sucesso!";
                $tipo_mensagem = "sucesso";
                
                // Limpa os dados do formulário após um cadastro bem-sucedido
                $_POST = array();
            } catch (Exception $e) {
                // Reverte a transação em caso de erro
                $conn->rollback();
                
                $mensagem = "Erro ao registrar consulta: " . $e->getMessage();
                $tipo_mensagem = "erro";
            }
        }
    }
}

// Busca todas as consultas
$consultas = [];
try {
    $sql = "SELECT c.*, p.nome as pet_nome, t.nome as tutor_nome 
            FROM Consultas c
            JOIN Pet p ON c.pet_id = p.id_pet
            JOIN Tutor t ON p.id_tutor = t.id_tutor
            ORDER BY c.data DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $consultas[] = $row;
        }
    }
} catch (Exception $e) {
    $mensagem = "Erro ao buscar consultas: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

// Busca todos os prontuários
$prontuarios = [];
try {
    $sql = "SELECT pr.*, p.nome as pet_nome, t.nome as tutor_nome 
            FROM prontuarios pr
            JOIN Pet p ON pr.pet_id = p.id_pet
            JOIN Tutor t ON p.id_tutor = t.id_tutor
            ORDER BY pr.data DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $prontuarios[] = $row;
        }
    }
} catch (Exception $e) {
    $mensagem = "Erro ao buscar prontuários: " . $e->getMessage();
    $tipo_mensagem = "erro";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Consultas - PetPlus</title>
    <style>
        .container {
            margin-left: 220px;
            padding: 80px 30px 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #0b3556;
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
            font-weight: 500;
        }
        
        .tab.active {
            background-color: #0b3556;
            color: white;
            border-color: #0b3556;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn-primary {
            background-color: #0b3556;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table th,
        table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        table th {
            background-color: #f2f2f2;
        }
        
        .btn-action {
            display: inline-block;
            padding: 5px 10px;
            margin-right: 5px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }
        
        .btn-edit {
            background-color: #2196f3;
        }
        
        .btn-delete {
            background-color: #f44336;
        }
        
        .mensagem {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .mensagem-sucesso {
            background-color: #d4edda;
            color: #155724;
        }
        
        .mensagem-erro {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        
        .status-agendada {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-em-andamento {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-concluída {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelada {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .container {
                margin-left: 60px;
                padding: 70px 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Gerenciamento de Consultas</h1>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <div class="tab active" onclick="showTab('consultas')">Consultas e Agenda</div>
                <div class="tab" onclick="showTab('prontuarios')">Prontuários</div>
                <div class="tab" onclick="showTab('novo')">Novo Agendamento</div>
            </div>
            
            <div id="tab-consultas" class="tab-content active">
                <h2>Consultas Agendadas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Tutor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhuma consulta registrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultas as $consulta): ?>
                                <tr>
                                    <td><?php echo $consulta['id']; ?></td>
                                    <td><?php echo htmlspecialchars($consulta['pet_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['tutor_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($consulta['data'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $consulta['status']; ?>">
                                            <?php echo ucfirst($consulta['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($consulta['descricao'], 0, 50)) . (strlen($consulta['descricao']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editarConsulta(<?php echo $consulta['id']; ?>)">Editar</button>
                                        <button class="btn-action btn-delete" onclick="confirmarExclusao(<?php echo $consulta['id']; ?>)">Excluir</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="tab-prontuarios" class="tab-content">
                <h2>Prontuários</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Tutor</th>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prontuarios)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum prontuário registrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prontuarios as $prontuario): ?>
                                <tr>
                                    <td><?php echo $prontuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($prontuario['pet_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($prontuario['tutor_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($prontuario['data'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($prontuario['descricao'], 0, 50)) . (strlen($prontuario['descricao']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="visualizarProntuario(<?php echo $prontuario['id']; ?>)">Visualizar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="tab-novo" class="tab-content">
                <h2>Novo Agendamento</h2>
                <form action="historico-consultas.php" method="POST">
                    <input type="hidden" name="form_tipo" value="consulta">
                    
                    <div class="form-group">
                        <label for="pet_id">Pet*</label>
                        <select id="pet_id" name="pet_id" required>
                            <option value="">Selecione um pet</option>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?php echo $pet['id_pet']; ?>">
                                    <?php echo htmlspecialchars($pet['nome']) . ' (' . htmlspecialchars($pet['nome_tutor']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="data">Data e Hora da Consulta*</label>
                        <input type="datetime-local" id="data" name="data" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição/Motivo*</label>
                        <textarea id="descricao" name="descricao" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="peso">Peso do Pet (kg)</label>
                        <input type="number" id="peso" name="peso" min="0" step="0.01" />
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status*</label>
                        <select id="status" name="status" required>
                            <option value="agendada">Agendada</option>
                            <option value="em andamento">Em andamento</option>
                            <option value="concluída">Concluída</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">Agendar Consulta</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Esconde todas as abas
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostra a aba selecionada
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Atualiza o estado ativo das abas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Encontra a aba clicada e adiciona a classe active
            document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');
        }
        
        function editarConsulta(id) {
            // Redirecionar para a página de edição ou abrir um modal
            alert('Editar consulta ' + id);
            // Em um sistema real, você redirecionaria para uma página de edição
            // window.location.href = 'editar_consulta.php?id=' + id;
        }
        
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta consulta?')) {
                // Em um sistema real, você enviaria uma requisição para excluir
                // window.location.href = 'historico-consultas.php?excluir=' + id;
                alert('Consulta ' + id + ' excluída com sucesso!');
            }
        }
        
        function visualizarProntuario(id) {
            // Redirecionar para a página de visualização do prontuário
            alert('Visualizar prontuário ' + id);
            // Em um sistema real, você redirecionaria para uma página de visualização
            // window.location.href = 'visualizar_prontuario.php?id=' + id;
        }
    </script>
</body>
</html>
