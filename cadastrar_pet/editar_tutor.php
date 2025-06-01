<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o header e a barra lateral
require_once('../includes/header.php'); // Supondo que SweetAlert2 não será incluído aqui
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
$tipo_mensagem = ''; // 'sucesso' ou 'erro' no código original

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_tutor = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $cpf_tutor = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
    $email_tutor = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefone_tutor = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $endereco_tutor = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';

    if (empty($nome_tutor) || empty($cpf_tutor) || empty($telefone_tutor)) {
        $mensagem = "Nome, CPF e telefone são campos obrigatórios.";
        $tipo_mensagem = "erro";
    } else {
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_tutor);
        
        // A validação de CPF estava comentada, vou manter assim.
        // if (!validarCPF($cpf_limpo)) {
        //     $mensagem = "CPF inválido. Por favor, verifique.";
        //     $tipo_mensagem = "erro";
        // } else {
            $sql_check_cpf = "SELECT id_tutor FROM Tutor WHERE cpf = ? AND id_tutor != ?";
            $stmt_check = $conn->prepare($sql_check_cpf);
            $stmt_check->bind_param("si", $cpf_limpo, $id_tutor);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $mensagem = "Este CPF já está cadastrado para outro tutor.";
                $tipo_mensagem = "erro";
            } else {
                $sql_update = "UPDATE Tutor SET nome = ?, cpf = ?, telefone = ?, email = ?, endereco = ? WHERE id_tutor = ?";
                $stmt_update = $conn->prepare($sql_update);
                // Bind param com cpf_limpo
                $stmt_update->bind_param("sssssi", $nome_tutor, $cpf_limpo, $telefone_tutor, $email_tutor, $endereco_tutor, $id_tutor);
                
                if ($stmt_update->execute()) {
                    $mensagem = "Tutor atualizado com sucesso!";
                    $tipo_mensagem = "sucesso";
                } else {
                    $mensagem = "Erro ao atualizar tutor: " . $conn->error;
                    $tipo_mensagem = "erro";
                }
            }
        // } // Fim do else da validação de CPF comentada
    }
}

// Função validarCPF (mantida como no original, embora a chamada esteja comentada)
function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false; // Verifica sequências repetidas
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// Buscar dados do tutor para preencher o formulário
$sql_tutor_data = "SELECT * FROM Tutor WHERE id_tutor = ?";
$stmt_data = $conn->prepare($sql_tutor_data);
$stmt_data->bind_param("i", $id_tutor);
$stmt_data->execute();
$result_data = $stmt_data->get_result();

if ($result_data->num_rows === 0) {
    // Se o tutor não for encontrado, redireciona com mensagem (será tratada em cadastrar_pets)
    $_SESSION['mensagem_flash'] = ['tipo' => 'erro', 'texto' => 'Tutor não encontrado para edição.'];
    header('Location: cadastrar_pets.php#tab-tutores');
    exit();
}
$tutor = $result_data->fetch_assoc();

// Preparar mensagens para SweetAlert2
$mensagem_swal = '';
$tipo_mensagem_swal = ''; // 'success', 'error', 'warning', 'info'

if (!empty($mensagem) && !empty($tipo_mensagem)) {
    $mensagem_swal = addslashes($mensagem);
    if ($tipo_mensagem === 'sucesso') $tipo_mensagem_swal = 'success';
    else if ($tipo_mensagem === 'erro') $tipo_mensagem_swal = 'error';
    else $tipo_mensagem_swal = 'info';
    
    // Limpar para não usar o display antigo
    $mensagem = ''; 
    $tipo_mensagem = '';
}

// $conn->close(); // Fechamento da conexão movido para o final do script HTML
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tutor - PetPlus</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        h1 { /* Removido h2 pois não há nesta página */
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
        .form-group textarea { /* select e textarea não são usados aqui, mas mantendo o estilo */
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Adicionado para consistência */
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
            margin-top: 10px; /* Pode ser ajustado se estiver muito próximo do botão Atualizar */
            margin-left: 10px; /* Adicionado para espaçamento */
            text-decoration: none;
            display: inline-block;
        }
        
        /* Removido o estilo de .mensagem, .mensagem-sucesso, .mensagem-erro */
        
        @media (max-width: 768px) {
            .container {
                margin-left: 60px; /* Ou 0 se a sidebar colapsar */
                padding: 70px 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Editar Tutor</h1>
            
            <?php /* if ($mensagem): ?> // Bloco de mensagem original comentado/removido
            <div class="mensagem <?php echo $tipo_mensagem === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro'; ?>">
                <?php echo $mensagem; ?>
            </div>
            <?php endif; */ ?>
            
            <form action="editar_tutor.php?id=<?php echo $id_tutor; ?>" method="POST">
                <div class="form-group">
                    <label for="nome_tutor">Nome do Tutor*</label>
                    <input type="text" id="nome_tutor" name="nome" value="<?php echo htmlspecialchars($tutor['nome']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="cpf_tutor">CPF*</label>
                    <input type="text" id="cpf_tutor" name="cpf" value="<?php echo htmlspecialchars($tutor['cpf']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="telefone_tutor">Telefone*</label>
                    <input type="text" id="telefone_tutor" name="telefone" value="<?php echo htmlspecialchars($tutor['telefone']); ?>" required />
                </div>
                
                <div class="form-group">
                    <label for="email_tutor">E-mail</label>
                    <input type="email" id="email_tutor" name="email" value="<?php echo htmlspecialchars($tutor['email'] ?? ''); ?>" />
                </div>
                
                <div class="form-group">
                    <label for="endereco_tutor">Endereço</label>
                    <input type="text" id="endereco_tutor" name="endereco" value="<?php echo htmlspecialchars($tutor['endereco'] ?? ''); ?>" />
                </div>
                
                <button type="submit" class="btn-primary">Atualizar Tutor</button>
                <a href="cadastrar_pets.php#tab-tutores" class="btn-secondary">Voltar</a> </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($mensagem_swal) && !empty($tipo_mensagem_swal)): ?>
            Swal.fire({
                title: '<?php echo ($tipo_mensagem_swal === "success" ? "Sucesso!" : ($tipo_mensagem_swal === "error" ? "Erro!" : "Atenção!")); ?>',
                html: '<?php echo $mensagem_swal; ?>',
                icon: '<?php echo $tipo_mensagem_swal; ?>',
                confirmButtonColor: '#0b3556'
            });
            <?php endif; ?>
        });

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
            
            if (value.length > 10) { // Celular com 9 dígitos + DDD
                value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length > 6) { // Fixo ou celular com 8 dígitos + DDD
                value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})$/, '($1) $2');
            } else if (value.length > 0) {
                 value = value.replace(/^(\d*)$/, '($1');
            }
            e.target.value = value;
        });
    </script>
</body>
</html>

<?php
if ($conn) { // Verifica se a conexão ainda está aberta antes de fechar
    $conn->close();
}
?>