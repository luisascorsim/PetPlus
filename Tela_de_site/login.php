<?php
include '../conecta_db.php'; // Garanta que o caminho está correto
$conn = conecta_db();

$error_message_for_sweetalert = '';
$login_success = false; // Flag para indicar sucesso no login

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $senha = $_POST['senha']; // A senha será verificada com password_verify

    $sql = "SELECT id_usuario, nome, senha FROM Usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $usuario = $result->fetch_assoc();
            if (password_verify($senha, $usuario['senha'])) {
                session_start();
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nome'] = $usuario['nome'];
                $login_success = true; // Marcar login como sucesso
                // O redirecionamento será feito via JavaScript após o SweetAlert de sucesso
            }
        }
        $stmt->close();
    } else {
        // Erro na preparação da query (pode ser logado ou tratado)
        $error_message_for_sweetalert = "Ocorreu um erro no servidor. Tente novamente mais tarde.";
    }
    
    if (!$login_success && empty($error_message_for_sweetalert)) { // Se o login não teve sucesso e nenhuma outra msg de erro foi definida
        $error_message_for_sweetalert = 'E-mail ou senha incorretos. Tente novamente.';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PetPlus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #e3f2fd 0%, #ffffff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        .background-image {
            position: fixed;
            left: 10%;
            top: 50%;
            transform: translateY(-50%);
            max-width: 40%;
            height: auto;
            opacity: 0.08;
            z-index: -1;
            pointer-events: none;
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .login-box {
            background: white;
            border-radius: 20px;
            padding: 3rem 2.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            border: 1px solid rgba(33, 150, 243, 0.1);
            text-align: center;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #2196f3, #21cbf3);
            border-radius: 20px 20px 0 0;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo img {
            height: 60px;
            width: auto;
        }

        .botao-voltar {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid #e0e0e0;
        }

        .botao-voltar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: #2196f3;
        }

        .icone-botao {
            width: 20px;
            height: 20px;
            filter: invert(0.5);
            transition: filter 0.3s ease;
        }

        .botao-voltar:hover .icone-botao {
            filter: invert(0.3) sepia(1) saturate(5) hue-rotate(200deg);
        }

        h2 {
            color: #333;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 2rem;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: #2196f3;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
            font-size: 0.95rem;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #2196f3;
            background: white;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
            transform: translateY(-2px);
        }
        
        .input-valid {
            border-color: #4caf50 !important;
            background: #f8fff8 !important;
        }

        .input-invalid {
            border-color: #f44336 !important;
            background: #fff8f8 !important;
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
            font-size: 1.2rem;
        }

        .password-toggle:hover {
            color: #2196f3;
        }

        .recuperar {
            text-align: right;
            margin-top: 0.5rem;
        }

        .recuperar a {
            color: #2196f3;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .recuperar a:hover {
            color: #1976d2;
            text-decoration: underline;
        }

        button[type="submit"] {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #2196f3, #21cbf3);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(33, 150, 243, 0.3);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .cadastro-texto {
            margin-top: 2rem;
            color: #666;
            font-size: 0.95rem;
        }

        .cadastro-texto a {
            color: #2196f3;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .cadastro-texto a:hover {
            color: #1976d2;
            text-decoration: underline;
        }

        /* Success animation (mantida conforme o original) */
        .success-animation {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            z-index: 1000;
            display: none; /* Inicialmente escondido */
        }

        .success-animation.show {
            display: block;
            animation: successPulse 0.6s ease;
        }

        @keyframes successPulse {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }

        /* Loading state */
        .loading button[type="submit"] { /* Aplicar loading ao botão */
            background: #ccc !important; /* Cor de fundo durante o loading */
            cursor: not-allowed !important;
            color: #888 !important; /* Cor do texto durante o loading */
        }
        
        .loading button[type="submit"]::after { /* Spinner no botão */
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin-top: -10px; /* Metade da altura */
            margin-left: -10px; /* Metade da largura */
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }


        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
                max-width: 100%;
            }
            .login-box {
                padding: 2rem 1.5rem;
                border-radius: 15px;
            }
            h2 { font-size: 1.5rem; }
            .background-image { opacity: 0.05; left: 5%;}
        }

        @media (max-width: 480px) {
            .login-box { padding: 1.5rem 1rem; }
            .botao-voltar { width: 40px; height: 40px; top: 15px; left: 15px; }
            .icone-botao { width: 16px; height: 16px; }
        }
    </style>
</head>
<body>
    <img src="gatoecao.jpg" alt="Gato e Cão" class="background-image"> <div class="container">
        <div class="login-box">
            <div class="logo">
                <img src="logo.png" alt="Logo PetPlus" />
            </div>
            
            <a href="tela_site.html" class="botao-voltar"> <img src="seta.png" alt="Voltar" class="icone-botao" /> </a>
            
            <h2>Entrar no PetPlus</h2>

            <form method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="seu@email.com" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="password-container">
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                        <span class="password-toggle" onclick="togglePassword()">👁️</span>
                    </div>
                    <div class="recuperar">
                        <a href="#" onclick="showRecoverModal(event)">Esqueceu sua senha?</a>
                    </div>
                </div>

                <button type="submit">Entrar</button>
            </form>

            <p class="cadastro-texto">
                Não tem uma conta? <a href="cadastro.html">Crie a sua conta aqui</a> </p>
        </div>
    </div>

    <div class="success-animation" id="successAnimation">
        <div style="font-size: 3rem; color: #4caf50; margin-bottom: 1rem;">✅</div>
        <h3 style="color: #333; margin-bottom: 0.5rem;">Login realizado com sucesso!</h3>
        <p style="color: #666;">Redirecionando...</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const toggleBtn = document.querySelector('.password-toggle');
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                senhaInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('senha');

        emailField.addEventListener('blur', function(e) {
            const email = e.target.value;
            if (email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                e.target.classList.add('input-valid');
                e.target.classList.remove('input-invalid');
            } else if (email) {
                e.target.classList.add('input-invalid');
                e.target.classList.remove('input-valid');
            } else {
                e.target.classList.remove('input-valid', 'input-invalid');
            }
        });

        passwordField.addEventListener('input', function(e) {
            const senha = e.target.value;
            // Apenas um exemplo simples, ajuste a regra de validação de senha conforme necessário
            if (senha.length >= 6) { 
                e.target.classList.add('input-valid');
                e.target.classList.remove('input-invalid');
            } else if (senha.length > 0) {
                e.target.classList.add('input-invalid');
                e.target.classList.remove('input-valid');
            } else {
                e.target.classList.remove('input-valid', 'input-invalid');
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = emailField.value;
            const senha = passwordField.value;
            const form = this; // Referência ao formulário
            const submitBtn = form.querySelector('button[type="submit"]');

            let clientSideValid = true;
            let errorText = '';

            if (!email && !senha) {
                errorText = 'Por favor, preencha e-mail e senha.';
                clientSideValid = false;
                emailField.classList.add('input-invalid');
                passwordField.classList.add('input-invalid');
            } else if (!email) {
                errorText = 'Por favor, preencha o campo de e-mail.';
                clientSideValid = false;
                emailField.classList.add('input-invalid');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorText = 'Por favor, insira um e-mail válido.';
                clientSideValid = false;
                emailField.classList.add('input-invalid');
            }

            if (!senha && clientSideValid) { // Só verifica senha se e-mail passou ou se e-mail também está vazio
                 errorText = 'Por favor, preencha o campo de senha.';
                 clientSideValid = false;
                 passwordField.classList.add('input-invalid');
            } else if (senha && senha.length < 6 && clientSideValid) { // Senha curta
                 errorText = 'A senha deve ter pelo menos 6 caracteres.';
                 clientSideValid = false;
                 passwordField.classList.add('input-invalid');
            }
            
            if (!clientSideValid) {
                e.preventDefault(); // Impede o envio do formulário
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos Inválidos',
                    text: errorText,
                    confirmButtonColor: '#2196f3',
                    confirmButtonText: 'OK'
                });
            } else {
                // Se a validação do lado do cliente passar, adiciona o estado de loading
                form.classList.add('loading');
                submitBtn.disabled = true;
                // O texto do botão 'Entrando...' será adicionado pelo CSS via ::after,
                // mas você pode adicionar aqui se preferir, removendo o spinner de texto no CSS.
                // submitBtn.textContent = 'Entrando...'; 
            }
        });

        function showRecoverModal(event) {
            event.preventDefault(); // Previne o comportamento padrão do link
            Swal.fire({
                title: 'Recuperar Senha',
                input: 'email',
                inputLabel: 'Digite seu e-mail para recuperação de senha:',
                inputPlaceholder: 'seu@email.com',
                showCancelButton: true,
                confirmButtonText: 'Enviar Instruções',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2196f3',
                cancelButtonColor: '#aaa',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Você precisa digitar um e-mail!'
                    }
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        return 'Por favor, digite um e-mail válido.'
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const email = result.value;
                    // Simulação de envio para o backend (gente é só uma simulação, não conecta com nenhum DB)
                    Swal.fire({
                        icon: 'success',
                        title: 'Instruções Enviadas!',
                        text: `Se o e-mail ${email} estiver cadastrado, você receberá as instruções.`,
                        confirmButtonColor: '#2196f3'
                    });
                }
            });
        }

        window.addEventListener('load', function() {
            document.querySelector('.login-box').style.opacity = '0';
            document.querySelector('.login-box').style.transform = 'translateY(30px)';
            setTimeout(() => {
                document.querySelector('.login-box').style.transition = 'all 0.6s ease';
                document.querySelector('.login-box').style.opacity = '1';
                document.querySelector('.login-box').style.transform = 'translateY(0)';
            }, 100);
        });

        document.addEventListener('DOMContentLoaded', function() {
            emailField.focus();

            // Script para exibir SweetAlert de erro do PHP, se houver
            <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($error_message_for_sweetalert)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Falha no Login',
                text: '<?php echo htmlspecialchars($error_message_for_sweetalert, ENT_QUOTES, 'UTF-8'); ?>',
                confirmButtonColor: '#2196f3',
                confirmButtonText: 'Tentar Novamente'
            });
            // Remove o estado de loading se a página recarregou com erro do PHP
            const loginForm = document.getElementById('loginForm');
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            loginForm.classList.remove('loading');
            if(submitBtn) submitBtn.disabled = false;
            <?php endif; ?>

            // Script para exibir animação de sucesso e redirecionar, se o login PHP foi bem-sucedido
            <?php if ($login_success): ?>
            const successAnimationDiv = document.getElementById('successAnimation');

            if (successAnimationDiv) {
                successAnimationDiv.classList.add('show');
                setTimeout(() => {
                    window.location.href = '../home/index.php'; // Ajuste o caminho se necessário
                }, 1400); // Tempo para ver a animação
            } else { // Fallback para SweetAlert se a div customizada não existir
                Swal.fire({
                    icon: 'success',
                    title: 'Login realizado com sucesso!',
                    text: 'Redirecionando...',
                    timer: 1400, // Mesmo tempo do timeout acima
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    willClose: () => {
                        window.location.href = '../home/index.php'; // Ajuste o caminho se necessário
                    }
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>