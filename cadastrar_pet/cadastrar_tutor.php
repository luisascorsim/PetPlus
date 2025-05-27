<?php
// Inicia a sessão e verifica login
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../Tela_de_site/login.php');
    exit();
}

// Inclui o arquivo de conexão
require_once('../conecta_db.php');
$conn = conecta_db();

// Processa o formulário quando enviado
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Dados do tutor
    $nome_tutor = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $cpf_tutor = isset($_POST['cpf']) ? trim($_POST['cpf']) : '';
    $email_tutor = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telefone_tutor = isset($_POST['telefone']) ? trim($_POST['telefone']) : '';
    $endereco_tutor = isset($_POST['endereco']) ? trim($_POST['endereco']) : '';

    // Inserir novo tutor
    $sql = "INSERT INTO Tutor (nome, cpf, email, telefone,  endereco) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $nome_tutor, $cpf_tutor, $email_tutor, $telefone_tutor, $endereco_tutor);
                
    if ($stmt->execute()) {
    $mensagem = "Tutor cadastrado com sucesso!";
    $tipo_mensagem = "sucesso";
                    
    } else {
        $mensagem = "Erro ao cadastrar tutor: " . $conn->error;
        $tipo_mensagem = "erro";
    }
               
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
            // Verificar se o CPF já está cadastrado
            $sql = "SELECT id_tutor FROM Tutor WHERE cpf = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $cpf_limpo);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $mensagem = "Este CPF já está cadastrado no sistema.";
                $tipo_mensagem = "erro";
            } else {
                // Redirecionar para a página de cadastro de pets
                header("Location: cadastrar_pets.php?mensagem=" . urlencode($mensagem) . "&tipo=" . urlencode($tipo_mensagem));
                exit();
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

// Redirecionar de volta para a página de cadastro de pets com a mensagem
header("Location: cadastrar_pets.php?mensagem=" . urlencode($mensagem) . "&tipo=" . urlencode($tipo_mensagem));
exit();
?>