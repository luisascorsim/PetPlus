<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

require_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';
$editando = false;
$registro_editando = null; 

// Verifica se está editando
if (isset($_GET['editar']) && !empty($_GET['editar']) && isset($_GET['indice'])) {
    $editando = true;
    $id_pet_editar = (int)$_GET['editar'];
    $indice_editar = (int)$_GET['indice'];
    
    $sql_pet = "SELECT p.*, t.nome as nome_tutor FROM Pets p JOIN Tutor t ON p.id_tutor = t.id_tutor WHERE p.id_pet = ?";
    $stmt_pet = $conn->prepare($sql_pet);
    $stmt_pet->bind_param("i", $id_pet_editar);
    $stmt_pet->execute();
    $result_pet = $stmt_pet->get_result();
    
    if ($result_pet->num_rows > 0) {
        $pet_data = $result_pet->fetch_assoc();
        $observacoes = $pet_data['observacoes'] ?? '';
        
        $registros_array_de_strings = explodeRegistros($observacoes); 

        if (isset($registros_array_de_strings[$indice_editar])) {
            $texto_do_registro_para_editar = $registros_array_de_strings[$indice_editar];
            $dados_parseados_do_registro = parseRegistro($texto_do_registro_para_editar); 

            $registro_editando = $dados_parseados_do_registro; 
            $registro_editando['id_pet'] = $id_pet_editar;
            $registro_editando['nome_pet'] = $pet_data['nome'];
            $registro_editando['nome_tutor'] = $pet_data['nome_tutor'];
            $registro_editando['indice'] = $indice_editar;
        } else {
            $mensagem = "Registro específico não encontrado para edição (Índice: {$indice_editar}).";
            $tipo_mensagem = "erro";
            $editando = false; 
        }
    } else {
        $mensagem = "Pet não encontrado para edição.";
        $tipo_mensagem = "erro";
        $editando = false; 
    }
    $stmt_pet->close();
}


// Processa exclusão
if (isset($_GET['excluir']) && !empty($_GET['excluir']) && isset($_GET['indice'])) {
    $id_pet_excluir = (int)$_GET['excluir'];
    $indice_excluir = (int)$_GET['indice'];
    
    $sql_get_obs = "SELECT observacoes FROM Pets WHERE id_pet = ?";
    $stmt_get = $conn->prepare($sql_get_obs);
    $stmt_get->bind_param("i", $id_pet_excluir);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    
    if ($result_get->num_rows > 0) {
        $pet_data_excluir = $result_get->fetch_assoc();
        $observacoes_excluir = $pet_data_excluir['observacoes'] ?? '';
        
        $registros_excluir = explodeRegistros($observacoes_excluir);
        if (isset($registros_excluir[$indice_excluir])) {
            unset($registros_excluir[$indice_excluir]);
            
            $novas_observacoes = implodeRegistros(array_values($registros_excluir)); 
            
            $sql_update = "UPDATE Pets SET observacoes = ? WHERE id_pet = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $novas_observacoes, $id_pet_excluir);
            
            if ($stmt_update->execute()) {
                $mensagem = "Registro excluído com sucesso!";
                $tipo_mensagem = "sucesso";
            } else {
                $mensagem = "Erro ao excluir registro: " . $conn->error;
                $tipo_mensagem = "erro";
            }
            $stmt_update->close();
        } else {
            $mensagem = "Erro ao excluir: registro não encontrado.";
            $tipo_mensagem = "erro";
        }
    }  else {
        $mensagem = "Erro ao excluir: pet não encontrado.";
        $tipo_mensagem = "erro";
    }
    $stmt_get->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pet = (int)$_POST['id_pet'];
    $tipo_registro = $_POST['tipo_registro'];
    $descricao = trim($_POST['descricao']);
    $data_registro_form = $_POST['data_registro']; 
    $gravidade = $_POST['gravidade'] ?? '';
    $editando_post = isset($_POST['editando']) && $_POST['editando'] == '1';
    $indice_edicao = $editando_post ? (int)$_POST['indice_edicao'] : -1;
    
    if (empty($id_pet) || empty($tipo_registro) || empty($descricao) || empty($data_registro_form)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
        $tipo_mensagem = "erro";
    } else {
        $sql_get_obs = "SELECT observacoes FROM Pets WHERE id_pet = ?";
        $stmt_get = $conn->prepare($sql_get_obs);
        $stmt_get->bind_param("i", $id_pet);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        
        if ($result_get->num_rows > 0) {
            $pet_data_post = $result_get->fetch_assoc();
            $observacoes_atuais = $pet_data_post['observacoes'] ?? '';
            
            $novo_registro_formatado = "\n--- " . strtoupper(str_replace('_', ' ', $tipo_registro)) . " ---\n";
            $novo_registro_formatado .= "Data: " . date('d/m/Y', strtotime($data_registro_form)) . "\n"; 
            if (!empty($gravidade) && ($tipo_registro === 'alergia' || $tipo_registro === 'condicao_cronica')) {
                $novo_registro_formatado .= "Gravidade: " . $gravidade . "\n";
            }
            $novo_registro_formatado .= "Descrição: " . $descricao . "\n";
            $novo_registro_formatado .= "Registrado em: " . date('d/m/Y H:i') . "\n";
            
            if ($editando_post && $indice_edicao >= 0) {
                $registros_atuais_array = explodeRegistros($observacoes_atuais);
                if(isset($registros_atuais_array[$indice_edicao])) {
                    $registros_atuais_array[$indice_edicao] = $novo_registro_formatado;
                    $observacoes_atualizadas = implodeRegistros($registros_atuais_array);
                    $mensagem_sucesso = "Registro atualizado com sucesso!";
                } else {
                     $mensagem = "Erro: Índice de edição inválido.";
                     $tipo_mensagem = "erro";
                     $observacoes_atualizadas = $observacoes_atuais; 
                }
            } else {
                $observacoes_atualizadas = $observacoes_atuais . $novo_registro_formatado;
                $mensagem_sucesso = "Registro adicionado com sucesso!";
            }
            
            if (empty($tipo_mensagem) || $tipo_mensagem !== "erro") { 
                $sql_update = "UPDATE Pets SET observacoes = ? WHERE id_pet = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("si", $observacoes_atualizadas, $id_pet);
                
                if ($stmt_update->execute()) {
                    $mensagem = $mensagem_sucesso;
                    $tipo_mensagem = "sucesso";
                    $_POST = array(); 
                    $editando = false;
                    $registro_editando = null; 
                } else {
                    $mensagem = "Erro ao salvar registro médico: " . $conn->error;
                    $tipo_mensagem = "erro";
                }
                $stmt_update->close();
            }
        } else {
            $mensagem = "Pet não encontrado.";
            $tipo_mensagem = "erro";
        }
        $stmt_get->close();
    }
}

function explodeRegistros($observacoes) {
    if (empty(trim($observacoes))) return [];
    $registros = [];
    $partes = preg_split('/(?=\\n--- )/m', trim($observacoes), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($partes as $parte) {
        $registros[] = trim($parte);
    }
    return $registros;
}

function implodeRegistros($registros) {
    return trim(implode("", $registros));
}

function parseRegistro($registro_texto) {
    $linhas = explode("\n", trim($registro_texto));
    $registro = [
        'tipo' => '',
        'data' => '', 
        'gravidade' => '',
        'descricao' => '',
        'data_registro_original' => '' 
    ];
    
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (str_starts_with($linha, '--- ') && str_ends_with($linha, ' ---')) {
            $tipo = str_replace(['--- ', ' ---'], '', $linha);
            $registro['tipo'] = strtolower(str_replace(' ', '_', $tipo)); 
        } elseif (str_starts_with($linha, 'Data: ')) {
            $data_str = str_replace('Data: ', '', $linha); 
            $date_obj = DateTime::createFromFormat('d/m/Y', $data_str);
            if ($date_obj) {
                $registro['data'] = $date_obj->format('Y-m-d');
            } else {
                $ts = strtotime($data_str);
                if ($ts) $registro['data'] = date('Y-m-d', $ts);
            }
        } elseif (str_starts_with($linha, 'Gravidade: ')) {
            $registro['gravidade'] = str_replace('Gravidade: ', '', $linha);
        } elseif (str_starts_with($linha, 'Descrição: ')) {
            $registro['descricao'] = str_replace('Descrição: ', '', $linha);
        } elseif (str_starts_with($linha, 'Registrado em: ')) {
            $registro['data_registro_original'] = str_replace('Registrado em: ', '', $linha);
        }
    }
    if (empty($registro['descricao'])) {
        $descricao_temp = [];
        $capturando_descricao = false;
        foreach ($linhas as $l) {
            $l = trim($l);
            if (str_starts_with($l, 'Descrição: ')) {
                $descricao_temp[] = str_replace('Descrição: ', '', $l);
                $capturando_descricao = true;
                continue;
            }
            if ($capturando_descricao && !preg_match('/^(Data:|Gravidade:|Registrado em:|---)/', $l)) {
                $descricao_temp[] = $l;
            } else if ($capturando_descricao) {
                $capturando_descricao = false; 
            }
        }
        if (!empty($descricao_temp)) {
            $registro['descricao'] = trim(implode("\n", $descricao_temp));
        }
    }
    return $registro;
}

$sql_pets = "SELECT p.id_pet, p.nome, t.nome as nome_tutor 
            FROM Pets p 
            JOIN Tutor t ON p.id_tutor = t.id_tutor 
            ORDER BY p.nome";
$result_pets_dropdown = $conn->query($sql_pets);
$pets_dropdown = [];
if ($result_pets_dropdown && $result_pets_dropdown->num_rows > 0) {
    while ($row_pet_dropdown = $result_pets_dropdown->fetch_assoc()) {
        $pets_dropdown[] = $row_pet_dropdown;
    }
}

$sql_historico_display = "SELECT p.id_pet, p.nome, p.observacoes, t.nome as nome_tutor 
                        FROM Pets p 
                        JOIN Tutor t ON p.id_tutor = t.id_tutor 
                        WHERE p.observacoes IS NOT NULL AND p.observacoes != ''
                        ORDER BY p.nome, p.id_pet";
$result_historico_display = $conn->query($sql_historico_display);
$historico_display = [];
if ($result_historico_display && $result_historico_display->num_rows > 0) {
    while ($row_historico_item = $result_historico_display->fetch_assoc()) {
        $registros_individuais = explodeRegistros($row_historico_item['observacoes']);
        foreach ($registros_individuais as $indice => $texto_registro_individual) {
            if(empty(trim($texto_registro_individual))) continue; 
            $registro_parseado_para_display = parseRegistro($texto_registro_individual);
            $historico_display[] = [
                'id_pet' => $row_historico_item['id_pet'],
                'nome_pet' => $row_historico_item['nome'],
                'nome_tutor' => $row_historico_item['nome_tutor'],
                'registro_texto_completo' => $texto_registro_individual,
                'registro_parseado' => $registro_parseado_para_display,
                'indice_no_pet' => $indice
            ];
        }
    }
}

if ($editando && $registro_editando) { 
    if (!empty($registro_editando['tipo'])) { 
        $_POST['id_pet'] = $registro_editando['id_pet'];
        $_POST['tipo_registro'] = $registro_editando['tipo']; 
        $_POST['data_registro'] = $registro_editando['data']; 
        $_POST['gravidade'] = $registro_editando['gravidade'];
        $_POST['descricao'] = $registro_editando['descricao'];
    }
}

$mensagem_swal = '';
$tipo_mensagem_swal = '';
if (!empty($mensagem) && !empty($tipo_mensagem)) {
    $mensagem_swal = addslashes(htmlspecialchars($mensagem)); 
    if ($tipo_mensagem === 'sucesso') $tipo_mensagem_swal = 'success';
    else if ($tipo_mensagem === 'erro') $tipo_mensagem_swal = 'error';
    else $tipo_mensagem_swal = 'info';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condições Médicas - PetPlus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
        }
        
        .main-content {
            margin-left: 250px; 
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s;
        }

        body.sidebar-collapsed .main-content { 
            margin-left: 60px; 
        }

        .card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,.125);
            border-radius: calc(0.375rem - 1px) calc(0.375rem - 1px) 0 0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-color: #0056b3; 
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #003d80); 
            border-color: #003d80;
        }
        
        /* Botão Editar (azul bem claro) */
        .btn-edit-light-blue {
            background-color: #cce5ff; /* Azul bem claro (Bootstrap info background) */
            border-color: #b8daff; /* Borda um pouco mais escura */
            color: #004085; /* Texto azul escuro para contraste */
        }
        .btn-edit-light-blue:hover {
            background-color: #b8daff;
            border-color: #a2cffe;
            color: #00376e;
        }

        /* Botão Excluir (vermelho/danger) */
        .btn-delete {
            background-color: #dc3545; 
            border-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        .btn-secondary { background-color: #6c757d; border-color: #6c757d; color: #fff; }
        .btn-secondary:hover { background-color: #5a6268; border-color: #545b62;}
      
        .form-control, .form-select {
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff; /* Azul claro no foco, padrão Bootstrap */
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .registro-badge { 
            font-size: 0.75em; 
            padding: 0.35em 0.65em;
            border-radius: 0.25rem; 
            font-weight: 600;
            text-transform: uppercase;
            color: white;
            vertical-align: middle; 
        }
        .badge-alergia { background-color: #dc3545; } 
        .badge-condicao_cronica { background-color: #ffc107; color: #000; } 
        .badge-medicamento { background-color: #0d6efd; } 
        .badge-cirurgia { background-color: #6f42c1; } 
        .badge-observacao { background-color: #6c757d; } 
        
        .historico-item {
            background-color: #fff;
            border: 1px solid #e3e6f0; 
            border-radius: 0.35rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            position: relative; 
        }
        .historico-pet-info {
            padding-bottom: 0.75rem; 
            margin-bottom: 0.75rem; 
            border-bottom: 1px solid #e9ecef; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            flex-wrap: wrap; 
        }
         .historico-pet-info .pet-details {
            flex-grow: 1; 
        }
        .historico-content {
            white-space: pre-wrap; 
            font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.9em;
            background-color: #f8f9fa; 
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            word-break: break-word;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.3rem; 
            margin-left: 1rem; 
            flex-shrink: 0; 
        }
        .action-buttons .btn-sm {
             padding: 0.2rem 0.4rem; 
             font-size: .7rem; 
        }

        .alert-editing-info { 
            background-color: #003b66; /* Azul claro (Bootstrap .alert-info) */
            border-color: #b6effb;
            color: white; /* Texto escuro para contraste */
            border-radius: 0.25rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
             .historico-pet-info {
                flex-direction: column; 
                align-items: stretch;
            }
            .action-buttons {
                margin-left: 0;
                margin-top: 0.5rem; 
                justify-content: flex-end; 
            }
        }
    </style>
</head>
<body>
    <?php require_once('../includes/sidebar.php'); ?>
    <?php require_once('../includes/header.php'); ?>
    <div class="main-content">
        <div class="container-fluid">
            <?php if ($editando && $registro_editando): ?>
                <div class="alert alert-editing-info mb-4">
                    <h5 class="alert-heading mb-2">Editando Registro Médico</h5>
                    <p class="mb-1">
                        Pet: <strong><?php echo htmlspecialchars($registro_editando['nome_pet'] ?? 'N/A'); ?></strong> - 
                        Tutor: <strong><?php echo htmlspecialchars($registro_editando['nome_tutor'] ?? 'N/A'); ?></strong>
                    </p>
                    
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card <?php echo ($editando && $registro_editando) ? 'editing-form-card' : ''; ?>">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <?php echo ($editando && $registro_editando) ? 'Editar Registro Médico' : 'Registrar Histórico Médico'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?">
                                <?php if ($editando && $registro_editando): ?>
                                    <input type="hidden" name="editando" value="1">
                                    <input type="hidden" name="indice_edicao" value="<?php echo htmlspecialchars($registro_editando['indice'] ?? ''); ?>">
                                    <input type="hidden" name="id_pet" value="<?php echo htmlspecialchars($_POST['id_pet'] ?? ($registro_editando['id_pet'] ?? '')); ?>">
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <label for="id_pet_form" class="form-label">Pet *</label>
                                    <select class="form-select" id="id_pet_form" name="id_pet" required <?php echo ($editando && $registro_editando) ? 'disabled' : ''; ?>>
                                        <option value="">Selecione um pet</option>
                                        <?php foreach ($pets_dropdown as $pet_opt): ?>
                                            <option value="<?php echo $pet_opt['id_pet']; ?>" 
                                                <?php echo (isset($_POST['id_pet']) && $_POST['id_pet'] == $pet_opt['id_pet']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pet_opt['nome']) . ' (Tutor: ' . htmlspecialchars($pet_opt['nome_tutor']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tipo_registro_form" class="form-label">Tipo de Registro *</label>
                                    <select class="form-select" id="tipo_registro_form" name="tipo_registro" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="alergia" <?php echo (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] == 'alergia') ? 'selected' : ''; ?>>Alergia</option>
                                        <option value="condicao_cronica" <?php echo (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] == 'condicao_cronica') ? 'selected' : ''; ?>>Condição Crônica</option>
                                        <option value="medicamento" <?php echo (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] == 'medicamento') ? 'selected' : ''; ?>>Medicamento em Uso</option>
                                        <option value="cirurgia" <?php echo (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] == 'cirurgia') ? 'selected' : ''; ?>>Cirurgia/Procedimento</option>
                                        <option value="observacao" <?php echo (isset($_POST['tipo_registro']) && $_POST['tipo_registro'] == 'observacao') ? 'selected' : ''; ?>>Observação Geral</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="data_registro_form" class="form-label">Data do Evento *</label>
                                    <input type="date" class="form-control" id="data_registro_form" name="data_registro" 
                                           value="<?php echo isset($_POST['data_registro']) ? htmlspecialchars($_POST['data_registro']) : date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="mb-3" id="gravidade_container" style="display: none;">
                                    <label for="gravidade_form" class="form-label">Gravidade</label>
                                    <select class="form-select" id="gravidade_form" name="gravidade">
                                        <option value="">Selecione</option>
                                        <option value="Leve" <?php echo (isset($_POST['gravidade']) && $_POST['gravidade'] == 'Leve') ? 'selected' : ''; ?>>Leve</option>
                                        <option value="Moderada" <?php echo (isset($_POST['gravidade']) && $_POST['gravidade'] == 'Moderada') ? 'selected' : ''; ?>>Moderada</option>
                                        <option value="Grave" <?php echo (isset($_POST['gravidade']) && $_POST['gravidade'] == 'Grave') ? 'selected' : ''; ?>>Grave</option>
                                        <option value="Crítica" <?php echo (isset($_POST['gravidade']) && $_POST['gravidade'] == 'Crítica') ? 'selected' : ''; ?>>Crítica</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="descricao_form" class="form-label">Descrição Detalhada *</label>
                                    <textarea class="form-control" id="descricao_form" name="descricao" rows="4" 
                                              placeholder="Descreva detalhadamente..." required><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                                    <div class="form-text">
                                        Seja específico: sintomas, causas, tratamentos, dosagens, etc.
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <?php echo ($editando && $registro_editando) ? 'Atualizar Registro' : 'Registrar Histórico'; ?>
                                    </button>
                                    <?php if ($editando && $registro_editando): ?>
                                        <a href="?" class="btn btn-secondary">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Condições Médicas dos Pets</h5>
                        </div>
                        <div class="card-body" style="max-height: 700px; overflow-y: auto;">
                            <?php if (empty($historico_display)): ?>
                                <div class="text-center text-muted py-4">
                                    <p>Nenhum histórico médico registrado ainda.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($historico_display as $item_hist): ?>
                                    <div class="historico-item">
                                        <div class="historico-pet-info">
                                            <div class="pet-details">
                                                <strong><?php echo htmlspecialchars($item_hist['nome_pet']); ?></strong>
                                                <small class="text-muted ms-2">(Tutor: <?php echo htmlspecialchars($item_hist['nome_tutor']); ?>)</small>
                                                <br> 
                                                <?php if (!empty($item_hist['registro_parseado']['tipo'])): ?>
                                                    <span class="registro-badge badge-<?php echo htmlspecialchars($item_hist['registro_parseado']['tipo']); ?> mt-1 d-inline-block">
                                                        <?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($item_hist['registro_parseado']['tipo']))); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="action-buttons">
                                                <a href="?editar=<?php echo $item_hist['id_pet']; ?>&indice=<?php echo $item_hist['indice_no_pet']; ?>" 
                                                   class="btn btn-sm btn-edit-light-blue" title="Editar">Editar</a>
                                                <button onclick="confirmarExclusao(<?php echo $item_hist['id_pet']; ?>, <?php echo $item_hist['indice_no_pet']; ?>)" 
                                                        class="btn btn-sm btn-delete" title="Excluir">Excluir</button>
                                            </div>
                                        </div>
                                        <div class="historico-content">
                                            <?php echo nl2br(htmlspecialchars(trim($item_hist['registro_texto_completo']))); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tipoRegistroEl = document.getElementById('tipo_registro_form'); 
            const gravidadeContainerEl = document.getElementById('gravidade_container');
            const gravidadeSelectEl = document.getElementById('gravidade_form'); 
            
            function toggleGravidadeField() {
                if (!tipoRegistroEl) return; 
                const valor = tipoRegistroEl.value;
                if (valor === 'alergia' || valor === 'condicao_cronica') {
                    gravidadeContainerEl.style.display = 'block';
                } else {
                    gravidadeContainerEl.style.display = 'none';
                    if (gravidadeSelectEl) gravidadeSelectEl.value = '';
                }
            }
            
            if (tipoRegistroEl) {
                tipoRegistroEl.addEventListener('change', toggleGravidadeField);
                toggleGravidadeField(); 
            }
            
            <?php if (!empty($mensagem_swal) && !empty($tipo_mensagem_swal)): ?>
            Swal.fire({
                title: '<?php echo ($tipo_mensagem_swal === "success" ? "Sucesso!" : ($tipo_mensagem_swal === "error" ? "Erro!" : "Atenção!")); ?>',
                html: '<?php echo $mensagem_swal; ?>', 
                icon: '<?php echo $tipo_mensagem_swal; ?>',
                confirmButtonColor: '#007bff', 
                confirmButtonText: 'OK'
            });
            <?php endif; ?>
        });
        
        function confirmarExclusao(idPet, indice) {
            Swal.fire({
                title: 'Confirmar Exclusão',
                text: 'Tem certeza que deseja excluir este registro médico? Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545', 
                cancelButtonColor: '#6c757d',  
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('excluir', idPet);
                    url.searchParams.set('indice', indice);
                    url.searchParams.delete('editar'); 
                    window.location.href = url.toString();
                }
            });
        }
    </script>
</body>
</html>