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

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

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
                // SQL com 6 placeholders
                $sql = "INSERT INTO Pets (id_tutor, nome, especie, raca, data_nascimento, sexo, observacoes) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                // bind_param com 6 variáveis e 6 tipos
                $stmt->bind_param("issssss", $id_tutor, $nome_pet, $especie, $raca, $data_nascimento, $sexo, $observacoes);                $stmt->execute();
                $id_pet = $conn->insert_id;
            }
            
            // Confirma a transação
            $conn->commit();
            
            $mensagem = $id_pet ? "Pet atualizado com sucesso!" : "Pet cadastrado com sucesso!";
            $tipo_mensagem = "sucesso";
            
            // Limpa os dados do formulário após um cadastro bem-sucedido
            if (!$id_pet) {
                $_POST = array();
            }
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $conn->rollback();
            
            $mensagem = "Erro ao " . ($id_pet ? "atualizar" : "cadastrar") . " pet: " . $e->getMessage();
            $tipo_mensagem = "erro";
        }
    }
}

// Exclui um pet se solicitado
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id_pet = (int)$_GET['excluir'];
    
    // Inicia uma transação
    $conn->begin_transaction();
    
    try {
        // Exclui o pet
        $sql = "DELETE FROM Pets WHERE id_pet = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_pet);
        $stmt->execute();
        
        // Confirma a transação
        $conn->commit();
        
        $mensagem = "Pet excluído com sucesso!";
        $tipo_mensagem = "sucesso";
    } catch (Exception $e) {
        // Reverte a transação em caso de erro
        $conn->rollback();
        
        $mensagem = "Erro ao excluir pet: " . $e->getMessage();
        $tipo_mensagem = "erro";
    }
}

// Busca todos os tutores para o dropdown
$sql = "SELECT id_tutor, nome FROM Tutor ORDER BY nome";
$sql = "SELECT * FROM Tutor";
$result = $conn->query($sql);
$tutores = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $tutores[] = $row;
    }
}


// Busca todos os pets
$sql = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
        FROM Pets p 
        JOIN Tutor t ON p.id_tutor = t.id_tutor 
        ORDER BY p.nome";
$result = $conn->query($sql);
$pets = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pets[] = $row;
    }
}

// Busca um pet específico para edição
$pet_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_pet = (int)$_GET['editar'];
    
    $sql = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
            FROM Pets p 
            JOIN Tutor t ON p.id_tutor = t.id_tutor 
            WHERE p.id_pet = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pet);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $pet_edicao = $result->fetch_assoc();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Pets - PetPlus</title>
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
            <h1>Cadastro de Pets</h1>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
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
                        <label for="idade">Data de Nascimento</label>
                        <input type="date" id="idade" name="data_nascimento" min="0" value="<?php echo $pet_edicao ? $pet_edicao['data_nascimento'] : (isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''); ?>" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="sexo">Sexo*</label>
                        <select id="sexo" name="sexo" required>
                            <option value="">Selecione</option>
                            <option value="Macho" <?php echo ($pet_edicao && $pet_edicao['sexo'] == 'Macho') || (isset($_POST['sexo']) && $_POST['sexo'] == 'Macho') ? 'selected' : ''; ?>>Macho</option>
                            <option value="Fêmea" <?php echo ($pet_edicao && $pet_edicao['sexo'] == 'Fêmea') || (isset($_POST['sexo']) && $_POST['sexo'] == 'Fêmea') ? 'selected' : ''; ?>>Fêmea</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacoes">Descrição</label>
                        <textarea id="descricao" name="observacoes" rows="4"><?php echo $pet_edicao ? htmlspecialchars($pet_edicao['observacoes']) : (isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : ''); ?></textarea>
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
                                    <td><?php echo $pet['data_nascimento'] ; ?></td>
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
                        <label for="nome_tutor">Nome do Tutor*</label>
                        <input type="text" id="nome_tutor" name="nome" required />
                    </div>
                    
                    <div class="form-group">
                        <label for="cpf_tutor">CPF*</label>
                        <input type="text" id="cpf_tutor" name="cpf" required />
                    </div>
                          <div class="form-group">
                        <label for="email_tutor">E-mail</label>
                        <input type="email" id="email_tutor" name="email" required />
                    </div>
                    <div class="form-group">
                        <label for="telefone_tutor">Telefone*</label>
                        <input type="text" id="telefone_tutor" name="telefone" required />
                    </div>
                    <div class="form-group">
                        <label for="endereco_tutor">Endereço</label>
                        <input type="text" id="endereco_tutor" name="endereco" required />
                    </div>
                    
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
                                <td colspan="5" style="text-align: center;">Nenhum tutor cadastrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tutores as $tutor): ?>
                                <tr>
                                    <td><?php echo $tutor['id_tutor']; ?></td>
                                    <td><?php echo htmlspecialchars($tutor['nome']); ?></td>
                                    <td><?php echo isset($tutor['cpf']) ? htmlspecialchars($tutor['cpf']) : ''; ?></td>
                                    <td><?php echo isset($tutor['telefone']) ? htmlspecialchars($tutor['telefone']) : ''; ?></td>
                                    <td><?php echo isset($tutor['endereco']) ? htmlspecialchars($tutor['endereco']) : ''; ?></td>
                                    <td>
                                        <a href="editar_tutor.php?id=<?php echo $tutor['id_tutor']; ?>" class="btn-action btn-edit">Editar</a>
                                        <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $tutor['id_tutor']; ?>, 'tutor')" class="btn-action btn-delete">Excluir</a>
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
        // Atualizar a função confirmarExclusao para incluir a exclusão de tutores
        function confirmarExclusao(id, tipo) {
            let mensagem = '';
            let url = '';
            
            if (tipo === 'pet') {
                mensagem = 'Tem certeza que deseja excluir este pet?';
                url = 'cadastrar_pets.php?excluir=' + id;
            } else if (tipo === 'tutor') {
                mensagem = 'Tem certeza que deseja excluir este tutor? Esta ação também excluirá todos os pets associados a ele.';
                url = 'excluir_tutor.php?id=' + id;
            }
            
            if (confirm(mensagem)) {
                window.location.href = url;
            }
        }
        
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
        
        
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
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
        
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>