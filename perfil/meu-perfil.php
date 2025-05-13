<?php
// Inicia a sessão apenas se ainda não estiver ativa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = 'perfil';
$page_title = 'Meu Perfil';
include_once('../includes/header.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario']) && !isset($_SESSION['usuario_id'])) {
    // Para desenvolvimento, criar uma sessão temporária
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Inclui o arquivo de conexão
include_once('../conecta_db.php');
$conn = conecta_db();

// Busca os dados do usuário
$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : $_SESSION['usuario_id'];
$usuario = null;

$query = "SELECT * FROM Usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
}

// Processa o formulário de atualização
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    
    // Verifica se o email já está em uso por outro usuário
    $check_query = "SELECT id_usuario FROM Usuarios WHERE email = ? AND id_usuario != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("si", $email, $id_usuario);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $mensagem = "Este email já está sendo usado por outro usuário.";
    } else {
        // Atualiza os dados do usuário
        $update_query = "UPDATE Usuarios SET nome = ?, email = ? WHERE id_usuario = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssi", $nome, $email, $id_usuario);
        
        if ($update_stmt->execute()) {
            $mensagem = "Perfil atualizado com sucesso!";
            // Atualiza os dados na sessão
            $_SESSION['nome'] = $nome;
            
            // Recarrega os dados do usuário
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $usuario = $result->fetch_assoc();
            }
        } else {
            $mensagem = "Erro ao atualizar o perfil: " . $conn->error;
        }
    }
}

// Fecha a conexão
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <style>
        /* Estilos específicos da página */
        .container {
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="container">
        <h1>Meu Perfil</h1>
    
    <?php if ($mensagem): ?>
        <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: <?php echo strpos($mensagem, 'sucesso') !== false ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo strpos($mensagem, 'sucesso') !== false ? '#155724' : '#721c24'; ?>;">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($usuario): ?>
        <form method="POST" action="">
            <div style="margin-bottom: 20px;">
                <label for="nome" style="display: block; margin-bottom: 5px; font-weight: bold;">Nome Completo</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="cpf" style="display: block; margin-bottom: 5px; font-weight: bold;">CPF</label>
                <input type="text" id="cpf" value="<?php echo htmlspecialchars($usuario['cpf']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;">
                <small style="color: #666;">O CPF não pode ser alterado.</small>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="data_nasc" style="display: block; margin-bottom: 5px; font-weight: bold;">Data de Nascimento</label>
                <input type="date" id="data_nasc" value="<?php echo htmlspecialchars($usuario['data_nasc']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;">
                <small style="color: #666;">A data de nascimento não pode ser alterada.</small>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="data_criacao" style="display: block; margin-bottom: 5px; font-weight: bold;">Data de Cadastro</label>
                <input type="text" id="data_criacao" value="<?php echo date('d/m/Y H:i', strtotime($usuario['data_criacao'])); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #f9f9f9;">
            </div>
            
            <div style="text-align: center;">
                <button type="submit" style="background-color: #2196f3; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-weight: bold;">Salvar Alterações</button>
            </div>
        </form>
    <?php else: ?>
        <p style="text-align: center; color: #721c24;">Não foi possível carregar os dados do usuário.</p>
    <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
    .container {
        margin-left: 60px;
    }
}
</style>

    </div>
    <script src="../includes/header.js"></script>
</body>
</html>
