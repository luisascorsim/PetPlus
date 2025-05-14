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

// Verifica se o ID do tutor foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: cadastrar_pets.php');
    exit();
}

$id_tutor = (int)$_GET['id'];

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados do tutor
    $nome_tutor = isset($_POST['nome_tutor']) ? trim($_POST['nome_tutor']) : '';
    $cpf_tutor = isset($_POST['cpf_tutor']) ? trim($_POST['cpf_tutor']) : '';
    $telefone_tutor = isset($_POST['telefone_tutor']) ? trim($_POST['telefone_tutor']) : '';
    $email_tutor = isset($_POST['email_tutor']) ? trim($_POST['email_tutor']) : '';
    $endereco_tutor = isset($_POST['endereco_tutor']) ? trim($_POST['endereco_tutor']) : '';
    
    // Validações
    if (empty($nome_tutor) || empty($cpf_tutor) || empty($telefone_tutor)) {
        $mensagem = "Nome, CPF e telefone são campos obrigatórios.";
        $tipo_mensagem = "erro";
    } else {
        // Limpar formatação do CPF
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_tutor);
        
        // Validar CPF
        if (!validarCPF($cpf_limpo)) {
            $mensagem = "CPF inválido. Por favor, verifique.";
            $tipo_mensagem = "erro";
        } else {
            // Verificar se o CPF já está cadastrado para outro tutor
            $sql = "SELECT id_tutor FROM Tutor WHERE cpf = ? AND id_tutor != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $cpf_limpo, $id_tutor);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensagem = "Este CPF já está cadastrado para outro tutor.";
                $tipo_mensagem = "erro";
            } else {
                // Atualizar tutor
                $sql = "UPDATE Tutor SET nome = ?, cpf = ?, telefone = ?, email = ?, endereco = ? WHERE id_tutor = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $nome_tutor, $cpf_limpo, $telefone_tutor, $email_tutor, $endereco_tutor, $id_tutor);
                
                if ($stmt->execute()) {
                    $mensagem = "Tutor atualizado com sucesso!";
                    $tipo_mensagem = "sucesso";
                } else {
                    $mensagem = "Erro ao atualizar tutor: " . $conn->error;
                    $tipo_mensagem = "erro";
                }
            }
        }
    }
}

// Função para validar CPF
function validarCPF($cpf) {
    // Elimina possível máscara
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Verifica se o número de dígitos informados é igual a 11
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verifica se nenhuma das sequências inválidas abaixo foi digitada
    if ($cpf == '00000000000' || 
        $cpf == '11111111111' || 
        $cpf == '22222222222' || 
        $cpf == '33333333333' || 
        $cpf == '44444444444' || 
        $cpf == '55555555555' || 
        $cpf == '66666666666' || 
        $cpf == '77777777777' || 
        $cpf == '88888888888' || 
        $cpf == '99999999999') {
        return false;
    }
    
    // Calcula os dígitos verificadores para verificar se o CPF é válido
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Buscar dados do tutor
$sql = "SELECT * FROM Tutor WHERE id_tutor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_tutor);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: cadastrar_pets.php');
    exit();
}

$tutor = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tutor - PetPlus</title>
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
            <h1>Editar Tutor</h1>
            
            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>
            
            <form action="editar_tutor.php?id=<?php echo $id_tutor; ?>" method="POST">
                <div class="form-group">
                    <label for="nome_tutor">Nome do Tutor*</label>
                    <input type="text" id="nome_tutor" name="nome_tutor" value="<?php echo htmlspecialchars($tutor['nome']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="cpf_tutor">CPF*</label>
                    <input type="text" id="cpf_tutor" name="cpf_tutor" value="<?php echo htmlspecialchars($tutor['cpf']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="telefone_tutor">Telefone*</label>
                    <input type="text" id="telefone_tutor" name="telefone_tutor" value="<?php echo htmlspecialchars($tutor['telefone']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="email_tutor">E-mail</label>
                    <input type="email" id="email_tutor" name="email_tutor" value="<?php echo htmlspecialchars($tutor['email'] ?? ''); ?>" />
                </div>
                
                <div class="form-group">
                    <label for="endereco_tutor">Endereço</label>
                    <input type="text" id="endereco_tutor" name="endereco_tutor" value="<?php echo htmlspecialchars($tutor['endereco'] ?? ''); ?>" />
                </div>
                
                <button type="submit" class="btn-primary">Atualizar Tutor</button>
                <a href="cadastrar_pets.php" class="btn-secondary">Voltar</a>
            </form>
        </div>
    </div>
    
    <script>
        // Máscara para CPF
        document.getElementById('cpf_tutor').addEventListener('input', function(e) {
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
        document.getElementById('telefone_tutor').addEventListener('input', function(e) {
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

<?php
$conn->close();
?>
