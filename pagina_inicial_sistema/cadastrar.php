<?php
include 'conecta_db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar se os dados foram enviados e não estão vazios
    if (isset($_POST['nome']) && isset($_POST['tipo']) && !empty($_POST['nome']) && !empty($_POST['tipo'])) {
        $nome = $_POST['nome'];
        $tipo = $_POST['tipo'];

        // Conectar ao banco de dados
        $oMysql = conecta_db();

        // Sanitizar as entradas para evitar SQL Injection
        $nome = mysqli_real_escape_string($oMysql, $nome);
        $tipo = mysqli_real_escape_string($oMysql, $tipo);

        // Preparar a consulta SQL
        $query = "INSERT INTO cadastros (nome, tipo) VALUES ('$nome', '$tipo')";

        // Executar a consulta
        if (mysqli_query($oMysql, $query)) {
            echo json_encode(["status" => "success", "message" => "Cadastro realizado com sucesso."]);
        } else {
            // Exibir erro detalhado para facilitar a depuração
            echo json_encode(["status" => "error", "message" => "Erro ao cadastrar. " . mysqli_error($oMysql)]);
        }

        // Fechar a conexão com o banco de dados
        mysqli_close($oMysql);
    } else {
        echo json_encode(["status" => "error", "message" => "Nome e tipo são obrigatórios."]);
    }
}
?>
