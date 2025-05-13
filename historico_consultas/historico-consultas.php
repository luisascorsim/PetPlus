<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header e a barra lateral
require_once('../includes/header.php');
require_once('../includes/sidebar.php');

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Verifica se a tabela Consultas existe, se não, cria
$check_table = "SHOW TABLES LIKE 'Consultas'";
$result = $conn->query($check_table);
if ($result->num_rows == 0) {
    // Tabela não existe, vamos criá-la
    $create_table = "CREATE TABLE IF NOT EXISTS Consultas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pet_id INT NOT NULL,
        data DATETIME NOT NULL,
        descricao TEXT,
        status ENUM('agendada', 'em andamento', 'concluída', 'cancelada') DEFAULT 'agendada',
        FOREIGN KEY (pet_id) REFERENCES Pet(id_pet) ON DELETE CASCADE
    )";
    $conn->query($create_table);
}

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se é uma edição ou um novo registro
    $id_consulta = isset($_POST['id_consulta']) && !empty($_POST['id_consulta']) ? (int)$_POST['id_consulta'] : null;
    
    $pet_id = (int)$_POST['pet_id'];
    $data = $_POST['data'];
    $descricao = $_POST['descricao'];
    $status = $_POST['status'];
    
    // Validações
    if (empty($pet_id) || empty($data) || empty($descricao) || empty($status)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
        $tipo_mensagem = "erro";
    } else {
        if ($id_consulta) {
            // Atualizar registro existente
            $sql = "UPDATE Consultas SET pet_id = ?, data = ?, descricao = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssi", $pet_id, $data, $descricao, $status, $id_consulta);
        } else {
            // Inserir novo registro
            $sql = "INSERT INTO Consultas (pet_id, data, descricao, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $pet_id, $data, $descricao, $status);
        }
        
        if ($stmt->execute()) {
            $mensagem = $id_consulta ? "Consulta atualizada com sucesso!" : "Consulta registrada com sucesso!";
            $tipo_mensagem = "sucesso";
            
            // Limpa os dados do formulário após um cadastro bem-sucedido
            if (!$id_consulta) {
                $_POST = array();
            }
        } else {
            $mensagem = "Erro ao " . ($id_consulta ? "atualizar" : "registrar") . " consulta: " . $stmt->error;
            $tipo_mensagem = "erro";
        }
        
        $stmt->close();
    }
}

// Exclui uma consulta se solicitado
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_consulta = (int)$_GET['excluir'];
    
    $sql = "DELETE FROM Consultas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_consulta);
    
    if ($stmt->execute()) {
        $mensagem = "Consulta excluída com sucesso!";
        $tipo_mensagem = "sucesso";
    } else {
        $mensagem = "Erro ao excluir consulta: " . $stmt->error;
        $tipo_mensagem = "erro";
    }
    
    $stmt->close();
}

// Busca todos os pets para o select
$sql = "SELECT p.id_pet, p.nome, t.nome as nome_tutor 
        FROM Pet p 
        JOIN Tutor t ON p.id_tutor = t.id_tutor 
        ORDER BY p.nome";
$result = $conn->query($sql);
$pets = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Busca todas as consultas
$consultas = [];
try {
    // Verificar se a tabela existe e criar se não existir
$check_table = "SHOW TABLES LIKE 'consultas'";
$table_exists = $conn->query($check_table);

if ($table_exists->num_rows == 0) {
    // Criar a tabela consultas
    $create_table = "CREATE TABLE IF NOT EXISTS `consultas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `pet_id` int(11) NOT NULL,
        `data_consulta` datetime NOT NULL,
        `motivo` varchar(255) NOT NULL,
        `diagnostico` text,
        `tratamento` text,
        `observacoes` text,
        `veterinario_id` int(11) DEFAULT NULL,
        `status` enum('agendada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'agendada',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `pet_id` (`pet_id`),
        KEY `veterinario_id` (`veterinario_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->query($create_table);
    
    // Inserir alguns dados de exemplo
    $insert_data = "INSERT INTO `consultas` (`pet_id`, `data_consulta`, `motivo`, `diagnostico`, `tratamento`, `observacoes`, `veterinario_id`, `status`)
    SELECT 
        (SELECT id FROM pets ORDER BY id LIMIT 1), 
        '2023-06-15 10:00:00', 
        'Checkup anual', 
        'Pet saudável', 
        'Nenhum tratamento necessário', 
        'Continuar com a dieta atual', 
        (SELECT id FROM veterinarios ORDER BY id LIMIT 1), 
        'concluida'
    FROM dual
    WHERE EXISTS (SELECT 1 FROM pets LIMIT 1) AND EXISTS (SELECT 1 FROM veterinarios LIMIT 1)";
    
    $conn->query($insert_data);
}

$sql = "SELECT c.*, p.nome as pet_nome, cl.nome as cliente_nome, v.nome as veterinario_nome 
        FROM consultas c
        JOIN pets p ON c.pet_id = p.id
        JOIN clientes cl ON p.cliente_id = cl.id
        LEFT JOIN veterinarios v ON c.veterinario_id = v.id
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

// Busca uma consulta específica para edição
$consulta_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_consulta = (int)$_GET['editar'];
    
    $sql = "SELECT * FROM Consultas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_consulta);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $consulta_edicao = $result->fetch_assoc();
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Consultas - PetPlus</title>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Histórico de Consultas</h1>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <form action="historico-consultas.php" method="POST">
                <input type="hidden" id="id_consulta" name="id_consulta" value="<?php echo $consulta_edicao ? $consulta_edicao['id'] : ''; ?>">
                
                <div class="form-group">
                    <label for="pet_id">Pet*</label>
                    <select id="pet_id" name="pet_id" required>
                        <option value="">Selecione um pet</option>
                        <?php foreach ($pets as $pet): ?>
                            <option value="<?php echo $pet['id_pet']; ?>" <?php echo ($consulta_edicao && $consulta_edicao['pet_id'] == $pet['id_pet']) || (isset($_POST['pet_id']) && $_POST['pet_id'] == $pet['id_pet']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($pet['nome']) . ' (' . htmlspecialchars($pet['nome_tutor']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data">Data da Consulta*</label>
                    <input type="date" id="data" name="data" value="<?php echo $consulta_edicao ? $consulta_edicao['data'] : (isset($_POST['data']) ? $_POST['data'] : date('Y-m-d')); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição*</label>
                    <textarea id="descricao" name="descricao" rows="4" required><?php echo $consulta_edicao ? htmlspecialchars($consulta_edicao['descricao']) : (isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="status">Status*</label>
                    <select id="status" name="status" required>
                        <option value="">Selecione</option>
                        <option value="agendada" <?php echo ($consulta_edicao && $consulta_edicao['status'] == 'agendada') || (isset($_POST['status']) && $_POST['status'] == 'agendada') ? 'selected' : ''; ?>>Agendada</option>
                        <option value="em andamento" <?php echo ($consulta_edicao && $consulta_edicao['status'] == 'em andamento') || (isset($_POST['status']) && $_POST['status'] == 'em andamento') ? 'selected' : ''; ?>>Em andamento</option>
                        <option value="concluída" <?php echo ($consulta_edicao && $consulta_edicao['status'] == 'concluída') || (isset($_POST['status']) && $_POST['status'] == 'concluída') ? 'selected' : ''; ?>>Concluída</option>
                        <option value="cancelada" <?php echo ($consulta_edicao && $consulta_edicao['status'] == 'cancelada') || (isset($_POST['status']) && $_POST['status'] == 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary"><?php echo $consulta_edicao ? 'Atualizar Consulta' : 'Registrar Consulta'; ?></button>
                
                <?php if ($consulta_edicao): ?>
                    <a href="historico-consultas.php" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
            
            <h2>Consultas Registradas</h2>
            
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
                                <td><?php echo htmlspecialchars($consulta['nome_pet']); ?></td>
                                <td><?php echo htmlspecialchars($consulta['nome_tutor']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($consulta['data'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $consulta['status']; ?>">
                                        <?php echo ucfirst($consulta['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(substr($consulta['descricao'], 0, 50)) . (strlen($consulta['descricao']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="historico-consultas.php?editar=<?php echo $consulta['id']; ?>" class="btn-action btn-edit">Editar</a>
                                    <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $consulta['id']; ?>)" class="btn-action btn-delete">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
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
        
        .status-em.andamento {
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
    
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir esta consulta?')) {
                window.location.href = 'historico-consultas.php?excluir=' + id;
            }
        }
    </script>
</body>
</html>
