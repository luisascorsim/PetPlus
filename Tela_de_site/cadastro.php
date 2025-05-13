<?php
include '../conecta_db.php';

// estabelece a conexão
$conn = conecta_db();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dados = [
        'nome' => $_POST['nome'],
        'email' => $_POST['email'],
        'senha' => password_hash($_POST['senha'], PASSWORD_DEFAULT),
        'cpf' => $_POST['cpf'],
        'data_nasc' => $_POST['data_nasc']
    ];

    // Verifica se o email ou cpf já existe
    $check_query = "SELECT email, cpf FROM Usuarios WHERE email = ? OR cpf = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ss", $dados['email'], $dados['cpf']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email ou CPF já cadastrado']);
        exit;
    }

    // Insere na tabela Usuarios
    $sql = "INSERT INTO Usuarios (nome, email, senha, cpf, data_nasc) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $dados['nome'], $dados['email'], $dados['senha'], $dados['cpf'], $dados['data_nasc']);

    if ($stmt->execute()) {
        header('Location: login.php');
        exit();
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
    
    // fecha a conexão
    $stmt->close();
    $conn->close();
}
?>
