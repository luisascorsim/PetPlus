<?php
include 'conecta_db.php';
if (isset($_POST['nome']) && isset($_POST['especie']) && isset($_POST['raca']) && isset($_POST['idade']) && isset($_POST['sexo']) && isset($_POST['peso_atual']) && isset($_POST['descricao'])) {
    $oMysql = conecta_db();
    
    $nome = $_POST['nome'];
    $especie = $_POST['especie'];
    $raca = $_POST['raca'];
    $idade = $_POST['idade'];
    $sexo = $_POST['sexo'];
    $peso_atual = $_POST['peso_atual'];
    $descricao = $_POST['descricao'];

    $query = "INSERT INTO Pet (nome, especie, raca, idade, sexo, peso_atual, descricao) 
          VALUES ('$nome', '$especie', '$raca', '$idade', '$sexo', '$peso_atual', '$descricao')";
    if ($oMysql->query($query)) {
        header('Location: semlocacaoainda.php');
        exit();
    } else {
        echo "Erro: " . $oMysql->error;
    }
}
?>
<!DOCTYPE html>
<html lang="pet">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cadastrar pet</title>
    <link rel="stylesheet" href="../css/cadastro_pet.css">
    <link rel="stylesheet" href="../css/nav_cliente.css">
    <style>

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Segoe UI', sans-serif;
}

body {
  display: flex;
  min-height: 100vh;
  background-color: #f5f7fa;
}

/* Sidebar*/
.sidebar {
  width: 180px;
  background-color: white;
  border-right: 2px solid #003b66;
  padding: 20px 10px;
  color: #003b66;
  font-weight: bold;
  position: fixed;
  top: 60px;
  bottom: 0;
  left: 0;
}

.sidebar img {
  width: 100px;
  margin: 0 auto 20px;
  display: block;
}

.sidebar button {
  width: 100%;
  background-color: #003b66;
  color: white;
  border: none;
  padding: 10px 0;
  margin: 8px 0;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.2s;
}

.sidebar button:hover {
  background-color: #00294d;
}

/* Header*/
.header {
  position: fixed;
  top: 0;
  left: 0;
  height: 60px;
  width: 100%;
  background-color: #2196f3;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  color: white;
  z-index: 1000;
}

.header .logo {
  height: 40px;
}

.header .user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.header .user-info img {
  width: 24px;
  cursor: pointer;
}

/*conteudo*/
.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.top-bar h2 {
  font-size: 20px;
  color: #003b66;
}
/*formulario */
.container {
  margin-left: 100px;
  margin-top: 100px;
  padding: 30px;
  width: calc(100% - 180px);
  height: calc(100vh - 60px);
  display: flex;
  flex-direction: column; 
  align-items: center;
  justify-content: center;
}

.formulario-pet {
  background-color: #ffffff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
}

.formulario-pet label {
  display: block;
  margin-bottom: 6px;
  font-weight: bold;
  color: #003b66;
}

.formulario-pet input {
  width: 100%;
  padding: 10px;
  margin-bottom: 20px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-size: 14px;
}

.btn-novo {
  background-color: #003b66;
  color: white;
  padding: 12px;
  border: none;
  font-size: 16px;
  border-radius: 6px;
  cursor: pointer;
  width: 100%;
}

.btn-novo:hover {
  background-color: #002b4d;
}

</style>
</head>
<body>

  <div class="sidebar">
    <button>Agenda</button>
    <button>Clientes</button>
    <button>Serviços</button>
    <button>Cadastro</button>
    <button>Relatórios</button>
    <button>Prontuários</button>
    <button>Consultas</button>
    <button>Vacinas</button>
  </div>

  <div class="header">
    <img src="\LoginECadastro\logo.png" Logo PetPlus class="logo" />
    <span>Bem vindo, Fulano!</span>
    <img src="icon-user.png" alt="Usuário" />
    <img src="icon-settings.png" alt="Configurações" />
    <img src="icon-logout.png" alt="Sair" />
  </div>

  <div class="container">
    <div class="top-bar">
      <h2>Cadastro de Pet</h2>
    </div>

    <form action="cadastrar_pet.php" method="POST" class="formulario-pet">
      <label for="nome">Nome do Pet:</label>
      <input type="text" id="nome_pet" name="nome_pet" required />

      <label for="especie">Especie:</label>
      <input type="text" id="especie" name="especie" required />

      <label for="raca">Raca:</label>
      <input type="text" id="raca" name="raca" required />

      <label for="idade">Idade:</label>
      <input type="number" id="idade" name="idade" required />

      <label for="sexo">Sexo:</label>
      <input type="int" id="sexo" name="sexo" required />

      <label for="peso_atual">Peso Atual:</label>
      <input type="float" id="peso_atual" name="peso_atual" required />

      <label for="descricao">Descriacao:</label>
      <input type="text" id="descricao" name="descricao" required />

      <button type="submit" class="btn-novo">Cadastrar Pet</button>
    </form>
  </div>

</body>
</html>

