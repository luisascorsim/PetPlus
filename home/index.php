<?php
session_start();

// destrói a sessão e redireciona
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /petplus/PetPlus-main/Tela_de_site/tela_site.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>PetPlus - Tela Home</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Quicksand', sans-serif;
      background-color: #d3d3d3;
    }

    .topo {
      background-color: #2196f3;
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 30px;
    }

    .logo {
      display: flex;
      align-items: center;
    }

    .logo img {
      height: 40px;
      margin-right: 10px;
    }

    .logo span {
      font-size: 20px;
      font-weight: 700;
    }

    .usuario {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .usuario img {
      height: 24px;
      cursor: pointer;
    }

    .conteudo {
      background-color: white;
      width: 700px;
      margin: 50px auto;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
    }

    .conteudo h1 {
      margin-bottom: 40px;
      color: #0b3556;
      font-size: 28px;
      font-weight: 600;
    }

    .botoes {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px 40px;
      justify-items: center;
    }

    .botao {
      display: flex;
      align-items: center;
      background-color: #0b3556;
      color: white;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 6px;
      font-size: 18px;
      font-weight: 500;
      transition: background-color 0.3s;
      width: 220px;
      justify-content: left;
    }

    .botao:hover {
      background-color: #0d4371;
    }

    .botao img {
      height: 30px;
      margin-right: 10px;
    }
  </style>
</head>
<body>

  <div class="topo">
    <div class="logo">
      <img src="imagens/logo.png" alt="PetPlus Logo">
      <span>PetPlus</span>
    </div>
    <div class="usuario">
      <img src="icones/usuario.png" alt="Usuário">
      <span>Bem-vindo!</span>
      <img src="icones/config.png" alt="Configurações">
      <a href="?logout=true">
        <img src="icones/sair.png" alt="Sair">
      </a>
    </div>
  </div>

  <div class="conteudo">
    <h1>PetPlus</h1>
    <div class="botoes">
      <a href="agenda.php" class="botao">
        <img src="icones/agenda.png" alt="Agenda"> Agenda
      </a>
      <a href="..\pagina_inicial_sistema\pagina_inicial.html" class="botao">
        <img src="icones/clientes.png" alt="Clientes"> Clientes
      </a>
      <a href="servicos.php" class="botao">
        <img src="icones/servicos.png" alt="Serviços"> Serviços
      </a>
      <a href="..\cadastrar_pet\cadastrar_pets.html" class="botao">
        <img src="icones/cadastro.png" alt="Cadastro"> Cadastro
      </a>
      <a href="..\fatura_detalhada\fatura-detalhada.php" class="botao">
        <img src="icones/relatorios.png" alt="Faturas"> Faturas
      </a>
      <a href="..\diagnostico_consulta\diagnostico-consulta.php" class="botao">
        <img src="icones/prontuarios.png" alt="Prontuários"> Prontuários
      </a>
      <a href="..\status_consulta\status-consulta.php" class="botao">
        <img src="icones/consultas.png" alt="Consultas"> Consultas
      </a>
      <a href="..\vacinas_e_controle_do_peso\vacinas-peso.html" class="botao">
        <img src="icones/vacinas.png" alt="Vacinas"> Vacinas
      </a>
    </div>
  </div>

</body>
</html>