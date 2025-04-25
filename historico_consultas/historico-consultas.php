<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Histórico de Consultas - PetPlus</title>
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
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    h1 {
      text-align: center;
      color: #333;
    }

    h2 {
      color: #555;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      font-weight: 600;
      margin-bottom: 8px;
    }

    input, textarea, select {
      width: 100%;
      padding: 10px;
      border-radius: 4px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    textarea {
      resize: vertical;
    }

    button {
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    button:hover {
      background-color: #45a049;
    }

    .tabela {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }

    .tabela th, .tabela td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    .tabela th {
      background-color: #f2f2f2;
      color: #333;
    }

    .btn-secondary {
      background-color: #f0ad4e;
      color: white;
      padding: 8px 16px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }

    .btn-secondary:hover {
      background-color: #ec971f;
    }

    footer {
      text-align: center;
      padding: 20px;
      background-color: #f6f9fc;
      color: #333;
      margin-top: 20px;
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

      input, textarea, select {
        font-size: 14px;
        padding: 8px;
      }

      .tabela th, .tabela td {
        padding: 8px;
      }

      button {
        width: 100%;
        font-size: 14px;
        padding: 12px 20px;
      }

      footer {
        padding: 15px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Histórico de Consultas do Pet: <span id="nomePet"></span></h1>
    <form id="formConsulta">
      <div class="form-group">
        <label for="dataConsulta">Data da Consulta</label>
        <input type="date" id="dataConsulta" required />
      </div>
      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" rows="3" required></textarea>
      </div>
      <div class="form-group">
        <label for="status">Status</label>
        <select id="status" required>
          <option value="agendada">Agendada</option>
          <option value="em andamento">Em Andamento</option>
          <option value="concluída">Concluída</option>
        </select>
      </div>
      <button type="submit" class="btn-primary">Cadastrar Consulta</button>
    </form>
    <h2>Consultas Registradas</h2>
    <table class="tabela">
      <thead>
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Descrição</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="listaConsultas"></tbody>
    </table>
  </div>
  <footer>
    <!-- Footer content here -->
  </footer>
  <script src="js/ConsultaController.js"></script>
</body>
</html>
