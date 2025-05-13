// Variáveis globais
let consultaIdParaExcluir = null
const paginaAtual = 1
const itensPorPagina = 10

// Função para carregar as consultas
function carregarConsultas(pagina = 1) {
  // Obter parâmetros de filtro do URL
  const urlParams = new URLSearchParams(window.location.search)
  const filtroData = urlParams.get("data") || ""
  const filtroStatus = urlParams.get("status") || ""
  const filtroVeterinario = urlParams.get("veterinario") || ""
  const filtroCliente = urlParams.get("cliente") || ""
  const filtroPet = urlParams.get("pet") || ""

  // Construir URL da API com filtros
  let apiUrl = `../api/consultas/listar_consultas.php?pagina=${pagina}&limite=${itensPorPagina}`

  if (filtroData) apiUrl += `&data=${filtroData}`
  if (filtroStatus) apiUrl += `&status=${filtroStatus}`
  if (filtroVeterinario) apiUrl += `&veterinario_id=${filtroVeterinario}`
  if (filtroCliente) apiUrl += `&cliente_id=${filtroCliente}`
  if (filtroPet) apiUrl += `&pet_id=${filtroPet}`

  // Fazer requisição AJAX
  fetch(apiUrl)
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erro ao carregar consultas")
      }
      return response.json()
    })
    .then((data) => {
      exibirConsultas(data.consultas)
      criarPaginacao(data.total, pagina)
    })
    .catch((error) => {
      console.error("Erro:", error)
      alert("Erro ao carregar consultas. Por favor, tente novamente.")
    })
}

// Função para exibir as consultas na tabela
function exibirConsultas(consultas) {
  const tbody = document.getElementById("consultasTableBody")
  tbody.innerHTML = ""

  if (consultas.length === 0) {
    const tr = document.createElement("tr")
    tr.innerHTML = '<td colspan="6" style="text-align: center;">Nenhuma consulta encontrada</td>'
    tbody.appendChild(tr)
    return
  }

  consultas.forEach((consulta) => {
    const tr = document.createElement("tr")

    // Formatar data e hora
    const dataHora = new Date(consulta.data_hora)
    const dataFormatada = dataHora.toLocaleDateString("pt-BR")
    const horaFormatada = dataHora.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" })

    // Criar classe de status
    const statusClass = `status status-${consulta.status}`

    // Traduzir status
    let statusTexto = ""
    switch (consulta.status) {
      case "agendada":
        statusTexto = "Agendada"
        break
      case "confirmada":
        statusTexto = "Confirmada"
        break
      case "em_andamento":
        statusTexto = "Em Andamento"
        break
      case "concluida":
        statusTexto = "Concluída"
        break
      case "cancelada":
        statusTexto = "Cancelada"
        break
      default:
        statusTexto = consulta.status
    }

    tr.innerHTML = `
            <td>${dataFormatada} ${horaFormatada}</td>
            <td>${consulta.cliente_nome}</td>
            <td>${consulta.pet_nome}</td>
            <td>${consulta.veterinario_nome}</td>
            <td><span class="${statusClass}">${statusTexto}</span></td>
            <td class="acoes">
                <a href="visualizar_consulta.php?id=${consulta.id}" title="Visualizar">
                    <i class="fas fa-eye"></i>
                </a>
                <a href="agendar_consulta.php?id=${consulta.id}" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="#" class="btn-excluir" data-id="${consulta.id}" title="Excluir">
                    <i class="fas fa-trash"></i>
                </a>
            </td>
        `

    tbody.appendChild(tr)
  })

  // Adicionar event listeners para os botões de excluir
  document.querySelectorAll(".btn-excluir").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault()
      const id = this.getAttribute("data-id")
      abrirModalExclusao(id)
    })
  })
}

// Função para criar a paginação
function criarPaginacao(totalItens, paginaAtual) {
  const totalPaginas = Math.ceil(totalItens / itensPorPagina)
  const paginacaoElement = document.getElementById("paginacao")
  paginacaoElement.innerHTML = ""

  // Botão anterior
  if (totalPaginas > 1) {
    const btnAnterior = document.createElement("button")
    btnAnterior.innerHTML = "&laquo; Anterior"
    btnAnterior.disabled = paginaAtual === 1
    btnAnterior.addEventListener("click", () => {
      if (paginaAtual > 1) {
        carregarConsultas(paginaAtual - 1)
      }
    })
    paginacaoElement.appendChild(btnAnterior)

    // Números das páginas
    for (let i = 1; i <= totalPaginas; i++) {
      const btnPagina = document.createElement("button")
      btnPagina.textContent = i
      btnPagina.classList.toggle("active", i === paginaAtual)
      btnPagina.addEventListener("click", () => carregarConsultas(i))
      paginacaoElement.appendChild(btnPagina)
    }

    // Botão próximo
    const btnProximo = document.createElement("button")
    btnProximo.innerHTML = "Próximo &raquo;"
    btnProximo.disabled = paginaAtual === totalPaginas
    btnProximo.addEventListener("click", () => {
      if (paginaAtual < totalPaginas) {
        carregarConsultas(paginaAtual + 1)
      }
    })
    paginacaoElement.appendChild(btnProximo)
  }
}

// Função para abrir o modal de exclusão
function abrirModalExclusao(id) {
  consultaIdParaExcluir = id
  const modal = document.getElementById("modalExcluir")
  modal.style.display = "block"
}

// Função para fechar o modal de exclusão
function fecharModalExclusao() {
  const modal = document.getElementById("modalExcluir")
  modal.style.display = "none"
  consultaIdParaExcluir = null
}

// Função para excluir consulta
function excluirConsulta() {
  if (!consultaIdParaExcluir) return

  fetch(`../api/consultas/excluir_consulta.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id: consultaIdParaExcluir }),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error("Erro ao excluir consulta")
      }
      return response.json()
    })
    .then((data) => {
      if (data.sucesso) {
        fecharModalExclusao()
        carregarConsultas(paginaAtual)
        alert("Consulta excluída com sucesso!")
      } else {
        alert(data.mensagem || "Erro ao excluir consulta")
      }
    })
    .catch((error) => {
      console.error("Erro:", error)
      alert("Erro ao excluir consulta. Por favor, tente novamente.")
    })
}

// Event Listeners
document.addEventListener("DOMContentLoaded", () => {
  // Carregar consultas iniciais
  carregarConsultas()

  // Botão de filtros
  const btnFiltros = document.getElementById("btnFiltros")
  const filtrosContainer = document.getElementById("filtrosContainer")

  btnFiltros.addEventListener("click", () => {
    filtrosContainer.style.display = filtrosContainer.style.display === "none" ? "block" : "none"
  })

  // Botão limpar filtros
  const btnLimparFiltros = document.getElementById("btnLimparFiltros")
  btnLimparFiltros.addEventListener("click", () => {
    window.location.href = "consultas.php"
  })

  // Modal de exclusão
  const btnConfirmarExclusao = document.getElementById("btnConfirmarExclusao")
  const btnCancelarExclusao = document.getElementById("btnCancelarExclusao")
  const spanClose = document.querySelector(".close")

  btnConfirmarExclusao.addEventListener("click", excluirConsulta)
  btnCancelarExclusao.addEventListener("click", fecharModalExclusao)
  spanClose.addEventListener("click", fecharModalExclusao)

  // Fechar modal ao clicar fora dele
  window.addEventListener("click", (event) => {
    const modal = document.getElementById("modalExcluir")
    if (event.target === modal) {
      fecharModalExclusao()
    }
  })

  // Atualizar lista de pets quando o cliente for alterado
  const clienteSelect = document.getElementById("cliente")
  const petSelect = document.getElementById("pet")

  if (clienteSelect && petSelect) {
    clienteSelect.addEventListener("change", function () {
      const clienteId = this.value

      // Limpar select de pets
      petSelect.innerHTML = '<option value="">Todos</option>'

      if (clienteId) {
        // Carregar pets do cliente selecionado
        fetch(`../api/pets/listar_pets.php?cliente_id=${clienteId}`)
          .then((response) => response.json())
          .then((data) => {
            data.forEach((pet) => {
              const option = document.createElement("option")
              option.value = pet.id
              option.textContent = pet.nome
              petSelect.appendChild(option)
            })
          })
          .catch((error) => console.error("Erro ao carregar pets:", error))
      }
    })
  }
})
