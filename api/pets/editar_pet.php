<?php
session_start();
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se o ID foi fornecido
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do pet não fornecido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

// Obter dados do formulário
$id = intval($_POST['id']);
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
$raca = isset($_POST['raca']) ? trim($_POST['raca']) : null;
$idade = isset($_POST['idade']) && $_POST['idade'] !== '' ? floatval($_POST['idade']) : null;
$peso = isset($_POST['peso']) && $_POST['peso'] !== '' ? floatval($_POST['peso']) : null;
$sexo = isset($_POST['sexo']) ? trim($_POST['sexo']) : null;
$cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
$data_nascimento = isset($_POST['data_nascimento']) && !empty($_POST['data_nascimento']) ? $_POST['data_nascimento'] : null;
$observacoes = isset($_POST['observacoes']) ? trim($_POST['observacoes']) : null;

// Validar dados obrigatórios
if (empty($nome) || empty($tipo) || empty($cliente_id)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos obrigatórios']);
    exit;
}

try {
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Verificar se o pet existe
    $stmt = $pdo->prepare("SELECT foto FROM pets WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Pet não encontrado']);
        exit;
    }
    
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    $foto_atual = $pet['foto'];
    
    // Processar upload de foto, se houver
    $foto_url = $foto_atual;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/pets/';
        
        // Criar diretório se não existir
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['foto']['name']);
        $upload_file = $upload_dir . $file_name;
        
        // Verificar tipo de arquivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['foto']['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF']);
            exit;
        }
        
        // Mover arquivo para o diretório de uploads
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_file)) {
            $foto_url = 'uploads/pets/' . $file_name;
            
            // Remover foto antiga se existir
            if ($foto_atual && file_exists('../../' . $foto_atual)) {
                unlink('../../' . $foto_atual);
            }
        } else {
            throw new Exception('Falha ao fazer upload da imagem');
        }
    }
    
    // Atualizar pet no banco de dados
    $query = "
        UPDATE pets SET
            nome = :nome,
            tipo = :tipo,
            raca = :raca,
            idade = :idade,
            peso = :peso,
            sexo = :sexo,
            cliente_id = :cliente_id,
            data_nascimento = :data_nascimento,
            observacoes = :observacoes,
            foto = :foto,
            data_atualizacao = NOW()
        WHERE id = :id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->bindParam(':raca', $raca);
    $stmt->bindParam(':idade', $idade);
    $stmt->bindParam(':peso', $peso);
    $stmt->bindParam(':sexo', $sexo);
    $stmt->bindParam(':cliente_id', $cliente_id);
    $stmt->bindParam(':data_nascimento', $data_nascimento);
    $stmt->bindParam(':observacoes', $observacoes);
    $stmt->bindParam(':foto', $foto_url);
    $stmt->execute();
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Pet atualizado com sucesso!']);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar pet: ' . $e->getMessage()]);
}
?>
