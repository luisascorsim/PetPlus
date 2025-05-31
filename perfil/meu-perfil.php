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
    $_SESSION['usuario_id'] = 1; // Usar o mesmo nome de chave consistentemente
    $_SESSION['id_usuario'] = 1; // Adicionar para consistência se ambos são usados
    $_SESSION['usuario_nome'] = 'Administrador';
    $_SESSION['usuario_email'] = 'admin@petplus.com';
    $_SESSION['usuario_tipo'] = 'admin';
}

// Inclui o arquivo de conexão
include_once('../conecta_db.php');
$conn = conecta_db();

// Busca os dados do usuário
$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : (isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null);
$usuario = null;

if ($id_usuario) {
    $query = "SELECT * FROM Usuarios WHERE id_usuario = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
        }
        // $stmt->close(); // Fechar mais tarde se for reutilizado
    } else {
        // Tratar erro de preparação da query inicial
        error_log("Erro ao preparar a query de busca de usuário: " . $conn->error);
    }
}


// Processa o formulário de atualização
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_usuario) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);

    if (empty($nome) || empty($email)) {
        $mensagem = "Nome e Email são obrigatórios.";
        $tipo_mensagem = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Formato de email inválido.";
        $tipo_mensagem = "error";
    } else {
        // Verifica se o email já está em uso por outro usuário
        $check_query = "SELECT id_usuario FROM Usuarios WHERE email = ? AND id_usuario != ?";
        $check_stmt = $conn->prepare($check_query);
        if ($check_stmt) {
            $check_stmt->bind_param("si", $email, $id_usuario);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $mensagem = "Este email já está sendo usado por outro usuário.";
                $tipo_mensagem = "error";
            } else {
                // Atualiza os dados do usuário
                $update_query = "UPDATE Usuarios SET nome = ?, email = ? WHERE id_usuario = ?";
                $update_stmt = $conn->prepare($update_query);
                if ($update_stmt) {
                    $update_stmt->bind_param("ssi", $nome, $email, $id_usuario);
                    if ($update_stmt->execute()) {
                        $mensagem = "Perfil atualizado com sucesso!";
                        $tipo_mensagem = "success";

                        $_SESSION['usuario_nome'] = $nome;
                        $_SESSION['usuario_email'] = $email;

                        // Recarrega os dados do usuário para exibir as informações atualizadas no formulário
                        if ($stmt) { // Reutiliza o $stmt preparado anteriormente se ainda válido
                             $stmt->execute(); // Re-executa a query original para buscar usuário
                             $result_refresh = $stmt->get_result();
                             if ($result_refresh && $result_refresh->num_rows > 0) {
                                 $usuario = $result_refresh->fetch_assoc();
                             }
                        }
                    } else {
                        $mensagem = "Erro ao atualizar o perfil: " . $conn->error;
                        $tipo_mensagem = "error";
                    }
                    $update_stmt->close();
                } else {
                    $mensagem = "Erro ao preparar atualização: " . $conn->error;
                    $tipo_mensagem = "error";
                }
            }
            $check_stmt->close();
        } else {
             $mensagem = "Erro ao preparar verificação de email: " . $conn->error;
             $tipo_mensagem = "error";
        }
    }
}

// Fecha o statement principal se ainda estiver aberto
if (isset($stmt) && $stmt) {
    $stmt->close();
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
    <title><?php echo htmlspecialchars($page_title); ?> - PetPlus</title>
    <link rel="stylesheet" href="../includes/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Estilos específicos da página */
        .container {
            margin-left: 250px; /* Ajuste conforme a largura do seu sidebar */
            padding: 20px;
        }
        @media (max-width: 768px) {
            .container {
                margin-left: 60px; /* Ajuste para sidebar recolhido ou em modo mobile */
            }
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input:disabled {
            background-color: #f9f9f9;
            cursor: not-allowed;
        }
        small {
            display: block;
            margin-top: -10px;
            margin-bottom: 15px;
            color: #666;
        }
        button { /* Estilo geral para o botão */
            background-color: #2196f3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #1976d2;
        }
        .form-group {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <?php include_once('../includes/sidebar.php'); ?>
    <div class="container">
        <h1>Meu Perfil</h1>

        <?php if ($usuario): ?>
            <form method="POST" action="" id="formMeuPerfil">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" value="<?php echo htmlspecialchars(isset($usuario['cpf']) ? $usuario['cpf'] : ''); ?>" disabled>
                    <small>O CPF não pode ser alterado.</small>
                </div>

                <div class="form-group">
                    <label for="data_nasc">Data de Nascimento</label>
                    <input type="date" id="data_nasc" name="data_nasc" value="<?php echo htmlspecialchars(isset($usuario['data_nasc']) ? $usuario['data_nasc'] : ''); ?>" disabled>
                    <small>A data de nascimento não pode ser alterada.</small>
                </div>

                <div class="form-group">
                    <label for="data_criacao">Data de Cadastro</label>
                    <input type="text" id="data_criacao" name="data_criacao" value="<?php echo htmlspecialchars(isset($usuario['data_criacao']) ? date('d/m/Y H:i', strtotime($usuario['data_criacao'])) : ''); ?>" disabled>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" id="btnSalvarPerfil">Salvar Alterações</button>
                </div>
            </form>
        <?php elseif(!$id_usuario): ?>
            <p style="text-align: center; color: #721c24;">ID do usuário não encontrado na sessão. Não é possível carregar o perfil.</p>
        <?php else: ?>
            <p style="text-align: center; color: #721c24;">Não foi possível carregar os dados do usuário. Verifique se o usuário existe no banco de dados.</p>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const formPerfil = document.getElementById('formMeuPerfil');
        const btnSalvarPerfil = document.getElementById('btnSalvarPerfil');

        if (formPerfil && btnSalvarPerfil) {
            btnSalvarPerfil.addEventListener('click', function(event) {
                event.preventDefault(); // Previne o envio padrão

                const nomeInput = document.getElementById('nome');
                const emailInput = document.getElementById('email');

                // Validação simples no cliente
                if (!nomeInput.value.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Campo Obrigatório',
                        text: 'Por favor, preencha o campo Nome.',
                    });
                    return;
                }
                if (!emailInput.value.trim()) {
                     Swal.fire({
                        icon: 'error',
                        title: 'Campo Obrigatório',
                        text: 'Por favor, preencha o campo Email.',
                    });
                    return;
                }
                // Validação de formato de email (básica)
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(emailInput.value.trim())) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Email Inválido',
                        text: 'Por favor, insira um endereço de email válido.',
                    });
                    return;
                }


                Swal.fire({
                    title: 'Confirmar Alterações',
                    text: "Tem certeza que deseja salvar as alterações no seu perfil?",
                    icon: 'question', // 'warning' ou 'question' são boas opções
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sim, salvar!',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formPerfil.submit(); // Submete o formulário se confirmado
                    }
                });
            });
        }

        // Script para exibir o SweetAlert de resultado do PHP (já presente no seu código original)
        <?php
        if (!empty($mensagem) && !empty($tipo_mensagem)) {
            echo "Swal.fire({
                    title: '" . ($tipo_mensagem === 'success' ? 'Sucesso!' : 'Erro!') . "',
                    text: '" . addslashes($mensagem) . "',
                    icon: '" . $tipo_mensagem . "',
                    confirmButtonText: 'OK'
                  });";
        }
        ?>
    });
    </script>
    <?php
        // Verifique se o arquivo existe antes de incluir
        if (file_exists('../includes/header.js')) {
            echo '<script src="../includes/header.js"></script>';
        }
    ?>
</body>
</html>