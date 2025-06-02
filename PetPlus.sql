CREATE DATABASE IF NOT EXISTS petplus;
USE petplus;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS Usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    data_nasc DATE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    tipo_usuario enum('administrador','veterinario','tutor') NOT NULL DEFAULT 'administrador'
);

INSERT INTO Usuarios (id_usuario, nome, email, senha, tipo_usuario) VALUES
(1, 'Dra. Ana Silva', 'ana@clinica.com', 'senha123', 'veterinario'),
(2, 'Dr. Carlos Souza', 'carlos@clinica.com', 'senha456', 'veterinario');

-- Tabela de Tutores
CREATE TABLE IF NOT EXISTS Tutor (
    id_tutor INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    email VARCHAR(100),
    telefone VARCHAR(15),
    endereco VARCHAR(200),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Pets
CREATE TABLE IF NOT EXISTS Pets (
    id_pet INT AUTO_INCREMENT PRIMARY KEY,
    id_tutor INT,
    nome VARCHAR(50) NOT NULL,
    especie VARCHAR(50),
    raca VARCHAR(50),
    data_nascimento DATE,
    sexo ENUM('M', 'F'),
    cor VARCHAR(50),
    peso DECIMAL(5,2),
    observacoes TEXT,
    FOREIGN KEY (id_tutor) REFERENCES Tutor(id_tutor) ON DELETE CASCADE
);

-- Criação da tabela de consultas
CREATE TABLE IF NOT EXISTS consultas (
  id int(11) NOT NULL AUTO_INCREMENT,
  pet_id int(11) NOT NULL,
  data_consulta datetime NOT NULL,
  motivo varchar(255) NOT NULL,
  diagnostico text,
  tratamento text,
  observacoes text,
  veterinario_id int(11) DEFAULT NULL,
  status_co enum('agendada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'agendada',
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY pet_id (pet_id),
  KEY veterinario_id (veterinario_id),
  CONSTRAINT consultas_ibfk_1 FOREIGN KEY (pet_id) REFERENCES Pets (id_pet) ON DELETE CASCADE,
  CONSTRAINT consultas_ibfk_2 FOREIGN KEY (veterinario_id) REFERENCES Usuarios (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT * FROM Usuarios WHERE id_usuario IN (1, 2);

-- Tabela de Serviços
CREATE TABLE IF NOT EXISTS Servicos (
    id_servico INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    duracao INT,
    categoria VARCHAR(100) NOT NULL,
    status_s ENUM('ativo', 'inativo') DEFAULT 'ativo'
);


-- Tabela de Consultas
CREATE TABLE IF NOT EXISTS Consultas_c (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    data_c DATETIME NOT NULL,
    descricao TEXT,
    status_c ENUM('agendada', 'em andamento', 'concluída', 'cancelada') DEFAULT 'agendada',
    FOREIGN KEY (pet_id) REFERENCES Pets(id_pet) ON DELETE CASCADE
);

-- Tabela de Diagnósticos
CREATE TABLE IF NOT EXISTS Diagnosticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL,
    sintomas TEXT NOT NULL,
    exames TEXT,
    prescricao TEXT,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES Consultas(id) ON DELETE CASCADE
);

-- Tabela de Vacinas
CREATE TABLE IF NOT EXISTS Vacinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    data_aplicacao DATE NOT NULL,
    data_proxima DATE,
    lote VARCHAR(50),
    observacoes TEXT,
    FOREIGN KEY (pet_id) REFERENCES Pets(id_pet) ON DELETE CASCADE
);

-- Tabela de Controle de Peso
CREATE TABLE IF NOT EXISTS Peso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    data_registro DATE NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (pet_id) REFERENCES Pets(id_pet) ON DELETE CASCADE
);

-- Tabela de Faturas
CREATE TABLE IF NOT EXISTS Faturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    pet_id INT NOT NULL,
    data_fatura DATE NOT NULL,
    servico VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL,
    observacoes TEXT,
    clinica VARCHAR(100) NOT NULL,
    profissional VARCHAR(100) NOT NULL,
    status_f ENUM('pago', 'pendente', 'cancelado') DEFAULT 'pendente',
    FOREIGN KEY (tutor_id) REFERENCES Tutor(id_tutor) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES Pets(id_pet) ON DELETE CASCADE
);

-- Tabela de Agendamentos
CREATE TABLE IF NOT EXISTS Agendamentos (
    id_agendamento INT AUTO_INCREMENT PRIMARY KEY,
    id_pet INT,
    id_tutor INT,
    id_servico INT,
    data_hora DATETIME NOT NULL,
    status_a ENUM('agendado', 'concluido', 'cancelado') DEFAULT 'agendado',
    observacoes TEXT,
    FOREIGN KEY (id_pet) REFERENCES Pets(id_pet) ON DELETE CASCADE,
    FOREIGN KEY (id_tutor) REFERENCES Tutor(id_tutor) ON DELETE CASCADE,
    FOREIGN KEY (id_servico) REFERENCES Servicos(id_servico) ON DELETE CASCADE
);

SELECT * FROM Agendamentos;
-- Tabela de prontuarios
CREATE TABLE IF NOT EXISTS Prontuarios (
    id_prontuario INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL,
    pet_id INT NOT NULL,
    data_P DATETIME NOT NULL COMMENT 'Data e hora do registro no prontuário',
    descricao TEXT NOT NULL COMMENT 'Descrição detalhada do registro no prontuário',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES Pets(id_pet) ON DELETE CASCADE
);

-- Tabela de Notificacoes
CREATE TABLE IF NOT EXISTS Notificacoes (
    id_notificacao INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id_usuario INT NOT NULL COMMENT 'ID do usuário que ENVIOU a notificação',
    id_usuario INT NOT NULL COMMENT 'ID do usuário que RECEBERÁ a notificação (destinatário)',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = não lida, 1 = lida (pelo destinatário)',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_visualizacao DATETIME DEFAULT NULL,
    FOREIGN KEY (remetente_id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS estoque (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        categoria ENUM('Medicamento', 'Insumo Médico', 'Produto de Higiene', 'Alimento', 'Outro') NOT NULL,
        descricao TEXT,
        quantidade_atual INT NOT NULL DEFAULT 0,
        quantidade_minima INT NOT NULL DEFAULT 5,
        quantidade_maxima INT NOT NULL DEFAULT 100,
        unidade_medida VARCHAR(20) NOT NULL,
        preco_unitario DECIMAL(10,2) NOT NULL,
        data_validade DATE,
        fornecedor VARCHAR(100),
        localizacao VARCHAR(100),
        data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

-- Inserir dados de exemplo para usuário administrador
INSERT INTO Usuarios (nome, email, senha, cpf, data_nasc) VALUES
('Administrador', 'admin@petplus.com', '$2y$10$8MJO1GyYGrCZgD.OUgKqWOoC9Vc0HOTSsM7Vx5OzXIchcVrXIQS4m', '123.456.789-00', '1990-01-01');

-- Inserir dados de exemplo para tutores
INSERT INTO Tutor (nome, email, cpf, telefone, endereco) VALUES
('João Silva', 'joao@email.com', '66839867030','(11) 98765-4321', 'Rua das Flores, 123'),
('Maria Oliveira', 'maria@email.com', '75390233093','(11) 91234-5678', 'Av. Principal, 456'),
('Carlos Santos', 'carlos@email.com', '71257794094','(11) 99876-5432', 'Rua do Comércio, 789');

-- Inserir dados de exemplo para pets
INSERT INTO Pets (id_tutor, nome, especie, raca, data_nascimento, sexo, cor, peso) VALUES
(1, 'Rex', 'Cachorro', 'Labrador', '2019-05-10', 'M', 'Caramelo', 25.5),
(1, 'Luna', 'Gato', 'Siamês', '2020-03-15', 'F', 'Branco', 4.2),
(2, 'Mel', 'Cachorro', 'Golden Retriever', '2018-07-22', 'F', 'Dourado', 28.0),
(3, 'Thor', 'Cachorro', 'Bulldog', '2020-11-05', 'M', 'Branco e Marrom', 15.8);

-- Inserir alguns dados de exemplo na tabela de consultas
INSERT INTO consultas (pet_id, data_consulta, motivo, diagnostico, tratamento, observacoes, veterinario_id, status_co)
VALUES
(1, '2023-06-15 10:00:00', 'Checkup anual', 'Pet saudável', 'Nenhum tratamento necessário', 'Continuar com a dieta atual', 1, 'concluida'),
(2, '2023-06-16 14:30:00', 'Vacina anual', NULL, NULL, 'Trazer carteira de vacinação', 2, 'agendada'),
(3, '2023-06-17 09:15:00', 'Vômito e diarreia', 'Gastroenterite', 'Medicação oral por 5 dias', 'Dieta leve por 3 dias', 1, 'em_andamento'),
(1, '2023-07-20 11:00:00', 'Consulta de rotina', NULL, NULL, NULL, 2, 'agendada');

-- Inserir dados de exemplo para serviços
INSERT INTO Servicos (nome, descricao, preco, duracao, categoria) VALUES
('Consulta Veterinária', 'Consulta de rotina com veterinário', 150.00, 30, 'M'),
('Banho e Tosa', 'Serviço completo de higiene', 80.00, 60, 'A'),
('Vacinação', 'Aplicação de vacinas', 120.00, 15, 'S'),
('Exame de Sangue', 'Hemograma completo', 200.00, 20, 'L');

-- Inserir dados de exemplo para consultas
INSERT INTO Consultas_c (pet_id, data_c, descricao, status_c) VALUES
(1, '2023-06-15 10:00:00', 'Consulta de rotina', 'concluída'),
(2, '2023-06-20 14:30:00', 'Verificação de alergia', 'concluída'),
(3, '2023-07-05 09:00:00', 'Consulta pós-cirúrgica', 'agendada'),
(4, '2023-07-10 16:00:00', 'Avaliação de comportamento', 'agendada');

-- Inserir dados de exemplo para diagnósticos
INSERT INTO Diagnosticos (consulta_id, sintomas, exames, prescricao) VALUES
(1, 'Tosse e espirros', 'Raio-X torácico', 'Antibiótico por 7 dias, 2x ao dia'),
(2, 'Coceira e vermelhidão na pele', 'Teste alérgico', 'Pomada antialérgica e banhos medicamentosos');

-- Inserir dados de exemplo para vacinas
INSERT INTO Vacinas (pet_id, nome, data_aplicacao, data_proxima, lote) VALUES
(1, 'V10', '2023-05-10', '2024-05-10', 'ABC123'),
(2, 'Raiva', '2023-04-15', '2024-04-15', 'DEF456'),
(3, 'V8', '2023-03-22', '2024-03-22', 'GHI789');

-- Inserir dados de exemplo para controle de peso
INSERT INTO Peso (pet_id, peso, data_registro) VALUES
(1, 25.5, '2023-05-10'),
(1, 26.0, '2023-06-10'),
(2, 4.2, '2023-05-15'),
(3, 28.0, '2023-04-22');

-- Inserir dados de exemplo para faturas
INSERT INTO Faturas (tutor_id, pet_id, data_fatura, servico, valor, forma_pagamento, clinica, profissional, status_f) VALUES
(1, 1, '2023-06-15', 'Consulta Veterinária', 150.00, 'Cartão de Crédito', 'PetPlus Clínica', 'Dr. Roberto Alves', 'pago'),
(2, 3, '2023-06-20', 'Banho e Tosa', 80.00, 'Dinheiro', 'PetPlus Clínica', 'Ana Souza', 'pago'),
(3, 4, '2023-07-05', 'Vacinação', 120.00, 'Pix', 'PetPlus Clínica', 'Dra. Carla Mendes', 'pendente');

-- Inserir dados de exemplo para agendamentos
INSERT INTO Agendamentos (id_pet, id_servico, data_hora, status_a) VALUES
(1, 1, '2023-07-15 10:00:00', 'agendado'),
(2, 2, '2023-07-16 14:30:00', 'agendado'),
(3, 3, '2023-07-17 09:00:00', 'agendado');
