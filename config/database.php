<?php
// Configurações do banco de dados
$host = 'localhost';
$dbname = 'petplus';
$username = 'root';
$password = 'PUC@1234';
$charset = 'utf8mb4';

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// String de conexão (DSN)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

try {
    // Criar conexão PDO
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Em ambiente de produção, você não deve exibir a mensagem de erro
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>
