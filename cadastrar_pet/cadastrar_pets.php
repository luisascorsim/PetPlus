<?php

session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

require_once('../includes/header.php'); // Supondo que SweetAlert2 não será incluído aqui
require_once('../includes/sidebar.php');

require_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = ''; // 'sucesso' ou 'erro' no código original

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verifica se é uma edição ou um novo registro
    $id_pet = isset($_POST['id_pet']) && !empty($_POST['id_pet']) ? (int)$_POST['id_pet'] : null;
    
    // Dados do tutor
    $id_tutor = $_POST['id_tutor'];
    
    // Dados do pet
    $nome_pet = $_POST['nome_pet'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $data_nascimento = $_POST['data_nascimento']?? null;
    $sexo = $_POST['sexo'];
    $observacoes = $_POST['observacoes']?? null;
    
    // Validações
    if (empty($id_tutor) || empty($nome_pet) || empty($especie) || empty($sexo)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
        $tipo_mensagem = "erro";
    } else {
        // Inicia uma transação
        $conn->begin_transaction();
        
        try {
            if ($id_pet) {
                // Atualiza os dados do pet
                $sql = "UPDATE Pets SET id_tutor = ?, nome = ?, especie = ?, raca = ?, data_nascimento = ?, sexo = ?, observacoes = ? WHERE id_pet = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssssi", $id_tutor, $nome_pet, $especie, $raca, $data_nascimento, $sexo, $observacoes, $id_pet);
                $stmt->execute();
            } else {
                // Insere um novo pet
                $sql = "INSERT INTO Pets (id_tutor, nome, especie, raca, data_nascimento, sexo, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssss", $id_tutor, $nome_pet, $especie, $raca, $data_nascimento, $sexo, $observacoes);
                $stmt->execute(); 
                $id_pet_inserted = $conn->insert_id; // Use uma nova variável para não confundir com $id_pet de edição
            }
            
            $conn->commit();
            
            $mensagem = $id_pet ? "Pet atualizado com sucesso!" : "Pet cadastrado com sucesso!";
            $tipo_mensagem = "sucesso";
            
            if (!$id_pet) { // Limpa apenas em caso de novo cadastro
                $_POST = array();
            }
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem = "Erro ao " . ($id_pet ? "atualizar" : "cadastrar") . " pet: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Exclui um pet se solicitado
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_pet_excluir = (int)$_GET['excluir'];
    
    $conn->begin_transaction();
    
    try {
        $sql = "DELETE FROM Pets WHERE id_pet = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pet_excluir);
        $stmt->execute();
        
        $conn->commit();
        
        $mensagem = "Pet excluído com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem = "Erro ao excluir pet: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Busca todos os tutores para o dropdown
$sql_tutores = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
// A linha abaixo estava sobrescrevendo a de cima, mantendo a segunda que parece mais completa se a tabela Tutor tiver mais campos úteis.
// Contudo, para o dropdown, apenas id_tutor e nome são usados. A query original `SELECT id_tutor, nome FROM Tutor ORDER BY nome` é mais otimizada.
// Vou manter a que estava por último no seu código:
$sql_tutores_ativos = "SELECT * FROM Tutor ORDER BY nome"; // Adicionado ORDER BY nome
$result_tutores = $conn->query($sql_tutores_ativos);
$tutores = [];

if ($result_tutores && $result_tutores->num_rows > 0) {
    while ($row_tutor = $result_tutores->fetch_assoc()) {
        $tutores[] = $row_tutor;
    }
}


// Busca todos os pets
$sql_pets = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
        FROM Pets p 
        JOIN Tutor t ON p.id_tutor = t.id_tutor 
        ORDER BY p.nome";
$result_pets = $conn->query($sql_pets);
$pets = [];

if ($result_pets && $result_pets->num_rows > 0) {
    while ($row_pet = $result_pets->fetch_assoc()) {
        $pets[] = $row_pet;
    }
}

// Busca um pet específico para edição
$pet_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_pet_editar = (int)$_GET['editar'];
    
    $sql_edit_pet = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
            FROM Pets p 
            JOIN Tutor t ON p.id_tutor = t.id_tutor 
            WHERE p.id_pet = ?";
    $stmt_edit = $conn->prepare($sql_edit_pet);
    $stmt_edit->bind_param("i", $id_pet_editar);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    
    if ($result_edit && $result_edit->num_rows > 0) {
        $pet_edicao = $result_edit->fetch_assoc();
    }
}

// Preparar mensagens para SweetAlert2
$mensagem_swal = '';
$tipo_mensagem_swal = ''; // 'success', 'error', 'warning', 'info'

// Prioridade 1: Mensagens flash da sessão (de excluir_tutor.php)
if (isset($_SESSION['mensagem_flash'])) {
    $mensagem_swal = addslashes($_SESSION['mensagem_flash']['texto']);
    $raw_tipo = $_SESSION['mensagem_flash']['tipo'];
    if ($raw_tipo === 'sucesso') $tipo_mensagem_swal = 'success';
    else if ($raw_tipo === 'erro') $tipo_mensagem_swal = 'error';
    else if ($raw_tipo === 'aviso') $tipo_mensagem_swal = 'warning';
    else $tipo_mensagem_swal = 'info';
    unset($_SESSION['mensagem_flash']);
}
// Prioridade 2: Mensagens via GET (de cadastrar_tutor.php)
else if (isset($_GET['mensagem']) && isset($_GET['tipo'])) {
    $mensagem_swal = addslashes(urldecode($_GET['mensagem']));
    $raw_tipo = urldecode($_GET['tipo']);
    if ($raw_tipo === 'sucesso') $tipo_mensagem_swal = 'success';
    else if ($raw_tipo === 'erro') $tipo_mensagem_swal = 'error';
    else if ($raw_tipo === 'aviso') $tipo_mensagem_swal = 'warning';
    else $tipo_mensagem_swal = 'info';
}
// Prioridade 3: Mensagens do processamento POST/GET do próprio cadastrar_pets.php
else if (!empty($mensagem) && !empty($tipo_mensagem)) {
    $mensagem_swal = addslashes($mensagem);
    $raw_tipo = $tipo_mensagem;
    if ($raw_tipo === 'sucesso') $tipo_mensagem_swal = 'success';
    else if ($raw_tipo === 'erro') $tipo_mensagem_swal = 'error';
    else $tipo_mensagem_swal = 'info';
}

// Limpar as variáveis originais de mensagem para não exibir o bloco HTML antigo se o SweetAlert for usado
if(!empty($mensagem_swal)){
    $mensagem = '';
    $tipo_mensagem = '';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Pets - PetPlus</title>
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
        
        h1, h2 {
            color: #0b3556;
            margin-bottom: 20px;
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
            margin-bottom: 15px;
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
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Adicionado para consistência de layout */
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
        }
        
        .btn-edit {
            background-color: #2196f3;
        }
        
        .btn-delete {
            background-color: #f44336;
        }
        
        /* Removido o estilo de .mensagem, .mensagem-sucesso, .mensagem-erro pois será tratado pelo SweetAlert */
        
        @media (max-width: 768px) {
            .container {
                margin-left: 60px; /* Ou 0 se a sidebar colapsar completamente */
                padding: 70px 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Cadastro de Pets</h1>
            
            <?php /* if ($mensagem): ?> // Bloco de mensagem original comentado/removido
            <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php endif; */ ?>
            
            <div class="tabs">
                <div class="tab active" onclick="showTab('pets')">Cadastro de Pets</div> 
                <div class="tab" onclick="showTab('tutores')">Cadastro de Tutores</div>
            </div>
            
            <div id="tab-pets" class="tab-content active">
                <form action="cadastrar_pets.php" method="POST">
                    <input type="hidden" id="id_pet" name="id_pet" value="<?php echo $pet_edicao ? $pet_edicao['id_pet'] : ''; ?>">
                    
                    <div class="form-group">
                        <label for="id_tutor">Tutor*</label>
                        <select id="id_tutor" name="id_tutor" required>
                            <option value="">Selecione um tutor</option>
                            <?php foreach ($tutores as $tutor): ?>
                                <option value="<?php echo $tutor['id_tutor']; ?>" <?php echo ($pet_edicao && $pet_edicao['id_tutor'] == $tutor['id_tutor']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tutor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nome_pet">Nome do Pet*</label>
                        <input type="text" id="nome_pet" name="nome_pet" value="<?php echo $pet_edicao ? htmlspecialchars($pet_edicao['nome']) : (isset($_POST['nome_pet']) ? htmlspecialchars($_POST['nome_pet']) : ''); ?>" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="especie">Espécie*</label>
                        <select id="especie" name="especie" required>
                            <option value="">Selecione</option>
                            <option value="Cachorro" <?php echo ($pet_edicao && $pet_edicao['especie'] == 'Cachorro') || (isset($_POST['especie']) && $_POST['especie'] == 'Cachorro') ? 'selected' : ''; ?>>Cachorro</option>
                            <option value="Gato" <?php echo ($pet_edicao && $pet_edicao['especie'] == 'Gato') || (isset($_POST['especie']) && $_POST['especie'] == 'Gato') ? 'selected' : ''; ?>>Gato</option>
                            <option value="Ave" <?php echo ($pet_edicao && $pet_edicao['especie'] == 'Ave') || (isset($_POST['especie']) && $_POST['especie'] == 'Ave') ? 'selected' : ''; ?>>Ave</option>
                            <option value="Roedor" <?php echo ($pet_edicao && $pet_edicao['especie'] == 'Roedor') || (isset($_POST['especie']) && $_POST['especie'] == 'Roedor') ? 'selected' : ''; ?>>Roedor</option>
                            <option value="Outro" <?php echo ($pet_edicao && $pet_edicao['especie'] == 'Outro') || (isset($_POST['especie']) && $_POST['especie'] == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="raca">Raça</label>
                        <input type="text" id="raca" name="raca" value="<?php echo $pet_edicao ? htmlspecialchars($pet_edicao['raca']) : (isset($_POST['raca']) ? htmlspecialchars($_POST['raca']) : ''); ?>" />
                    </div>
                    
                    <div class="form-group">
                        <label for="idade">Data de Nascimento</label> <input type="date" id="idade" name="data_nascimento" value="<?php echo $pet_edicao ? $pet_edicao['data_nascimento'] : (isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''); ?>" /> </div>
                    
                    <div class="form-group">
                        <label for="sexo">Sexo*</label>
                        <select id="sexo" name="sexo" required>
                            <option value="">Selecione</option>
                            <option value="M" <?php echo ($pet_edicao && $pet_edicao['sexo'] == 'M') || (isset($_POST['sexo']) && $_POST['sexo'] == 'M') ? 'selected' : ''; ?>>Macho</option>
                            <option value="F" <?php echo ($pet_edicao && $pet_edicao['sexo'] == 'F') || (isset($_POST['sexo']) && $_POST['sexo'] == 'F') ? 'selected' : ''; ?>>Fêmea</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Descrição</label> <textarea id="descricao" name="observacoes" rows="4"><?php echo $pet_edicao ? htmlspecialchars($pet_edicao['observacoes']) : (isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary"><?php echo $pet_edicao ? 'Atualizar Pet' : 'Cadastrar Pet'; ?></button>
                    
                    <?php if ($pet_edicao): ?>
                        <a href="cadastrar_pets.php" class="btn-secondary">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
                
                <h2>Pets Cadastrados</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Espécie</th>
                            <th>Raça</th>
                            <th>Data de Nascimento</th>
                            <th>Tutor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pets)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhum pet cadastrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pets as $pet): ?>
                                <tr>
                                    <td><?php echo $pet['id_pet']; ?></td>
                                    <td><?php echo htmlspecialchars($pet['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['especie']); ?></td>
                                    <td><?php echo htmlspecialchars($pet['raca']); ?></td>
                                    <td><?php echo $pet['data_nascimento'] ? date('d/m/Y', strtotime($pet['data_nascimento'])) : ''; ?></td>
                                    <td><?php echo htmlspecialchars($pet['nome_tutor']); ?></td>
                                    <td>
                                        <a href="cadastrar_pets.php?editar=<?php echo $pet['id_pet']; ?>" class="btn-action btn-edit">Editar</a>
                                        <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $pet['id_pet']; ?>, 'pet')" class="btn-action btn-delete">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="tab-tutores" class="tab-content">
                <h2>Cadastro de Tutores</h2>
                <form action="cadastrar_tutor.php" method="POST">
                    <div class="form-group">
                        <label for="nome_tutor_form">Nome do Tutor*</label> <input type="text" id="nome_tutor_form" name="nome" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf_tutor_form">CPF*</label> <input type="text" id="cpf_tutor_form" name="cpf" required />
                    </div>
                    <div class="form-group">
                        <label for="email_tutor_form">E-mail</label> <input type="email" id="email_tutor_form" name="email" /> </div>
                    <div class="form-group">
                        <label for="telefone_tutor_form">Telefone*</label> <input type="text" id="telefone_tutor_form" name="telefone" required />
                    </div>
                    <div class="form-group">
                        <label for="endereco_tutor_form">Endereço</label> <input type="text" id="endereco_tutor_form" name="endereco" /> </div>
                    
                    <button type="submit" class="btn-primary">Cadastrar Tutor</button>
                </form>
                
                <h2>Tutores Cadastrados</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>Endereço</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tutores)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum tutor cadastrado</td> </tr>
                        <?php else: ?>
                            <?php foreach ($tutores as $tutor_row): ?> <tr>
                                    <td><?php echo $tutor_row['id_tutor']; ?></td>
                                    <td><?php echo htmlspecialchars($tutor_row['nome']); ?></td>
                                    <td><?php echo isset($tutor_row['cpf']) ? htmlspecialchars($tutor_row['cpf']) : ''; ?></td>
                                    <td><?php echo isset($tutor_row['telefone']) ? htmlspecialchars($tutor_row['telefone']) : ''; ?></td>
                                    <td><?php echo isset($tutor_row['endereco']) ? htmlspecialchars($tutor_row['endereco']) : ''; ?></td>
                                    <td>
                                        <a href="editar_tutor.php?id=<?php echo $tutor_row['id_tutor']; ?>" class="btn-action btn-edit">Editar</a>
                                        <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $tutor_row['id_tutor']; ?>, 'tutor')" class="btn-action btn-delete">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function confirmarExclusao(id, tipo) {
            let tituloSwal = '';
            let textoSwal = '';
            let url = '';

            if (tipo === 'pet') {
                tituloSwal = 'Excluir Pet?';
                textoSwal = 'Tem certeza que deseja excluir este pet? Esta ação não poderá ser desfeita.';
                url = 'cadastrar_pets.php?excluir=' + id + '&tab=pets'; // Mantém na aba de pets
            } else if (tipo === 'tutor') {
                tituloSwal = 'Excluir Tutor?';
                textoSwal = 'Tem certeza que deseja excluir este tutor? Esta ação também excluirá todos os pets associados a ele e não poderá ser desfeita.';
                url = 'excluir_tutor.php?id=' + id; // excluir_tutor.php redirecionará para cadastrar_pets.php#tab-tutores
            }

            Swal.fire({
                title: tituloSwal,
                text: textoSwal,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#0b3556',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
        
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById('tab-' + tabName).classList.add('active');
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add('active');

            // Atualiza a URL com o hash da aba ativa para persistência no reload
            if (history.pushState) {
                history.pushState(null, null, '#tab-' + tabName);
            } else {
                location.hash = '#tab-' + tabName;
            }
        }

        // Mostrar a aba correta com base no hash da URL ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.hash) {
                const hash = window.location.hash.substring(1); // Remove o '#'
                const tabName = hash.startsWith('tab-') ? hash.substring(4) : hash;
                if (document.getElementById('tab-' + tabName)) {
                    showTab(tabName);
                } else if (tabName === 'pets' || tabName === 'tutores') { // Compatibilidade com ?tab=pets
                     showTab(tabName);
                }
            } else {
                // Se não houver hash, verifica se há ?tab=pets (após exclusão de pet, por exemplo)
                const urlParams = new URLSearchParams(window.location.search);
                const tabParam = urlParams.get('tab');
                if (tabParam === 'pets' || tabParam === 'tutores') {
                    showTab(tabParam);
                } else {
                     showTab('pets'); // Aba padrão
                }
            }

            <?php if (!empty($mensagem_swal) && !empty($tipo_mensagem_swal)): ?>
            Swal.fire({
                title: '<?php echo ($tipo_mensagem_swal === "success" ? "Sucesso!" : ($tipo_mensagem_swal === "error" ? "Erro!" : ($tipo_mensagem_swal === "warning" ? "Atenção!" : "Info!"))); ?>',
                html: '<?php echo $mensagem_swal; ?>', // Usar html para permitir que addslashes escape corretamente as aspas e novas linhas
                icon: '<?php echo $tipo_mensagem_swal; ?>',
                confirmButtonColor: '#0b3556'
            });

            // Limpar parâmetros de mensagem da URL para não mostrar o alerta novamente no refresh
            if (window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('mensagem');
                url.searchParams.delete('tipo');
                // Mantém o hash se existir, ou define para a aba atual se não houver mensagem GET
                let currentHash = window.location.hash;
                if (!currentHash && document.querySelector('.tab-content.active')) {
                    currentHash = '#tab-' + document.querySelector('.tab-content.active').id.substring(4);
                }
                const newUrl = url.pathname + url.search + (currentHash || '');
                window.history.replaceState({ path: newUrl }, '', newUrl);
            }
            <?php endif; ?>
        });
        
        // Máscara para CPF (na aba de cadastro de tutores)
        // Os IDs dos inputs no formulário de tutor foram alterados para `cpf_tutor_form` e `telefone_tutor_form` para evitar conflito
        // e para que as máscaras abaixo funcionem corretamente para esses campos específicos.
        const cpfInput = document.getElementById('cpf_tutor_form'); // Corrigido para o ID do formulário de cadastro de tutor
        if (cpfInput) {
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                
                if (value.length > 9) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
                } else if (value.length > 6) {
                    value = value.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
                } else if (value.length > 3) {
                    value = value.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
                }
                e.target.value = value;
            });
        }
        
        // Máscara para telefone (na aba de cadastro de tutores)
        const telInput = document.getElementById('telefone_tutor_form'); // Corrigido para o ID do formulário de cadastro de tutor
        if (telInput) {
            telInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) {
                    value = value.slice(0, 11);
                }
                
                if (value.length > 10) { // Celular com 9 dígitos + DDD
                    value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                } else if (value.length > 6) { // Fixo ou celular com 8 dígitos + DDD
                    value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
                } else if (value.length > 2) {
                    value = value.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
                } else if (value.length > 0) {
                     value = value.replace(/^(\d*)$/, '($1');
                }
                e.target.value = value;
            });
        }
    </script>
</body>
</html>