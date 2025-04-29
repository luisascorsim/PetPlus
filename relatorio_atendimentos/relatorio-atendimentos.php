<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Relatório de Atendimentos - PetPlus</title>

  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f7f7f7;
    }

    .container {
      max-width: 900px;
      margin: 40px auto;
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
      text-align: center;
      margin-bottom: 30px;
      color: #2b3e50;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    input, textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    textarea {
      resize: vertical;
    }

    button {
      background-color: #38b6ff;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s;
      width: 100%;
    }

    button:hover {
      background-color: #2a94cc;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }

    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #38b6ff;
      color: white;
    }

    .actions button {
      background-color: #ff6b6b;
      margin-right: 8px;
      color: white;
    }

    .actions button.edit {
      background-color: #2ecc71;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .container {
        padding: 20px;
      }

      h1 {
        font-size: 1.5em;
      }

      .form-group {
        margin-bottom: 15px;
      }

      th, td {
        font-size: 14px;
        padding: 8px;
      }

      button {
        font-size: 14px;
        padding: 10px 20px;
      }

      .actions button {
        font-size: 14px;
        padding: 8px 12px;
      }
    }

    @media (max-width: 600px) {
      .form-group {
        margin-bottom: 12px;
      }

      h1 {
        font-size: 1.3em;
      }

      th, td {
        font-size: 12px;
        padding: 6px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Relatório de Atendimentos</h1>

    <form id="formAtendimento">
      <div class="form-group">
        <label for="nomeTutor">Nome do Tutor</label>
        <input type="text" id="nomeTutor" required />
      </div>

      <div class="form-group">
        <label for="nomePet">Nome do Pet</label>
        <input type="text" id="nomePet" required />
      </div>

      <div class="form-group">
        <label for="data">Data do Atendimento</label>
        <input type="date" id="data" required />
      </div>

      <div class="form-group">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" rows="4" required></textarea>
      </div>

      <input type="hidden" id="idAtendimento" />
      <button type="submit">Salvar Atendimento</button>
    </form>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tutor</th>
          <th>Pet</th>
          <th>Data</th>
          <th>Descrição</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="listaAtendimentos"></tbody>
    </table>
  </div>

  <script src="js/ControleDeAtendimentos.js"></script>
</body>
</html>
