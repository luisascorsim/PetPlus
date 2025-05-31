<?php
$current_page = 'perfil';
$page_title = 'Alterar Senha';
include_once('../includes/header.php'); // Supondo que este header não imprima <head> ou <body> ainda

// Inicia a sessão
if (session_status() == PHP_SESSION_NONE) { // Verifica se a sessão já não foi iniciada
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o arquivo de conexão
include_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário de alteração de senha
$mensagem = ''; // Inicializa a mensagem
$tipo_mensagem = ''; // Para controlar o tipo de ícone do SweetAlert (success, error)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $mensagem = "Todos os campos são obrigatórios.";
        $tipo_mensagem = "error";
    } elseif ($nova_senha !== $confirmar_senha) {
        $mensagem = "As novas senhas não coincidem.";
        $tipo_mensagem = "error";
    } else {
        $id_usuario = $_SESSION['id_usuario'];
        $query = "SELECT senha FROM Usuarios WHERE id_usuario = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $row = $result->fetch_assoc()) {
                if (password_verify($senha_atual, $row['senha'])) {
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $update_query = "UPDATE Usuarios SET senha = ? WHERE id_usuario = ?";
                    $update_stmt = $conn->prepare($update_query);
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $senha_hash, $id_usuario);
                        if ($update_stmt->execute()) {
                            $mensagem = "Senha alterada com sucesso!";
                            $tipo_mensagem = "success";
                        } else {
                            $mensagem = "Erro ao alterar a senha: " . $conn->error;
                            $tipo_mensagem = "error";
                        }
                        $update_stmt->close();
                    } else {
                        $mensagem = "Erro ao preparar a atualização da senha: " . $conn->error;
                        $tipo_mensagem = "error";
                    }
                } else {
                    $mensagem = "Senha atual incorreta.";
                    $tipo_mensagem = "error";
                }
            } else {
                $mensagem = "Erro ao buscar dados do usuário.";
                $tipo_mensagem = "error";
            }
            $stmt->close();
        } else {
            $mensagem = "Erro ao preparar a consulta: " . $conn->error;
            $tipo_mensagem = "error";
        }
    }
}

// Fecha a conexão
if ($conn) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        <form method="POST" action="" id="formAlterarSenha">
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
                <button type="button" id="btnSubmitSenha" style="background-color: #2196f3; color: white; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-weight: bold;">Alterar Senha</button>
            </div>
        </form>
    </div>

    <script>
    // Adiciona o listener ao DOMContentLoaded para garantir que o formulário e o botão existam
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formAlterarSenha');
        const btnSubmit = document.getElementById('btnSubmitSenha');

        if (btnSubmit) {
            btnSubmit.addEventListener('click', function(e) {
                // Validação básica do lado do cliente (opcional, já que há validação no PHP)
                const senhaAtual = document.getElementById('senha_atual').value;
                const novaSenha = document.getElementById('nova_senha').value;
                const confirmarSenha = document.getElementById('confirmar_senha').value;

                if (!senhaAtual || !novaSenha || !confirmarSenha) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Por favor, preencha todos os campos!',
                    });
                    return; // Impede o prosseguimento
                }

                if (novaSenha !== confirmarSenha) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Atenção!',
                        text: 'As novas senhas não coincidem.',
                    });
                    return; // Impede o prosseguimento
                }

                // SweetAlert de confirmação
                Swal.fire({
                    title: 'Tem certeza?',
                    text: "Você deseja realmente alterar sua senha?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, alterar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Se confirmado, submete o formulário
                        if (form) {
                            form.submit();
                        }
                    }
                });
            });
        }

        // Exibir a mensagem do PHP com SweetAlert, se houver
        <?php if (!empty($mensagem) && !empty($tipo_mensagem)): ?>
        Swal.fire({
            icon: '<?php echo $tipo_mensagem; ?>', // 'success' ou 'error'
            title: '<?php echo ($tipo_mensagem === "success") ? "Sucesso!" : "Erro!"; ?>',
            text: '<?php echo addslashes($mensagem); // Adiciona barras para escapar aspas na string JS ?>',
            confirmButtonText: 'OK'
        });
        <?php endif; ?>
    });
    </script>

</body>
</html>