<?php
// Arquivo para inicializar o banco de dados do PetPlus

// Configurações de conexão
$host = "localhost";
$usuario = "root";
$senha = "";

// Conecta ao servidor MySQL sem selecionar um banco de dados
$conn = new mysqli($host, $usuario, $senha);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Cria o banco de dados se não existir
$sql = "CREATE DATABASE IF NOT EXISTS PetPlus";
if ($conn->query($sql) === TRUE) {
    echo "Banco de dados criado ou já existente.<br>";
} else {
    echo "Erro ao criar banco de dados: " . $conn->error . "<br>";
}

// Seleciona o banco de dados
$conn->select_db("PetPlus");

// Cria as tabelas necessárias

// Tabela Usuarios
$sql = "CREATE TABLE IF NOT EXISTS Usuarios (
    id_usuario INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(50) NOT NULL,
    cpf CHAR(11) UNIQUE NOT NULL,
    data_nasc DATE NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela Usuarios criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela Usuarios: " . $conn->error . "<br>";
}

// Tabela Tutor
$sql = "CREATE TABLE IF NOT EXISTS Tutor (
    id_tutor INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    telefone VARCHAR(20),
    endereco VARCHAR(100),
    Codigo_do_pet CHAR(10)
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela Tutor criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela Tutor: " . $conn->error . "<br>";
}

// Tabela Pet
$sql = "CREATE TABLE IF NOT EXISTS Pet (
    id_pet INT PRIMARY KEY AUTO_INCREMENT,
    id_tutor INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    especie VARCHAR(15) NOT NULL,
    raca VARCHAR(50),
    idade INT NOT NULL,
    sexo VARCHAR(10) NOT NULL,
    peso_atual DECIMAL(5,2),
    descricao TEXT,
    FOREIGN KEY (id_tutor) REFERENCES Tutor(id_tutor) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela Pet criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela Pet: " . $conn->error . "<br>";
}

// Tabela consultas
$sql = "CREATE TABLE IF NOT EXISTS consultas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    data DATE NOT NULL,
    descricao TEXT,
    status VARCHAR(50) DEFAULT 'agendada',
    FOREIGN KEY (pet_id) REFERENCES Pet(id_pet) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela consultas criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela consultas: " . $conn->error . "<br>";
}

// Tabela diagnosticos
$sql = "CREATE TABLE IF NOT EXISTS diagnosticos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    consulta_id INT NOT NULL,
    sintomas TEXT,
    exames TEXT,
    prescricao TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela diagnosticos criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela diagnosticos: " . $conn->error . "<br>";
}

// Tabela pesos
$sql = "CREATE TABLE IF NOT EXISTS pesos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    data DATE NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (pet_id) REFERENCES Pet(id_pet) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela pesos criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela pesos: " . $conn->error . "<br>";
}

// Tabela vacinas
$sql = "CREATE TABLE IF NOT EXISTS vacinas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pet_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    data DATE NOT NULL,
    lote VARCHAR(50),
    reforco DATE,
    FOREIGN KEY (pet_id) REFERENCES Pet(id_pet) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela vacinas criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela vacinas: " . $conn->error . "<br>";
}

// Tabela atendimentos
$sql = "CREATE TABLE IF NOT EXISTS atendimentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome_tutor VARCHAR(100) NOT NULL,
    nome_pet VARCHAR(100) NOT NULL,
    data DATE NOT NULL,
    descricao TEXT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Tabela atendimentos criada ou já existente.<br>";
} else {
    echo "Erro ao criar tabela atendimentos: " . $conn->error . "<br>";
}

// Insere um tutor de exemplo se não existir
$sql = "SELECT * FROM Tutor LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "INSERT INTO Tutor (nome, telefone, endereco, Codigo_do_pet) 
            VALUES ('Tutor de Teste', '(11) 98765-4321', 'Rua Exemplo, 123', 'PET001')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Tutor de exemplo inserido com sucesso.<br>";
    } else {
        echo "Erro ao inserir tutor de exemplo: " . $conn->error . "<br>";
    }
}

// Insere um usuário de exemplo se não existir
$sql = "SELECT * FROM Usuarios LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Senha: senha123
    $senha_hash = password_hash('senha123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO Usuarios (email, senha, nome, cpf, data_nasc) 
            VALUES ('admin@petplus.com', '$senha_hash', 'Administrador', '12345678901', '1990-01-01')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Usuário de exemplo inserido com sucesso.<br>";
    } else {
        echo "Erro ao inserir usuário de exemplo: " . $conn->error . "<br>";
    }
}

echo "<br>Inicialização do banco de dados concluída!";

// Fecha a conexão
$conn->close();
?>
