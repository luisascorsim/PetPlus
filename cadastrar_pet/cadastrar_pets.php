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
    $nome_tutor = $_POST['nome_tutor'];
    $telefone_tutor = $_POST['telefone_tutor'];
    $endereco_tutor = $_POST['endereco_tutor'];
    
    // Dados do pet
    $nome_pet = $_POST['nome_pet'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $idade = (int)$_POST['idade'];
    $sexo = $_POST['sexo'];
    $peso = (float)$_POST['peso'];
    $descricao = $_POST['descricao'];
    
    // Validações
    if (empty($nome_tutor) || empty($nome_pet) || empty($especie) || empty($sexo)) {
        $mensagem = "Todos os campos obrigatórios devem ser preenchidos.";
        $tipo_mensagem = "erro";
    } else {
        // Inicia uma transação
        $conn->begin_transaction();
        
        try {
            if ($id_pet) {
                // Busca o id_tutor associado ao pet
                $sql = "SELECT id_tutor FROM Pet WHERE id_pet = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_pet);
                $stmt->execute();
                $result = $stmt->get_result();
                $pet = $result->fetch_assoc();
                $id_tutor = $pet['id_tutor'];
                
                // Atualiza os dados do tutor
                $sql = "UPDATE Tutor SET nome = ?, telefone = ?, endereco = ? WHERE id_tutor = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $nome_tutor, $telefone_tutor, $endereco_tutor, $id_tutor);
                $stmt->execute();
                
                // Atualiza os dados do pet
                $sql = "UPDATE Pet SET nome = ?, especie = ?, raca = ?, idade = ?, sexo = ?, peso_atual = ?, descricao = ? WHERE id_pet = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssissdi", $nome_pet, $especie, $raca, $idade, $sexo, $peso, $descricao, $id_pet);
                $stmt->execute();
            } else {
                // Insere um novo tutor
                $sql = "INSERT INTO Tutor (nome, telefone, endereco) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $nome_tutor, $telefone_tutor, $endereco_tutor);
                $stmt->execute();
                $id_tutor = $conn->insert_id;
                
                // Insere um novo pet
                $sql = "INSERT INTO Pet (id_tutor, nome, especie, raca, idade, sexo, peso_atual, descricao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssisds", $id_tutor, $nome_pet, $especie, $raca, $idade, $sexo, $peso, $descricao);
                $stmt->execute();
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
    
    // Busca o id_tutor associado ao pet
    $sql = "SELECT id_tutor FROM Pet WHERE id_pet = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_pet);
    $stmt->execute();
    $result = $stmt->get_result();
    $pet = $result->fetch_assoc();
    
    if ($pet) {
        $id_tutor = $pet['id_tutor'];
        
        // Inicia uma transação
        $conn->begin_transaction();
        
        try {
            // Exclui o pet
            $sql = "DELETE FROM Pet WHERE id_pet = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_pet);
            $stmt->execute();
            
            // Verifica se o tutor possui outros pets
            $sql = "SELECT COUNT(*) as total FROM Pet WHERE id_tutor = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_tutor);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // Se o tutor não possui outros pets, exclui o tutor também
            if ($row['total'] == 0) {
                $sql = "DELETE FROM Tutor WHERE id_tutor = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_tutor);
                $stmt->execute();
            }
            
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
    } else {
        $mensagem = "Pet não encontrado.";
        $tipo_mensagem = "erro";
    }
}

// Busca todos os pets
$sql = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
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

// Busca um pet específico para edição
$pet_edicao = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_pet = (int)$_GET['editar'];
    
    $sql = "SELECT p.*, t.nome as nome_tutor, t.telefone, t.endereco 
            FROM Pet p 
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
            
            <form action="cadastrar_pets.php" method="POST">
                <input type="hidden" id="id_pet" name="id_pet" value="<?php echo $pet_edicao ? $pet_edicao['id_pet'] : ''; ?>">
                
                <h2>Dados do Tutor</h2>
                
                <div class="form-group">
                    <label for="nome_tutor">Nome do Tutor*</label>
                    <input type="text" id="nome_tutor" name="nome_tutor" value="<?php echo $pet_edicao ? htmlspecialchars($pet_edicao['nome_tutor']) : (isset($_POST['nome_tutor']) ? htmlspecialchars($_POST['nome_tutor']) : ''); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="telefone_tutor">Telefone</label>
                    <input type="text" id="telefone_tutor" name="telefone_tutor" value="<?php echo $pet_edicao ? htmlspecialchars($pet_edicao['telefone']) : (isset($_POST['telefone_tutor']) ? htmlspecialchars($_POST['telefone_tutor']) : ''); ?>" />
                </div>
                
                <div class="form-group">
                    <label for="endereco_tutor">Endereço</label>
                    <input type="text" id="endereco_tutor" name="endereco_tutor" value="<?php echo $pet_edicao ? htmlspecialchars($pet_edicao['endereco']) : (isset($_POST['endereco_tutor']) ? htmlspecialchars($_POST['endereco_tutor']) : ''); ?>" />
                </div>
                
                <h2>Dados do Pet</h2>
                
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
                    <label for="idade">Idade (anos)*</label>
                    <input type="number" id="idade" name="idade" min="0" value="<?php echo $pet_edicao ? $pet_edicao['idade'] : (isset($_POST['idade']) ? $_POST['idade'] : ''); ?>" required />
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
                    <label for="peso">Peso (kg)</label>
                    <input type="number" id="peso" name="peso" min="0" step="0.01" value="<?php echo $pet_edicao ? $pet_edicao['peso_atual'] : (isset($_POST['peso']) ? $_POST['peso'] : ''); ?>" />
                </div>
                
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="4"><?php echo $pet_edicao ? htmlspecialchars($pet_edicao['descricao']) : (isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''); ?></textarea>
                </div>
                
                <button type="submit" class="btn-primary"><?php echo $pet_edicao ? 'Atualizar Pet' : 'Cadastrar Pet'; ?></button>
                
                <?php if ($pet_edicao): ?>
                    <a href="cadastrar_pets.php" class="btn-secondary" style="display: block; text-align: center; text-decoration: none;">Cancelar Edição</a>
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
                        <th>Idade</th>
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
                                <td><?php $nascimento = new DateTime($pet['data_nascimento']);
                                        $hoje = new DateTime();
                                        $idade = $nascimento->diff($hoje)->y;
                                        echo $idade . ' anos';?></td>
                                <td><?php echo htmlspecialchars($pet['nome_tutor']); ?></td>
                                <td>
                                    <a href="cadastrar_pets.php?editar=<?php echo $pet['id_pet']; ?>" class="btn-action btn-edit">Editar</a>
                                    <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $pet['id_pet']; ?>)" class="btn-action btn-delete">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir este pet? Esta ação também pode excluir o tutor se não houver outros pets associados a ele.')) {
                window.location.href = 'cadastrar_pets.php?excluir=' + id;
            }
        }
    </script>
</body>
</html>
