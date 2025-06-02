<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
}

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

// Lógica de mensagens da sessão
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = $_SESSION['tipo_mensagem'];
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao'])) {
        $acao = $_POST['acao'];
        
        if ($acao == 'criar_consulta') {
            // Criar nova consulta
            $pet_id = (int)$_POST['pet_id'];
            $data_consulta = $_POST['data_consulta'];
            $motivo = $_POST['motivo'];
            $observacoes = $_POST['observacoes'];
            $veterinario_id = !empty($_POST['veterinario_id']) ? (int)$_POST['veterinario_id'] : null;
            $status_co = $_POST['status_co'];
            
            if (empty($pet_id) || empty($data_consulta) || empty($motivo) || empty($status_co)) {
                $_SESSION['mensagem'] = "Todos os campos obrigatórios devem ser preenchidos.";
                $_SESSION['tipo_mensagem'] = "erro";
            } else {
                $conn->begin_transaction();
                
                try {
                    // Inserir consulta
                    $sql = "INSERT INTO consultas (pet_id, data_consulta, motivo, observacoes, veterinario_id, status_co) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("isssis", $pet_id, $data_consulta, $motivo, $observacoes, $veterinario_id, $status_co);
                    $stmt->execute();
                    $consulta_id = $conn->insert_id;
                    
                    // Se o status for "concluida", adiciona ao prontuário automaticamente
                    if ($status_co === 'concluida') {
                        $sql_prontuario = "INSERT INTO Prontuarios (consulta_id, pet_id, data_P, descricao) VALUES (?, ?, ?, ?)";
                        $stmt_prontuario = $conn->prepare($sql_prontuario);
                        $descricao_prontuario = "Consulta: " . $motivo . "\nObservações: " . $observacoes;
                        $stmt_prontuario->bind_param("iiss", $consulta_id, $pet_id, $data_consulta, $descricao_prontuario);
                        $stmt_prontuario->execute();
                    }
                    
                    $conn->commit();
                    
                    $_SESSION['mensagem'] = "Consulta registrada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['mensagem'] = "Erro ao registrar consulta: " . $e->getMessage();
                    $_SESSION['tipo_mensagem'] = "erro";
                }
            }
            
            header("Location: historico-consultas.php");
            exit();
            
        } elseif ($acao == 'editar_consulta') {
            // Editar consulta existente
            $id = (int)$_POST['id'];
            $pet_id = (int)$_POST['pet_id'];
            $data_consulta = $_POST['data_consulta'];
            $motivo = $_POST['motivo'];
            $diagnostico = $_POST['diagnostico'];
            $tratamento = $_POST['tratamento'];
            $observacoes = $_POST['observacoes'];
            $veterinario_id = !empty($_POST['veterinario_id']) ? (int)$_POST['veterinario_id'] : null;
            $status_co = $_POST['status_co'];
            
            $conn->begin_transaction();
            
            try {
                // Buscar status anterior
                $sql_status = "SELECT status_co FROM consultas WHERE id = ?";
                $stmt_status = $conn->prepare($sql_status);
                $stmt_status->bind_param("i", $id);
                $stmt_status->execute();
                $result_status = $stmt_status->get_result();
                $status_anterior = $result_status->fetch_assoc()['status_co'];
                
                // Atualizar consulta
                $sql = "UPDATE consultas SET pet_id = ?, data_consulta = ?, motivo = ?, diagnostico = ?, tratamento = ?, observacoes = ?, veterinario_id = ?, status_co = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssiss", $pet_id, $data_consulta, $motivo, $diagnostico, $tratamento, $observacoes, $veterinario_id, $status_co, $id);
                $stmt->execute();
                
                // Se mudou para "concluida" e não estava antes, adiciona ao prontuário
                if ($status_co === 'concluida' && $status_anterior !== 'concluida') {
                    // Verificar se já existe prontuário para esta consulta
                    $sql_check = "SELECT id_prontuario FROM Prontuarios WHERE consulta_id = ?";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->bind_param("i", $id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows == 0) {
                        $sql_prontuario = "INSERT INTO Prontuarios (consulta_id, pet_id, data_P, descricao) VALUES (?, ?, ?, ?)";
                        $stmt_prontuario = $conn->prepare($sql_prontuario);
                        $descricao_prontuario = "Consulta: " . $motivo;
                        if (!empty($diagnostico)) $descricao_prontuario .= "\nDiagnóstico: " . $diagnostico;
                        if (!empty($tratamento)) $descricao_prontuario .= "\nTratamento: " . $tratamento;
                        if (!empty($observacoes)) $descricao_prontuario .= "\nObservações: " . $observacoes;
                        $stmt_prontuario->bind_param("iiss", $id, $pet_id, $data_consulta, $descricao_prontuario);
                        $stmt_prontuario->execute();
                    }
                }
                
                $conn->commit();
                
                $_SESSION['mensagem'] = "Consulta atualizada com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['mensagem'] = "Erro ao atualizar consulta: " . $e->getMessage();
                $_SESSION['tipo_mensagem'] = "erro";
            }
            
            header("Location: historico-consultas.php");
            exit();
            
        } elseif ($acao == 'editar_prontuario') {
            // Editar prontuário
            $id = (int)$_POST['id'];
            $data = $_POST['data_P'];
            $descricao = $_POST['descricao'];
            
            try {
                $sql = "UPDATE Prontuarios SET data_P = ?, descricao = ? WHERE id_prontuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $data_P, $descricao, $id);
                $stmt->execute();
                
                $_SESSION['mensagem'] = "Prontuário atualizado com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                
            } catch (Exception $e) {
                $_SESSION['mensagem'] = "Erro ao atualizar prontuário: " . $e->getMessage();
                $_SESSION['tipo_mensagem'] = "erro";
            }
            
            header("Location: historico-consultas.php");
            exit();
        }
    }
}

// Lógica de Exclusão
if (isset($_GET['excluir_consulta']) && is_numeric($_GET['excluir_consulta'])) {
    $id_consulta = (int)$_GET['excluir_consulta'];
    
    $conn->begin_transaction();
    
    try {
        // Excluir prontuários relacionados primeiro
        $sql_prontuario = "DELETE FROM Prontuarios WHERE consulta_id = ?";
        $stmt_prontuario = $conn->prepare($sql_prontuario);
        $stmt_prontuario->bind_param("i", $id_consulta);
        $stmt_prontuario->execute();
        
        // Excluir consulta
        $sql = "DELETE FROM consultas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_consulta);
        $stmt->execute();
        
        $conn->commit();
        
        $_SESSION['mensagem'] = "Consulta excluída com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao excluir consulta: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
    
    header("Location: historico-consultas.php");
    exit();
}

if (isset($_GET['excluir_prontuario']) && is_numeric($_GET['excluir_prontuario'])) {
    $id_prontuario = (int)$_GET['excluir_prontuario'];
    
    try {
        $sql = "DELETE FROM Prontuarios WHERE id_prontuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_prontuario);
        $stmt->execute();
        
        $_SESSION['mensagem'] = "Prontuário excluído com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao excluir prontuário: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
    
    header("Location: historico-consultas.php");
    exit();
}

// Busca todos os pets para o dropdown
$query = "SELECT p.id_pet, p.nome, t.nome as nome_tutor 
        FROM Pets p 
        JOIN Tutor t ON p.id_tutor = t.id_tutor 
        ORDER BY p.nome";
$result = $conn->query($query);
$pets = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Busca todos os veterinários para o dropdown
$query_vet = "SELECT id_usuario, nome FROM Usuarios WHERE tipo_usuario = 'veterinario' ORDER BY nome";
$result_vet = $conn->query($query_vet);
$veterinarios = [];
if ($result_vet && $result_vet->num_rows > 0) {
    while ($row = $result_vet->fetch_assoc()) {
        $veterinarios[] = $row;
    }
}

// Busca todas as consultas
$consultas = [];
try {
    $sql = "SELECT c.*, p.nome as pet_nome, t.nome as tutor_nome, u.nome as veterinario_nome 
            FROM consultas c
            JOIN Pets p ON c.pet_id = p.id_pet
            JOIN Tutor t ON p.id_tutor = t.id_tutor
            LEFT JOIN Usuarios u ON c.veterinario_id = u.id_usuario
            ORDER BY c.data_consulta DESC";
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
    $sql = "SELECT pr.*, p.nome as pet_nome, t.nome as tutor_nome, c.motivo as consulta_motivo
            FROM Prontuarios pr
            JOIN Pets p ON pr.pet_id = p.id_pet
            JOIN Tutor t ON p.id_tutor = t.id_tutor
            LEFT JOIN consultas c ON pr.consulta_id = c.id
            ORDER BY pr.data_P DESC";
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

// Buscar dados para edição se solicitado
$consulta_edicao = null;
$prontuario_edicao = null;

if (isset($_GET['editar_consulta']) && is_numeric($_GET['editar_consulta'])) {
    $id_consulta = (int)$_GET['editar_consulta'];
    $sql = "SELECT * FROM consultas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_consulta);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $consulta_edicao = $result->fetch_assoc();
    }
}

if (isset($_GET['editar_prontuario']) && is_numeric($_GET['editar_prontuario'])) {
    $id_prontuario = (int)$_GET['editar_prontuario'];
    $sql = "SELECT * FROM Prontuarios WHERE id_prontuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_prontuario);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $prontuario_edicao = $result->fetch_assoc();
    }
}

// Agora inclui o header e sidebar após toda a lógica
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Consultas - PetPlus</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            text-decoration: none;
            display: inline-block;
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
            font-size: 12px;
            cursor: pointer;
            border: none;
        }
        
        .btn-edit {
            background-color: #2196f3;
        }
        
        .btn-delete {
            background-color: #f44336;
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
        
        .status-em_andamento {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-concluida {
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
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Gerenciamento de Consultas</h1>
            
            <div class="tabs">
                <div class="tab active" onclick="showTab('consultas')">Consultas e Agenda</div>
                <div class="tab" onclick="showTab('prontuarios')">Prontuários</div>
                <div class="tab" onclick="showTab('novo')">Novo Agendamento</div>
            </div>
            
            <div id="tab-consultas" class="tab-content active">
                <h2>Histórico de Consultas</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pet</th>
                            <th>Tutor</th>
                            <th>Data</th>
                            <th>Motivo</th>
                            <th>Veterinário</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consultas)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">Nenhuma consulta registrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($consultas as $consulta): ?>
                                <tr>
                                    <td><?php echo $consulta['id']; ?></td>
                                    <td><?php echo htmlspecialchars($consulta['pet_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['tutor_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($consulta['data_consulta'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($consulta['motivo'], 0, 30)) . (strlen($consulta['motivo']) > 30 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($consulta['veterinario_nome'] ?? 'Não definido'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $consulta['status_co']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $consulta['status_co'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editarConsulta(<?php echo $consulta['id']; ?>)">Editar</button>
                                        <button class="btn-action btn-delete" onclick="confirmarExclusaoConsulta(<?php echo $consulta['id']; ?>)">Excluir</button>
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
                            <th>Diagnóstico</th>
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
                                    <td><?php echo $prontuario['id_prontuario']; ?></td>
                                    <td><?php echo htmlspecialchars($prontuario['pet_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($prontuario['tutor_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($prontuario['data_P'])); ?></td>
                                    <td><?php echo htmlspecialchars(substr($prontuario['descricao'], 0, 50)) . (strlen($prontuario['descricao']) > 50 ? '...' : ''); ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editarProntuario(<?php echo $prontuario['id_prontuario']; ?>)">Editar</button>
                                        <button class="btn-action btn-delete" onclick="confirmarExclusaoProntuario(<?php echo $prontuario['id_prontuario']; ?>)">Excluir</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="tab-novo" class="tab-content">
                <h2><?php echo $consulta_edicao ? 'Editar Consulta' : ($prontuario_edicao ? 'Editar Prontuário' : 'Novo Agendamento'); ?></h2>
                
                <?php if ($consulta_edicao): ?>
                    <!-- Formulário de Edição de Consulta -->
                    <form action="historico-consultas.php" method="POST">
                        <input type="hidden" name="acao" value="editar_consulta">
                        <input type="hidden" name="id" value="<?php echo $consulta_edicao['id']; ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="pet_id">Pet*</label>
                                <select id="pet_id" name="pet_id" required>
                                    <option value="">Selecione um pet</option>
                                    <?php foreach ($pets as $pet): ?>
                                        <option value="<?php echo $pet['id_pet']; ?>" <?php echo $consulta_edicao['pet_id'] == $pet['id_pet'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pet['nome']) . ' (' . htmlspecialchars($pet['nome_tutor']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_consulta">Data e Hora da Consulta*</label>
                                <input type="datetime-local" id="data_consulta" name="data_consulta" value="<?php echo date('Y-m-d\TH:i', strtotime($consulta_edicao['data_consulta'])); ?>" required />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="motivo">Motivo*</label>
                            <textarea id="motivo" name="motivo" rows="3" required><?php echo htmlspecialchars($consulta_edicao['motivo']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="diagnostico">Diagnóstico</label>
                            <textarea id="diagnostico" name="diagnostico" rows="3"><?php echo htmlspecialchars($consulta_edicao['diagnostico'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="tratamento">Tratamento</label>
                            <textarea id="tratamento" name="tratamento" rows="3"><?php echo htmlspecialchars($consulta_edicao['tratamento'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="observacoes">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($consulta_edicao['observacoes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="veterinario_id">Veterinário</label>
                                <select id="veterinario_id" name="veterinario_id">
                                    <option value="">Selecione um veterinário</option>
                                    <?php foreach ($veterinarios as $vet): ?>
                                        <option value="<?php echo $vet['id_usuario']; ?>" <?php echo $consulta_edicao['veterinario_id'] == $vet['id_usuario'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vet['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status_co">Status*</label>
                                <select id="status_co" name="status_co" required>
                                    <option value="agendada" <?php echo $consulta_edicao['status_co'] == 'agendada' ? 'selected' : ''; ?>>Agendada</option>
                                    <option value="em_andamento" <?php echo $consulta_edicao['status_co'] == 'em_andamento' ? 'selected' : ''; ?>>Em andamento</option>
                                    <option value="concluida" <?php echo $consulta_edicao['status_co'] == 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                                    <option value="cancelada" <?php echo $consulta_edicao['status_co'] == 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">Atualizar Consulta</button>
                        <a href="historico-consultas.php" class="btn-secondary">Cancelar</a>
                    </form>
                    
                <?php elseif ($prontuario_edicao): ?>
                    <!-- Formulário de Edição de Prontuário -->
                    <form action="historico-consultas.php" method="POST">
                        <input type="hidden" name="acao" value="editar_prontuario">
                        <input type="hidden" name="id" value="<?php echo $prontuario_edicao['id_prontuario']; ?>">
                        
                        <div class="form-group">
                            <label for="data">Data e Hora*</label>
                            <input type="datetime-local" id="data_P" name="data_P" value="<?php echo date('Y-m-d\TH:i', strtotime($prontuario_edicao['data_P'])); ?>" required />
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao">Descrição*</label>
                            <textarea id="descricao" name="descricao" rows="6" required><?php echo htmlspecialchars($prontuario_edicao['descricao']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-primary">Atualizar Prontuário</button>
                        <a href="historico-consultas.php" class="btn-secondary">Cancelar</a>
                    </form>
                    
                <?php else: ?>
                    <!-- Formulário de Nova Consulta -->
                    <form action="historico-consultas.php" method="POST">
                        <input type="hidden" name="acao" value="criar_consulta">
                        
                        <div class="form-row">
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
                                <label for="data_consulta">Data e Hora da Consulta*</label>
                                <input type="datetime-local" id="data_consulta" name="data_consulta" required />
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="motivo">Motivo/Descrição*</label>
                            <textarea id="motivo" name="motivo" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="observacoes">Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="veterinario_id">Veterinário</label>
                                <select id="veterinario_id" name="veterinario_id">
                                    <option value="">Selecione um veterinário</option>
                                    <?php foreach ($veterinarios as $vet): ?>
                                        <option value="<?php echo $vet['id_usuario']; ?>">
                                            <?php echo htmlspecialchars($vet['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status_co">Status*</label>
                                <select id="status_co" name="status_co" required>
                                    <option value="agendada">Agendada</option>
                                    <option value="em_andamento">Em andamento</option>
                                    <option value="concluida">Concluída</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-primary">Agendar Consulta</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Mostrar mensagens com SweetAlert2
        <?php if ($mensagem): ?>
            Swal.fire({
                icon: '<?php echo $tipo_mensagem === "sucesso" ? "success" : "error"; ?>',
                title: '<?php echo $tipo_mensagem === "sucesso" ? "Sucesso!" : "Erro!"; ?>',
                text: '<?php echo addslashes($mensagem); ?>',
                confirmButtonColor: '#0b3556'
            });
        <?php endif; ?>
        
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
            window.location.href = 'historico-consultas.php?editar_consulta=' + id;
        }
        
        function editarProntuario(id) {
            window.location.href = 'historico-consultas.php?editar_prontuario=' + id;
        }
        
        function confirmarExclusaoConsulta(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não pode ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'historico-consultas.php?excluir_consulta=' + id;
                }
            });
        }
        
        function confirmarExclusaoProntuario(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não pode ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'historico-consultas.php?excluir_prontuario=' + id;
                }
            });
        }
        
        // Verificar se há parâmetros de edição na URL e mostrar a aba correta
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('editar_consulta') || urlParams.has('editar_prontuario')) {
                showTab('novo');
            }
        });
    </script>
</body>
</html>
