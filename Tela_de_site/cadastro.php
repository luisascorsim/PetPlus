<?php
include 'C:\xampp\htdocs\PetPlus\conecta_db.php';

// estabelece a conxão
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
    $check = mysqli_query($conn, "SELECT email, cpf FROM Usuarios WHERE email = '{$dados['email']}' OR cpf = '{$dados['cpf']}'");

    if (mysqli_num_rows($check) > 0) {
        die(json_encode(['status' => 'error', 'message' => 'Email ou CPF já cadastrado']));
    }

    // Insere na tabela Usuarios
    $sql = "INSERT INTO Usuarios (nome, email, senha, cpf, data_nasc) VALUES (
        '{$dados['nome']}',
        '{$dados['email']}',
        '{$dados['senha']}',
        '{$dados['cpf']}',
        '{$dados['data_nasc']}'
    )";

    if (mysqli_query($conn, $sql)) {
        header('Location: login.php');
        exit();
        
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
    // fecha a conexão
    mysqli_close($conn);
}
?>