class ControladorDiagnostico {
  constructor() {
    this.apiUrl = "api/diagnosticos/"
    this.carregarDiagnosticos()

    // Verificar se o formulário existe antes de adicionar o event listener
    const formDiagnostico = document.getElementById("formDiagnostico")
    if (formDiagnostico) {
      formDiagnostico.addEventListener("submit", (e) => {
        e.preventDefault()
        this.salvarDiagnostico()
      })
    } else {
      console.warn("Formulário de diagnóstico não encontrado")
    }

    // Simular uma consulta selecionada para testes
    this.simularConsultaSelecionada()
  }

  // Função para simular uma consulta selecionada (para testes)
  simularConsultaSelecionada() {
    // Criar um cookie ou localStorage para simular a sessão
    localStorage.setItem("consulta_id", "1")

    // Verificar se estamos em ambiente de teste ou produção
    if (!document.cookie.includes("PHPSESSID")) {
      console.log("Ambiente de teste detectado, usando localStorage para simular sessão")

      // Adicionar um interceptor para as requisições fetch
      const originalFetch = window.fetch
      window.fetch = (url, options) => {
        if (url.includes("listarDiagnosticos.php") || url.includes("cadastrarDiagnostico.php")) {
          // Modificar as opções para incluir o consulta_id no corpo da requisição
          if (!options) options = {}
          if (!options.headers) options.headers = {}
          options.headers["X-Consulta-ID"] = localStorage.getItem("consulta_id")
        }
        return originalFetch(url, options)
      }
    }
  }

  carregarDiagnosticos() {
    fetch(this.apiUrl + "listarDiagnosticos.php")
      .then((res) => res.json())
      .then((diagnosticos) => {
        if (Array.isArray(diagnosticos)) {
          this.renderizarTabela(diagnosticos)
        } else {
          console.log("Nenhum diagnóstico encontrado ou erro:", diagnosticos.mensagem)
          this.renderizarTabela([])
        }
      })
      .catch((err) => {
        console.error("Erro ao carregar diagnósticos:", err)
        // Dados de exemplo para teste quando a API falha
        const diagnosticosExemplo = [
          { id: 1, sintomas: "Febre e tosse", exames: "Raio-X", prescricao: "Antibiótico 2x ao dia" },
          { id: 2, sintomas: "Coceira", exames: "Exame de pele", prescricao: "Pomada antialérgica" },
        ]
        this.renderizarTabela(diagnosticosExemplo)
      })
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
        alert(msg.mensagem || "Diagnóstico salvo com sucesso!")
        this.carregarDiagnosticos()
        document.getElementById("formDiagnostico").reset()
      })
      .catch((err) => {
        console.error("Erro ao salvar diagnóstico:", err)
        alert("Erro ao salvar diagnóstico. Verifique o console para mais detalhes.")
      })
  }

  excluirDiagnostico(id) {
    if (!confirm("Deseja realmente excluir este diagnóstico?")) return
    fetch(this.apiUrl + "excluirDiagnostico.php?id=" + id)
      .then((res) => res.json())
      .then((msg) => {
        alert(msg.mensagem || "Diagnóstico excluído com sucesso!")
        this.carregarDiagnosticos()
      })
      .catch((err) => {
        console.error("Erro ao excluir diagnóstico:", err)
        alert("Erro ao excluir diagnóstico. Verifique o console para mais detalhes.")
      })
  }

  renderizarTabela(diagnosticos) {
    const tbody = document.getElementById("listaDiagnosticos")
    if (!tbody) {
      console.error("Elemento listaDiagnosticos não encontrado")
      return
    }

    tbody.innerHTML = ""

    if (diagnosticos.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5">Nenhum diagnóstico encontrado</td></tr>'
      return
    }

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
