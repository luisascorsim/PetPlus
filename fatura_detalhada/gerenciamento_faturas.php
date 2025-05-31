<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'fatura';
$page_title = 'Fatura Detalhada';

// Verificar se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
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
        
        if ($acao == 'criar_fatura') {
            // Criar nova fatura
            $tutor_id = (int)$_POST['tutor_id'];
            $pet_id = (int)$_POST['pet_id'];
            $data_fatura = $_POST['dataFatura'];
            $servico = (int)$_POST['servico'];
            $valor = (float)$_POST['valor'];
            $forma_pagamento = $_POST['pagamento'];
            $observacoes = $_POST['observacoes'];
            $clinica = $_POST['clinica'];
            $profissional = $_POST['profissional'];
            
            if (empty($tutor_id) || empty($pet_id) || empty($data_fatura) || empty($servico) || empty($valor) || empty($forma_pagamento)) {
                $_SESSION['mensagem'] = "Todos os campos obrigatórios devem ser preenchidos.";
                $_SESSION['tipo_mensagem'] = "erro";
            } else {
                try {
                    $sql = "INSERT INTO Faturas (tutor_id, pet_id, data_fatura, servico, valor, forma_pagamento, observacoes, clinica, profissional) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisisssss", $tutor_id, $pet_id, $data_fatura, $servico, $valor, $forma_pagamento, $observacoes, $clinica, $profissional);
                    $stmt->execute();
                    
                    $_SESSION['mensagem'] = "Fatura cadastrada com sucesso!";
                    $_SESSION['tipo_mensagem'] = "sucesso";
                    
                } catch (Exception $e) {
                    $_SESSION['mensagem'] = "Erro ao cadastrar fatura: " . $e->getMessage();
                    $_SESSION['tipo_mensagem'] = "erro";
                }
            }
            
            header("Location: gerenciamento_faturas.php");
            exit();
            
        } elseif ($acao == 'editar_fatura') {
            // Editar fatura existente
            $id = (int)$_POST['id'];
            $tutor_id = (int)$_POST['tutor_id'];
            $pet_id = (int)$_POST['pet_id'];
            $data_fatura = $_POST['dataFatura'];
            $servico = (int)$_POST['servico'];
            $valor = (float)$_POST['valor'];
            $forma_pagamento = $_POST['pagamento'];
            $observacoes = $_POST['observacoes'];
            $clinica = $_POST['clinica'];
            $profissional = $_POST['profissional'];
            
            try {
                $sql = "UPDATE Faturas SET tutor_id = ?, pet_id = ?, data_fatura = ?, servico = ?, valor = ?, forma_pagamento = ?, observacoes = ?, clinica = ?, profissional = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisisssssi", $tutor_id, $pet_id, $data_fatura, $servico, $valor, $forma_pagamento, $observacoes, $clinica, $profissional, $id);
                $stmt->execute();
                
                $_SESSION['mensagem'] = "Fatura atualizada com sucesso!";
                $_SESSION['tipo_mensagem'] = "sucesso";
                
            } catch (Exception $e) {
                $_SESSION['mensagem'] = "Erro ao atualizar fatura: " . $e->getMessage();
                $_SESSION['tipo_mensagem'] = "erro";
            }
            
            header("Location: gerenciamento_faturas.php");
            exit();
        }
    }
}

// Lógica de Exclusão
if (isset($_GET['excluir_fatura']) && is_numeric($_GET['excluir_fatura'])) {
    $id = (int)$_GET['excluir_fatura'];
    
    try {
        $sql = "DELETE FROM Faturas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $_SESSION['mensagem'] = "Fatura excluída com sucesso!";
        $_SESSION['tipo_mensagem'] = "sucesso";
    } catch (Exception $e) {
        $_SESSION['mensagem'] = "Erro ao excluir fatura: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = "erro";
    }
    
    header("Location: gerenciamento_faturas.php");
    exit();
}

// Buscar tutores para o dropdown
$tutores = [];
$sql_tutores = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$result_tutores = $conn->query($sql_tutores);
if ($result_tutores && $result_tutores->num_rows > 0) {
    while ($row = $result_tutores->fetch_assoc()) {
        $tutores[] = $row;
    }
}

// Buscar serviços para o dropdown
$servicos = [];
$sql_servicos = "SELECT id_servico, nome, preco FROM Servicos ORDER BY nome";
$result_servicos = $conn->query($sql_servicos);
if ($result_servicos && $result_servicos->num_rows > 0) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos[] = $row;
    }
} else {
    // Dados de exemplo se a tabela não existir
    $servicos = [
        ['id_servico' => 1, 'nome' => 'Consulta de Rotina', 'preco' => 120.00],
        ['id_servico' => 2, 'nome' => 'Vacinação', 'preco' => 80.00],
        ['id_servico' => 3, 'nome' => 'Banho e Tosa', 'preco' => 70.00],
        ['id_servico' => 4, 'nome' => 'Exames Laboratoriais', 'preco' => 150.00],
        ['id_servico' => 5, 'nome' => 'Cirurgia de Castração', 'preco' => 350.00]
    ];
}

// Buscar todas as faturas
$faturas = [];
try {
    $sql = "SELECT f.*, t.nome as tutor_nome, p.nome as pet_nome, s.nome as servico_nome 
            FROM Faturas f
            JOIN Tutor t ON f.tutor_id = t.id_tutor
            JOIN Pets p ON f.pet_id = p.id_pet
            LEFT JOIN Servicos s ON f.servico = s.id_servico
            ORDER BY f.data_fatura DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $faturas[] = $row;
        }
    }
} catch (Exception $e) {
    // Se a tabela não existir, criar dados de exemplo
    $faturas = [
        [
            'id' => 1,
            'tutor_nome' => 'João Silva',
            'pet_nome' => 'Rex',
            'data_fatura' => '2023-06-01',
            'servico_nome' => 'Consulta de Rotina',
            'valor' => 120.00,
            'forma_pagamento' => 'Cartão de Crédito',
            'profissional' => 'Dr. Carlos'
        ],
        [
            'id' => 2,
            'tutor_nome' => 'Maria Oliveira',
            'pet_nome' => 'Luna',
            'data_fatura' => '2023-06-02',
            'servico_nome' => 'Vacinação',
            'valor' => 80.00,
            'forma_pagamento' => 'Dinheiro',
            'profissional' => 'Dra. Ana'
        ]
    ];
}

// Buscar dados para edição se solicitado
$fatura_edicao = null;
if (isset($_GET['editar_fatura']) && is_numeric($_GET['editar_fatura'])) {
    $id = (int)$_GET['editar_fatura'];
    $sql = "SELECT * FROM Faturas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $fatura_edicao = $result->fetch_assoc();
    }
}

// Buscar dados para visualização se solicitado
$fatura_visualizacao = null;
if (isset($_GET['visualizar_fatura']) && is_numeric($_GET['visualizar_fatura'])) {
    $id = (int)$_GET['visualizar_fatura'];
    $sql = "SELECT f.*, t.nome as tutor_nome, p.nome as pet_nome, 
        COALESCE(s.nome, 'Serviço não especificado') as servico_nome 
        FROM Faturas f
        JOIN Tutor t ON f.tutor_id = t.id_tutor
        JOIN Pets p ON f.pet_id = p.id_pet
        LEFT JOIN Servicos s ON (f.servico = s.id_servico OR f.servico IS NULL)
        WHERE f.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $fatura_visualizacao = $result->fetch_assoc();
    }
}

include_once('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Detalhada - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Estilos específicos da página */
        .fatura-container {
            margin-left: 250px;
            padding: 20px;
            background-color: #f6f9fc;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            color: #0b3556;
            font-size: 24px;
            margin: 0;
        }
        
        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            color: #0b3556;
            font-size: 18px;
            margin: 0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: #0b3556;
            box-shadow: 0 0 0 3px rgba(11, 53, 86, 0.1);
            outline: none;
        }
        
        .form-control:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-primary {
            background-color: #0b3556;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0d4371;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-icon i {
            font-size: 16px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th, 
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            color: #0b3556;
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background-color: #f6f9fc;
        }
        
        .data-table .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            transition: color 0.3s;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-size: 12px;
        }
        
        .btn-edit {
            background-color: #4a90e2;
        }
        
        .btn-delete {
            background-color: #e25c5c;
        }
        
        .btn-view {
            background-color: #50c878;
        }
        
        .btn-action:hover {
            opacity: 0.8;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 0;
            color: #777;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
        
        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
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
        
        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease-out;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .fatura-container {
                margin-left: 0;
                padding: 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        /* Garantir compatibilidade com o sidebar */
        .sidebar {
            width: 220px !important;
            background-color: #003b66 !important;
            position: fixed !important;
            top: 60px !important;
            bottom: 0 !important;
            left: 0 !important;
            z-index: 900 !important;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 60px !important;
            }
            
            .sidebar.expanded {
                width: 220px !important;
            }
            
            .fatura-container {
                margin-left: 60px;
            }
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="fatura-container">

        <!-- Formulário da Fatura -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?php echo $fatura_edicao ? 'Editar Fatura' : 'Nova Fatura'; ?></h2>
            </div>
            
            <form id="formularioFatura" method="POST" action="gerenciamento_faturas.php">
                <input type="hidden" name="acao" value="<?php echo $fatura_edicao ? 'editar_fatura' : 'criar_fatura'; ?>">
                <?php if ($fatura_edicao): ?>
                    <input type="hidden" name="id" value="<?php echo $fatura_edicao['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tutor_id">Tutor</label>
                        <select id="tutor_id" name="tutor_id" class="form-control" onchange="carregarPets(this.value)" required>
                            <option value="">Selecione um tutor</option>
                            <?php foreach ($tutores as $tutor): ?>
                                <option value="<?php echo $tutor['id_tutor']; ?>" <?php echo ($fatura_edicao && $fatura_edicao['tutor_id'] == $tutor['id_tutor']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tutor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="pet_id">Pet</label>
                        <select id="pet_id" name="pet_id" class="form-control" required disabled>
                            <option value="">Selecione um tutor primeiro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dataFatura">Data da Fatura</label>
                        <input type="date" id="dataFatura" name="dataFatura" class="form-control" required value="<?php echo $fatura_edicao ? $fatura_edicao['data_fatura'] : date('Y-m-d'); ?>" />
                    </div>

                    <div class="form-group">
                        <label for="servico">Serviço Realizado</label>
                        <select id="servico" name="servico" class="form-control" onchange="atualizarValor()" required>
                            <option value="">Selecione um serviço</option>
                            <?php foreach ($servicos as $servico): ?>
                                <option value="<?php echo $servico['id_servico']; ?>" data-preco="<?php echo $servico['preco']; ?>" <?php echo ($fatura_edicao && $fatura_edicao['servico'] == $servico['id_servico']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($servico['nome']); ?> - R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="valor">Valor do Serviço (R$)</label>
                        <input type="number" id="valor" name="valor" class="form-control" step="0.01" required value="<?php echo $fatura_edicao ? $fatura_edicao['valor'] : ''; ?>" />
                    </div>

                    <div class="form-group">
                        <label for="pagamento">Forma de Pagamento</label>
                        <select id="pagamento" name="pagamento" class="form-control" required>
                            <option value="">Selecione</option>
                            <option value="Dinheiro" <?php echo ($fatura_edicao && $fatura_edicao['forma_pagamento'] == 'Dinheiro') ? 'selected' : ''; ?>>Dinheiro</option>
                            <option value="Cartão de Crédito" <?php echo ($fatura_edicao && $fatura_edicao['forma_pagamento'] == 'Cartão de Crédito') ? 'selected' : ''; ?>>Cartão de Crédito</option>
                            <option value="Cartão de Débito" <?php echo ($fatura_edicao && $fatura_edicao['forma_pagamento'] == 'Cartão de Débito') ? 'selected' : ''; ?>>Cartão de Débito</option>
                            <option value="Pix" <?php echo ($fatura_edicao && $fatura_edicao['forma_pagamento'] == 'Pix') ? 'selected' : ''; ?>>Pix</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="3"><?php echo $fatura_edicao ? htmlspecialchars($fatura_edicao['observacoes']) : ''; ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="clinica">Nome da Clínica</label>
                        <input type="text" id="clinica" name="clinica" class="form-control" required value="<?php echo $fatura_edicao ? htmlspecialchars($fatura_edicao['clinica']) : 'PetPlus Clínica Veterinária'; ?>" />
                    </div>

                    <div class="form-group">
                        <label for="profissional">Profissional Responsável</label>
                        <input type="text" id="profissional" name="profissional" class="form-control" required value="<?php echo $fatura_edicao ? htmlspecialchars($fatura_edicao['profissional']) : ''; ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-icon">
                        <i class="fas fa-save"></i> <?php echo $fatura_edicao ? 'Atualizar Fatura' : 'Cadastrar Fatura'; ?>
                    </button>
                    <?php if ($fatura_edicao): ?>
                        <a href="gerenciamento_faturas.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Tabela de Faturas -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Faturas Registradas</h2>
                <div class="card-actions">
                    <input type="text" id="pesquisaFatura" class="form-control" placeholder="Pesquisar faturas..." style="width: 250px;">
                </div>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tutor</th>
                            <th>Pet</th>
                            <th>Data</th>
                            <th>Serviço</th>
                            <th>Valor (R$)</th>
                            <th>Pagamento</th>
                            <th>Profissional</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="listaFaturas">
                        <?php if (empty($faturas)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center;">Nenhuma fatura registrada</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($faturas as $fatura): ?>
                                <tr>
                                    <td><?php echo $fatura['id']; ?></td>
                                    <td><?php echo htmlspecialchars($fatura['tutor_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($fatura['pet_nome']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($fatura['data_fatura'])); ?></td>
                                    <td><?php echo htmlspecialchars($fatura['servico_nome']); ?></td>
                                    <td>R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($fatura['forma_pagamento']); ?></td>
                                    <td><?php echo htmlspecialchars($fatura['profissional']); ?></td>
                                    <td class="actions">
                                        <button class="btn-action btn-view" onclick="visualizarFatura(<?php echo $fatura['id']; ?>)" title="Visualizar">Visualizar</button>
                                        <button class="btn-action btn-edit" onclick="editarFatura(<?php echo $fatura['id']; ?>)" title="Editar">Editar</button>
                                        <button class="btn-action btn-delete" onclick="confirmarExclusaoFatura(<?php echo $fatura['id']; ?>)" title="Excluir">Excluir</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização -->
    <div id="modalVisualizacao" class="modal">
        <div class="modal-content">
            <span class="fechar" onclick="fecharModalVisualizacao()">&times;</span>
            <h2>Detalhes da Fatura</h2>
            <div id="conteudoVisualizacao">
                <!-- Conteúdo será preenchido via JavaScript -->
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

        // Função para carregar os pets do tutor selecionado
        
function carregarPets(tutorId) {
    const petSelect = document.getElementById('pet_id');
    
    // Limpar o select de pets
    petSelect.innerHTML = '<option value="">Selecione um pet</option>';
    
    if (!tutorId) {
        petSelect.disabled = true;
        return;
    }
    
    // Habilitar o select de pets
    petSelect.disabled = false;
    
    // Fazer uma requisição para buscar os pets do tutor
    fetch(`get_pets.php?id_tutor=${tutorId}`)
        .then(response => response.json())
        .then(pets => {
            if (pets && pets.length > 0) {
                pets.forEach(pet => {
                    const option = document.createElement('option');
                    option.value = pet.id_pet;
                    option.textContent = pet.nome;
                    petSelect.appendChild(option);
                });
            } else {
                petSelect.innerHTML = '<option value="">Nenhum pet encontrado</option>';
            }
        })
        .catch(error => {
            console.error('Erro ao carregar pets:', error);
            petSelect.innerHTML = '<option value="">Erro ao carregar pets</option>';
        });
}
        
        // Função para atualizar o valor com base no serviço selecionado
        function atualizarValor() {
            const servicoSelect = document.getElementById('servico');
            const valorInput = document.getElementById('valor');
            
            if (servicoSelect.value) {
                const option = servicoSelect.options[servicoSelect.selectedIndex];
                const preco = option.getAttribute('data-preco');
                valorInput.value = preco;
            } else {
                valorInput.value = '';
            }
        }

        // Função para editar fatura
        function editarFatura(id) {
            window.location.href = 'gerenciamento_faturas.php?editar_fatura=' + id;
        }
        
        // Função para visualizar fatura
        function visualizarFatura(id) {
            window.location.href = 'gerenciamento_faturas.php?visualizar_fatura=' + id;
        }
        
        // Função para confirmar exclusão
        function confirmarExclusaoFatura(id) {
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
                    window.location.href = 'gerenciamento_faturas.php?excluir_fatura=' + id;
                }
            });
        }
        
        // Função para fechar modal de visualização
        function fecharModalVisualizacao() {
            document.getElementById('modalVisualizacao').style.display = 'none';
        }
        
        // Pesquisa de faturas
        document.getElementById('pesquisaFatura').addEventListener('input', function() {
            const termo = this.value.toLowerCase();
            const linhas = document.querySelectorAll('#listaFaturas tr');
            
            linhas.forEach(linha => {
                const conteudo = linha.textContent.toLowerCase();
                if (conteudo.includes(termo)) {
                    linha.style.display = '';
                } else {
                    linha.style.display = 'none';
                }
            });
        });
        
        // Carregar pets se estiver editando
        <?php if ($fatura_edicao): ?>
            document.addEventListener('DOMContentLoaded', function() {
                carregarPets(<?php echo $fatura_edicao['tutor_id']; ?>);
                setTimeout(() => {
                    document.getElementById('pet_id').value = <?php echo $fatura_edicao['pet_id']; ?>;
                }, 500);
            });
        <?php endif; ?>
        
        // Mostrar modal de visualização se solicitado
        <?php if ($fatura_visualizacao): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('modalVisualizacao');
                const conteudo = document.getElementById('conteudoVisualizacao');
                
                conteudo.innerHTML = `
                    <div style="line-height: 1.6;">
                        <p><strong>ID:</strong> <?php echo $fatura_visualizacao['id']; ?></p>
                        <p><strong>Tutor:</strong> <?php echo htmlspecialchars($fatura_visualizacao['tutor_nome']); ?></p>
                        <p><strong>Pet:</strong> <?php echo htmlspecialchars($fatura_visualizacao['pet_nome']); ?></p>
                        <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($fatura_visualizacao['data_fatura'])); ?></p>
                        <p><strong>Serviço:</strong> <?php echo htmlspecialchars($fatura_visualizacao['servico_nome']); ?></p>
                        <p><strong>Valor:</strong> R$ <?php echo number_format($fatura_visualizacao['valor'], 2, ',', '.'); ?></p>
                        <p><strong>Forma de Pagamento:</strong> <?php echo htmlspecialchars($fatura_visualizacao['forma_pagamento']); ?></p>
                        <p><strong>Clínica:</strong> <?php echo htmlspecialchars($fatura_visualizacao['clinica']); ?></p>
                        <p><strong>Profissional:</strong> <?php echo htmlspecialchars($fatura_visualizacao['profissional']); ?></p>
                        <p><strong>Observações:</strong> <?php echo htmlspecialchars($fatura_visualizacao['observacoes'] ?? 'Nenhuma observação'); ?></p>
                    </div>
                `;
                
                modal.style.display = 'block';
            });
        <?php endif; ?>
        
        // Fechar modal ao clicar fora
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalVisualizacao');
            if (event.target == modal) {
                fecharModalVisualizacao();
            }
        });
    </script>
    
    <script src="../includes/header.js"></script>
</body>
</html>

<?php $conn->close(); ?>
