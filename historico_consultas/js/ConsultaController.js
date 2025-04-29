class ControladorConsulta {
  constructor() {
    this.apiUrl = "api/consultas/";
    this.carregarConsultas();
    document.getElementById("formConsulta").addEventListener("submit", (e) => {
      e.preventDefault();
      this.salvarConsulta();
    });
  }

  carregarConsultas() {
    fetch(this.apiUrl + "listarConsultas.php")
      .then(res => res.json())
      .then(dados => this.renderizarTabela(dados))
      .catch(err => console.error("Erro ao carregar consultas:", err));
  }

  salvarConsulta() {
    const data = document.getElementById("dataConsulta").value;
    const descricao = document.getElementById("descricao").value;
    const status = document.getElementById("status").value;
    const novaConsulta = { data: data, descricao: descricao, status: status };

    fetch(this.apiUrl + "cadastrarConsulta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(novaConsulta)
    })
      .then(res => res.json())
      .then(msg => {
        alert(msg.mensagem);
        this.carregarConsultas();
        document.getElementById("formConsulta").reset();
      })
      .catch(err => console.error("Erro ao salvar consulta:", err));
  }

  excluirConsulta(id) {
    if (!confirm("Tem certeza que deseja excluir esta consulta?")) return;
    fetch(this.apiUrl + "excluirConsulta.php?id=" + id)
      .then(res => res.json())
      .then(msg => {
        alert(msg.mensagem);
        this.carregarConsultas();
      })
      .catch(err => console.error("Erro ao excluir consulta:", err));
  }

  renderizarTabela(consultas) {
    const corpoTabela = document.getElementById("listaConsultas");
    corpoTabela.innerHTML = "";
    consultas.forEach((consulta) => {
      const linha = document.createElement("tr");
      linha.innerHTML = `
        <td>${consulta.id}</td>
        <td>${consulta.data}</td>
        <td>${consulta.descricao}</td>
        <td>${consulta.status}</td>
        <td>
          <button class="btn-secondary" onclick="controlador.excluirConsulta(${consulta.id})">Excluir</button>
        </td>
      `;
      corpoTabela.appendChild(linha);
    });
  }
}
const controlador = new ControladorConsulta();
