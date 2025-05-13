class ControladorDiagnostico {
  constructor() {
    this.apiUrl = "api/diagnosticos/"
    this.carregarDiagnosticos()
    document.getElementById("formDiagnostico").addEventListener("submit", (e) => {
      e.preventDefault()
      this.salvarDiagnostico()
    })
  }

  carregarDiagnosticos() {
    fetch(this.apiUrl + "listarDiagnosticos.php")
      .then((res) => res.json())
      .then((diagnosticos) => this.renderizarTabela(diagnosticos))
      .catch((err) => console.error("Erro ao carregar diagnósticos:", err))
  }

  salvarDiagnostico() {
    const sintomas = document.getElementById("sintomas").value
    const exames = document.getElementById("exames").value
    const prescricao = document.getElementById("prescricao").value
    const diagnostico = { sintomas: sintomas, exames: exames, prescricao: prescricao }

    fetch(this.apiUrl + "cadastrarDiagnostico.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(diagnostico),
    })
      .then((res) => res.json())
      .then((msg) => {
        alert(msg.mensagem)
        this.carregarDiagnosticos()
        document.getElementById("formDiagnostico").reset()
      })
      .catch((err) => console.error("Erro ao salvar diagnóstico:", err))
  }

  excluirDiagnostico(id) {
    if (!confirm("Deseja realmente excluir este diagnóstico?")) return
    fetch(this.apiUrl + "excluirDiagnostico.php?id=" + id)
      .then((res) => res.json())
      .then((msg) => {
        alert(msg.mensagem)
        this.carregarDiagnosticos()
      })
      .catch((err) => console.error("Erro ao excluir diagnóstico:", err))
  }

  renderizarTabela(diagnosticos) {
    const tbody = document.getElementById("listaDiagnosticos")
    tbody.innerHTML = ""
    diagnosticos.forEach((d) => {
      const tr = document.createElement("tr")
      tr.innerHTML = `
        <td>${d.id}</td>
        <td>${d.sintomas}</td>
        <td>${d.exames}</td>
        <td>${d.prescricao}</td>
        <td>
          <button class="btn-secondary" onclick="controlador.excluirDiagnostico(${d.id})">Excluir</button>
        </td>
      `
      tbody.appendChild(tr)
    })
  }
}
const controlador = new ControladorDiagnostico()
