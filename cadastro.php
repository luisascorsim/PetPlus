<?php
header('Content-Type: application/json');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Incluir arquivo de conexão com o banco de dados
require_once 'config/database.php';

// Obter dados do formulário
$nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$senha = isset($_POST['senha']) ? $_POST['senha'] : '';
$confirmarSenha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';
$cpf = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';

// Validar dados
if (empty($nome) || empty($email) || empty($usuario) || empty($senha) || empty($confirmarSenha) || empty($cpf)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, preencha todos os campos']);
    exit;
}

if ($senha !== $confirmarSenha) {
    echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
    exit;
}

if (strlen($senha) < 8) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres']);
    exit;
}

// Validar CPF
function validaCPF($cpf) {
    // Remove caracteres especiais do CPF
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se o CPF tem 11 dígitos
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica o primeiro dígito verificador
    if ($cpf[9] != $dv1) {
        return false;
    }
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica o segundo dígito verificador
    if ($cpf[10] != $dv2) {
        return false;
    }
    
    return true;
}

if (!validaCPF($cpf)) {
    echo json_encode(['success' => false, 'message' => 'CPF inválido. Por favor, verifique e tente novamente.']);
    exit;
}

try {
    // Verificar se o e-mail já está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este e-mail já está cadastrado']);
        exit;
    }
    
    // Verificar se o nome de usuário já está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Este nome de usuário já está em uso']);
        exit;
    }
    
    // Hash da senha
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inserir novo usuário
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, usuario, senha, cpf, data_cadastro) VALUES (:nome, :email, :usuario, :senha, :cpf, NOW())");
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->bindParam(':senha', $senhaHash);
    $stmt->bindParam(':cpf', $cpf);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação: ' . $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - PetPlus</title>
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/cadastro.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h1>Cadastro</h1>
            <form id="cadastro-form" method="post">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" required>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="usuario">Nome de Usuário</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <button type="submit" class="btn-cadastrar">Cadastrar</button>
                
                <p class="login-link">Já tem uma conta? <a href="login.php">Faça login</a></p>
            </form>
        </div>
    </div>
    
    <script src="assets/js/cadastro.js"></script>
    <script>
        // Adicionar máscara para o CPF
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
    </script>
</body>
</html>
