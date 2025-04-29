<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Status da Consulta - PetPlus</title>
  <link rel="stylesheet" href="css/estilo.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f6f9fc;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
    }

    h1 {
      text-align: center;
      color: #1e3a8a;
      margin-bottom: 30px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 500;
      color: #333;
      margin-bottom: 8px;
    }

    input[type="text"],
    select,
    button {
      width: 100%;
      padding: 12px 20px;
      border-radius: 4px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    input[type="text"] {
      background-color: #f6f9fc;
    }

    select {
      background-color: #f6f9fc;
    }

    button {
      background-color: #1e3a8a;
      color: white;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:hover {
      background-color: #374cb1;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .container {
        padding: 15px;
      }

      h1 {
        font-size: 1.5em;
      }

      .form-group {
        margin-bottom: 15px;
      }

      input[type="text"],
      select,
      button {
        padding: 10px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Status da Consulta</h1>
    <form id="formStatus">
      <input type="hidden" id="consultaId" value="1" />
      
      <!-- Campo para o nome do pet -->
      <div class="form-group">
        <label for="nomePet">Nome do Pet</label>
        <input type="text" id="nomePet" required placeholder="Digite o nome do pet" />
      </div>

      <div class="form-group">
        <label for="status">Status Atual</label>
        <select id="status" required>
          <option value="agendada">Agendada</option>
          <option value="em andamento">Em Andamento</option>
          <option value="concluída">Concluída</option>
        </select>
      </div>

      <button type="submit" class="btn-primary">Atualizar Status</button>
    </form>
  </div>
  <script src="js/StatusConsultaController.js"></script>
</body>
</html>
