<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Diagnóstico de Consulta</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f6f9fc;
      margin: 0;
      padding: 20px;
    }

    /* Container Principal */
    .container {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    /* Estilo dos Formulários */
    .grupo-formulario {
      margin-bottom: 20px;
    }

    .grupo-formulario label {
      display: block;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .grupo-formulario input,
    .grupo-formulario select,
    .grupo-formulario textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-sizing: border-box;
    }

    .botoes-formulario {
      text-align: center;
      margin-top: 20px;
    }

    button {
      padding: 10px 20px;
      margin: 5px;
      border: none;
      border-radius: 6px;
      background-color: #4caf50;
      color: white;
      font-weight: bold;
      cursor: pointer;
      width: 100%;
    }

    button:hover {
      background-color: #45a049;
    }

    /* Estilo da Tabela */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 40px;
    }

    table th,
    table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }

    table th {
      background-color: #f2f2f2;
    }

    /* Responsividade */
    @media (max-width: 768px) {
      .container {
        padding: 15px;
      }

      .grupo-formulario input,
      .grupo-formulario select,
      .grupo-formulario textarea {
        padding: 8px;
      }

      table th, table td {
        padding: 8px;
      }

      button {
        width: 100%;
      }
    }
  </style>
</head>
<body>

  <div class="container">
    <h1>Diagnóstico de Consulta</h1>

    <!-- Formulário de Diagnóstico de Consulta -->
    <form id="formularioDiagnostico">
      <div class="grupo-formulario">
        <label for="nomeTutor">Nome do Tutor</label>
        <input type="text" id="nomeTutor" required />
      </div>

      <div class="grupo-formulario">
        <label for="nomePet">Nome do Pet</label>
        <input type="text" id="nomePet" required />
      </div>

      <div class="grupo-formulario">
        <label for="dataConsulta">Data da Consulta</label>
        <input type="date" id="dataConsulta" required />
      </div>

      <div class="grupo-formulario">
        <label for="diagnostico">Diagnóstico</label>
        <textarea id="diagnostico" rows="5" required></textarea>
      </div>

      <div class="grupo-formulario">
        <label for="tratamento">Tratamento</label>
        <textarea id="tratamento" rows="3" required></textarea>
      </div>

      <div class="grupo-formulario">
        <label for="medicamentos">Medicamentos Prescritos</label>
        <input type="text" id="medicamentos" required />
      </div>

      <div class="grupo-formulario">
        <label for="observacoes">Observações</label>
        <textarea id="observacoes" rows="3"></textarea>
      </div>

      <div class="botoes-formulario">
        <button type="submit">Cadastrar Diagnóstico</button>
      </div>
    </form>

    <!-- Tabela de Consultas -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tutor</th>
          <th>Pet</th>
          <th>Data da Consulta</th>
          <th>Diagnóstico</th>
          <th>Tratamento</th>
          <th>Medicamentos</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="listaConsultas"></tbody>
    </table>
  </div>

  <script src="js/ControladorDiagnostico.js"></script>
</body>
</html>

