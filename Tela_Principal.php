<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>PetPlus - Tela Inicial</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f2f2f2;
    }

    header {
      background-color: #2196f3;
      color: white;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .logo {
      display: flex;
      align-items: center;
    }

    .logo img {
      height: 40px;
      margin-right: 10px;
    }

    .user-options {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-options span {
      font-weight: bold;
    }

    .container {
      text-align: center;
      padding: 30px;
    }

    h1 {
      margin-bottom: 40px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(2, 180px);
      gap: 20px;
      justify-content: center;
    }

    .btn {
      background-color: #0d3b66;
      color: white;
      padding: 15px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn:hover {
      background-color: #155fa0;
    }

    .btn img {
      width: 20px;
      height: 20px;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">
    <img src="\LoginECadastro\logo.png" alt="PetPlus Logo">
  </div>
  <div class="user-options">
    <span>Bem vindo, Fulano!</span>
    <img src="gear-icon.png" alt="Configuração" width="20">
    <img src="logout-icon.png" alt="Sair" width="20">
  </div>
</header>

<div class="container">
  <h1>Nome da Clínica</h1>
  <div class="grid">
    <button class="btn"><img src="agenda-icon.png" alt="">Agenda</button>
    <button class="btn"><img src="clientes-icon.png" alt="">Clientes</button>
    <button class="btn"><img src="servicos-icon.png" alt="">Serviços</button>
    <a class="btn" href="index.php?page=4">
       <img src="cadastro-icon.png" alt="">Cadastro
    </a>
    <button class="btn"><img src="relatorios-icon.png" alt="">Relatórios</button>
    <button class="btn"><img src="prontuarios-icon.png" alt="">Prontuários</button>
    <button class="btn"><img src="consultas-icon.png" alt="">Consultas</button>
    <button class="btn"><img src="vacinas-icon.png" alt="">Vacinas</button>
  </div>
</div>

</body>
</html>
