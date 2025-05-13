<?php
$current_page = 'perfil';
$page_title = 'Alterar Senha';
include_once('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha - PetPlus</title>
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
        <h1>Alterar Senha</h1>
<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o arquivo de conexão
include_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário de alteração de senha
$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    
    // Verifica se as senhas novas coincidem
    if ($nova_senha !== $confirmar_senha) {
        $mensagem = "As senhas não coincidem.";
    } else {
        // Busca a senha atual do usuário
        $id_usuario = $_SESSION['id_usuario'];
        $query = "SELECT senha FROM Usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            // Verifica se a senha atual está correta
            if (password_verify($senha_atual, $row['senha'])) {
                // Atualiza a senha
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $update_query = "UPDATE Usuarios SET senha = ? WHERE id_usuario = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $senha_hash, $id_usuario);
                
                if ($update_stmt->execute()) {
                    $mensagem = "Senha alterada com sucesso!";
                } else {
                    $mensagem = "Erro ao alterar a senha: " . $conn->error;
                }
            } else {
                $mensagem = "Senha atual incorreta.";
            }
        } else {
            $mensagem = "Erro ao buscar dados do usuário.";
        }
    }
}

// Fecha a conexão
$conn->close();

// Inclui o header e sidebar
//include_once('../includes/header.php');
//include_once('../includes/sidebar.php');
?>

<!--<div class="container" style="max-width: 600px; margin-left: 220px; margin-top: 80px; padding: 20px; background-color: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
    <h1 style="color: #2196f3; text-align: center; margin-bottom: 30px;">Alterar Senha</h1>-->
    
    <?php if ($mensagem): ?>
        <div style="padding: 10px; margin-bottom: 20px; border-radius: 4px; background-color: <?php echo strpos($mensagem, 'sucesso') !== false ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo strpos($mensagem, 'sucesso') !== false ? '#155724' : '#721c24'; ?>;">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div style="margin-bottom: 20px;">
            <label for="senha_atual" style="display: block; margin-bottom: 5px; font-weight: bold;">Senha Atual</label>
            <input type="password" id="senha_atual" name="senha_atual" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="nova_senha" style="display: block; margin-bottom: 5px; font-weight: bold;">Nova Senha</label>
            <input type="password" id="nova_senha" name="nova_senha" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="confirmar_senha" style="display: block; margin-bottom: 5px; font-weight: bold;">Confirmar Nova Senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        
        <div style="text-align: center;">
            <button type="submit" style="background-color: #2196f3; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-weight: bold;">Alterar Senha</button>
        </div>
    </form>
</div>

<script src="../includes/header.js"></script>
</body>
</html>
