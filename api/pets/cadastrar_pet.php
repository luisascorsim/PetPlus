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

// Incluir arquivo de conexão com o banco de dados
require_once '../../config/database.php';

// Obter dados do formulário
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
    
    // Processar upload de foto, se houver
    $foto_url = null;
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
        } else {
            throw new Exception('Falha ao fazer upload da imagem');
        }
    }
    
    // Inserir pet no banco de dados
    $query = "
        INSERT INTO pets (nome, tipo, raca, idade, peso, sexo, cliente_id, data_nascimento, observacoes, foto, data_cadastro)
        VALUES (:nome, :tipo, :raca, :idade, :peso, :sexo, :cliente_id, :data_nascimento, :observacoes, :foto, NOW())
    ";
    
    $stmt = $pdo->prepare($query);
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
    
    $pet_id = $pdo->lastInsertId();
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Pet cadastrado com sucesso!', 'id' => $pet_id]);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar pet: ' . $e->getMessage()]);
}
?>
