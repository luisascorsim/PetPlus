document.addEventListener("DOMContentLoaded", () => {
  // Elementos do DOM
  const btnNovaVacina = document.getElementById("btnNovaVacina")
  const vacinaModal = document.getElementById("vacinaModal")
  const confirmacaoModal = document.getElementById("confirmacaoModal")
  const btnCancelar = document.getElementById("btnCancelar")
  const btnSalvar = document.getElementById("btnSalvar")
  const btnCancelarExclusao = document.getElementById("btnCancelarExclusao")
  const btnConfirmarExclusao = document.getElementById("btnConfirmarExclusao")
  const closeButtons = document.querySelectorAll(".close")
  const tipoVacina = document.getElementById("tipoVacina")
  const outroTipoGroup = document.getElementById("outroTipoGroup")
  const tabelaVacinas = document.getElementById("tabelaVacinas")
  const listaProximasVacinas = document.getElementById("listaProximasVacinas")
  const paginacao = document.getElementById("paginacao")

  // Variáveis de estado
  let vacinaParaExcluir = null
  let paginaAtual = 1
  let totalPaginas = 1

  // Inicialização
  carregarPets()
  carregarVeterinarios()
  carregarVacinas()
  carregarProximasVacinas()
  carregarEstatisticas()

  // Event Listeners
  btnNovaVacina.addEventListener("click", abrirModalNovaVacina)
  btnCancelar.addEventListener("click", fecharModais)
  btnSalvar.addEventListener("click", salvarVacina)
  btnCancelarExclusao.addEventListener("click", fecharModais)
  btnConfirmarExclusao.addEventListener("click", excluirVacina)

  tipoVacina.addEventListener("change", function () {
    if (this.value === "outra") {
      outroTipoGroup.style.display = "block"
    } else {
      outroTipoGroup.style.display = "none"
    }
  })

  closeButtons.forEach((button) => {
    button.addEventListener("click", fecharModais)
  })

  // Fechar modais ao clicar fora deles
  window.addEventListener("click", (event) => {
    if (event.target === vacinaModal) {
      fecharModais()
    } else if (event.target === confirmacaoModal) {
      fecharModais()
    }
  })

  // Funções
  function carregarPets() {
    fetch("../api/pets/listar_pets.php")
      .then((response) => response.json())
      .then((data) => {
        const petSelect = document.getElementById("pet_id")
        const petSelectModal = document.getElementById("petIdModal")

        if (data.success && data.pets) {
          // Limpar opções existentes
          while (petSelect.options.length > 1) {
            petSelect.remove(1)
          }

          while (petSelectModal.options.length > 1) {
            petSelectModal.remove(1)
          }

          // Adicionar novas opções
          data.pets.forEach((pet) => {
            const option = document.createElement("option")
            option.value = pet.id
            option.textContent = `${pet.nome} (${pet.proprietario})`

            const optionModal = option.cloneNode(true)

            petSelect.appendChild(option)
            petSelectModal.appendChild(optionModal)
          })

          // Verificar se há um pet_id na URL
          const urlParams = new URLSearchParams(window.location.search)
          const petId = urlParams.get("pet_id")

          if (petId) {
            petSelect.value = petId
          }
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar pets:", error)
      })
  }

  function carregarVeterinarios() {
    fetch("../api/veterinarios/listar_veterinarios.php")
      .then((response) => response.json())
      .then((data) => {
        const vetSelect = document.getElementById("veterinarioIdModal")

        if (data.success && data.veterinarios) {
          // Limpar opções existentes
          while (vetSelect.options.length > 1) {
            vetSelect.remove(1)
          }

          // Adicionar novas opções
          data.veterinarios.forEach((vet) => {
            const option = document.createElement("option")
            option.value = vet.id
            option.textContent = vet.nome
            vetSelect.appendChild(option)
          })
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar veterinários:", error)

        // Adicionar opções padrão em caso de erro
        const vetSelect = document.getElementById("veterinarioIdModal")
        const defaultVets = [
          { id: 1, nome: "Dr. Carlos Silva" },
          { id: 2, nome: "Dra. Ana Oliveira" },
          { id: 3, nome: "Dr. Roberto Santos" },
        ]

        // Limpar opções existentes
        while (vetSelect.options.length > 1) {
          vetSelect.remove(1)
        }

        defaultVets.forEach((vet) => {
          const option = document.createElement("option")
          option.value = vet.id
          option.textContent = vet.nome
          vetSelect.appendChild(option)
        })
      })
  }

  function carregarVacinas(pagina = 1) {
    tabelaVacinas.innerHTML = '<tr><td colspan="7" class="loading">Carregando vacinas...</td></tr>'

    // Obter parâmetros de filtro da URL
    const urlParams = new URLSearchParams(window.location.search)
    const pet_id = urlParams.get("pet_id") || ""
    const tipo_vacina = urlParams.get("tipo_vacina") || ""
    const data_inicio = urlParams.get("data_inicio") || ""
    const data_fim = urlParams.get("data_fim") || ""

    // Construir URL com filtros
    let url = `../api/vacinas/listar_vacinas.php?pagina=${pagina}`

    if (pet_id) url += `&pet_id=${pet_id}`
    if (tipo_vacina) url += `&tipo_vacina=${tipo_vacina}`
    if (data_inicio) url += `&data_inicio=${data_inicio}`
    if (data_fim) url += `&data_fim=${data_fim}`

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          paginaAtual = pagina
          totalPaginas = data.total_paginas || 1

          renderizarVacinas(data.vacinas || [])
          renderizarPaginacao()
        } else {
          tabelaVacinas.innerHTML = `<tr><td colspan="7" class="sem-vacinas">${data.message || "Erro ao carregar vacinas"}</td></tr>`
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar vacinas:", error)
        tabelaVacinas.innerHTML =
          '<tr><td colspan="7" class="sem-vacinas">Erro ao carregar vacinas. Tente novamente mais tarde.</td></tr>'
      })
  }

  function renderizarVacinas(vacinas) {
    if (vacinas.length === 0) {
      tabelaVacinas.innerHTML = '<tr><td colspan="7" class="sem-vacinas">Nenhuma vacina encontrada.</td></tr>'
      return
    }

    tabelaVacinas.innerHTML = ""

    vacinas.forEach((vacina) => {
      const row = document.createElement("tr")

      // Formatar datas
      const dataAplicacao = new Date(vacina.data_aplicacao)
      const dataAplicacaoFormatada = dataAplicacao.toLocaleDateString("pt-BR")

      let proximaDoseFormatada = "-"
      if (vacina.proxima_dose) {
        const proximaDose = new Date(vacina.proxima_dose)
        proximaDoseFormatada = proximaDose.toLocaleDateString("pt-BR")
      }

      // Determinar status
      let status = "Aplicada"
      let statusClass = "status-aplicada"

      if (vacina.proxima_dose) {
        const hoje = new Date()
        const proximaDose = new Date(vacina.proxima_dose)

        if (proximaDose < hoje) {
          status = "Vencida"
          statusClass = "status-vencida"
        } else if ((proximaDose - hoje) / (1000 * 60 * 60 * 24) <= 30) {
          status = "Pendente"
          statusClass = "status-pendente"
        }
      }

      // Formatar tipo de vacina
      let tipoVacina = vacina.tipo_vacina
      if (tipoVacina === "v8") tipoVacina = "V8 (Cães)"
      else if (tipoVacina === "v10") tipoVacina = "V10 (Cães)"
      else if (tipoVacina === "v3") tipoVacina = "V3 (Gatos)"
      else if (tipoVacina === "v4") tipoVacina = "V4 (Gatos)"
      else if (tipoVacina === "v5") tipoVacina = "V5 (Gatos)"
      else if (tipoVacina === "antirrabica") tipoVacina = "Antirrábica"
      else if (tipoVacina === "giardase") tipoVacina = "Giardíase"
      else if (tipoVacina === "gripe") tipoVacina = "Gripe Canina"
      else if (tipoVacina === "leishmaniose") tipoVacina = "Leishmaniose"
      else if (tipoVacina === "outra" && vacina.outro_tipo) tipoVacina = vacina.outro_tipo

      row.innerHTML = `
                <td>${vacina.pet_nome}</td>
                <td>${tipoVacina}</td>
                <td>${dataAplicacaoFormatada}</td>
                <td>${proximaDoseFormatada}</td>
                <td>${vacina.veterinario_nome || "-"}</td>
                <td class="${statusClass}">${status}</td>
                <td>
                    <div class="acoes-vacina">
                        <button class="btn-acao btn-editar" data-id="${vacina.id}" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-acao btn-excluir" data-id="${vacina.id}" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `

      tabelaVacinas.appendChild(row)
    })

    // Adicionar event listeners para os botões
    document.querySelectorAll(".btn-editar").forEach((btn) => {
      btn.addEventListener("click", () => editarVacina(btn.getAttribute("data-id")))
    })

    document.querySelectorAll(".btn-excluir").forEach((btn) => {
      btn.addEventListener("click", () => confirmarExclusaoVacina(btn.getAttribute("data-id")))
    })
  }

  function renderizarPaginacao() {
    paginacao.innerHTML = ""

    if (totalPaginas <= 1) return

    // Botão anterior
    if (paginaAtual > 1) {
      const btnAnterior = document.createElement("a")
      btnAnterior.href = "javascript:void(0)"
      btnAnterior.className = "pagina-link"
      btnAnterior.textContent = "«"
      btnAnterior.addEventListener("click", () => carregarVacinas(paginaAtual - 1))
      paginacao.appendChild(btnAnterior)
    }

    // Páginas
    for (let i = 1; i <= totalPaginas; i++) {
      const btnPagina = document.createElement("a")
      btnPagina.href = "javascript:void(0)"
      btnPagina.className = "pagina-link"
      if (i === paginaAtual) btnPagina.className += " ativa"
      btnPagina.textContent = i
      btnPagina.addEventListener("click", () => carregarVacinas(i))
      paginacao.appendChild(btnPagina)
    }

    // Botão próximo
    if (paginaAtual < totalPaginas) {
      const btnProximo = document.createElement("a")
      btnProximo.href = "javascript:void(0)"
      btnProximo.className = "pagina-link"
      btnProximo.textContent = "»"
      btnProximo.addEventListener("click", () => carregarVacinas(paginaAtual + 1))
      paginacao.appendChild(btnProximo)
    }
  }

  function carregarProximasVacinas() {
    listaProximasVacinas.innerHTML = '<div class="loading">Carregando...</div>'

    fetch("../api/vacinas/proximas_vacinas.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          renderizarProximasVacinas(data.vacinas || [])
        } else {
          listaProximasVacinas.innerHTML = `<div class="sem-vacinas">${data.message || "Erro ao carregar próximas vacinas"}</div>`
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar próximas vacinas:", error)
        listaProximasVacinas.innerHTML =
          '<div class="sem-vacinas">Erro ao carregar próximas vacinas. Tente novamente mais tarde.</div>'
      })
  }

  function renderizarProximasVacinas(vacinas) {
    if (vacinas.length === 0) {
      listaProximasVacinas.innerHTML = '<div class="sem-vacinas">Nenhuma vacina pendente.</div>'
      return
    }

    listaProximasVacinas.innerHTML = ""

    vacinas.forEach((vacina) => {
      const item = document.createElement("div")
      item.className = "proxima-vacina-item"

      // Formatar data
      const proximaDose = new Date(vacina.proxima_dose)
      const dataFormatada = proximaDose.toLocaleDateString("pt-BR")

      // Formatar tipo de vacina
      let tipoVacina = vacina.tipo_vacina
      if (tipoVacina === "v8") tipoVacina = "V8 (Cães)"
      else if (tipoVacina === "v10") tipoVacina = "V10 (Cães)"
      else if (tipoVacina === "v3") tipoVacina = "V3 (Gatos)"
      else if (tipoVacina === "v4") tipoVacina = "V4 (Gatos)"
      else if (tipoVacina === "v5") tipoVacina = "V5 (Gatos)"
      else if (tipoVacina === "antirrabica") tipoVacina = "Antirrábica"
      else if (tipoVacina === "giardase") tipoVacina = "Giardíase"
      else if (tipoVacina === "gripe") tipoVacina = "Gripe Canina"
      else if (tipoVacina === "leishmaniose") tipoVacina = "Leishmaniose"
      else if (tipoVacina === "outra" && vacina.outro_tipo) tipoVacina = vacina.outro_tipo

      item.innerHTML = `
                <div class="proxima-vacina-info">
                    <div class="proxima-vacina-pet">${vacina.pet_nome}</div>
                    <div class="proxima-vacina-tipo">${tipoVacina}</div>
                </div>
                <div class="proxima-vacina-data">${dataFormatada}</div>
            `

      listaProximasVacinas.appendChild(item)
    })
  }

  function carregarEstatisticas() {
    fetch("../api/vacinas/estatisticas.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          document.getElementById("totalVacinas").textContent = data.total_vacinas || 0
          document.getElementById("vacinasUltimoMes").textContent = data.vacinas_ultimo_mes || 0
          document.getElementById("proximasVacinas").textContent = data.proximas_vacinas || 0
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar estatísticas:", error)
      })
  }

  function abrirModalNovaVacina() {
    document.getElementById("modalTitle").textContent = "Nova Vacina"
    document.getElementById("vacinaId").value = ""
    document.getElementById("vacinaForm").reset()

    // Definir data padrão como hoje
    const hoje = new Date()
    const dataFormatada = hoje.toISOString().split("T")[0]
    document.getElementById("dataAplicacao").value = dataFormatada

    // Esconder campo de outro tipo
    outroTipoGroup.style.display = "none"

    // Verificar se há um pet_id na URL
    const urlParams = new URLSearchParams(window.location.search)
    const petId = urlParams.get("pet_id")

    if (petId) {
      document.getElementById("petIdModal").value = petId
    }

    vacinaModal.style.display = "block"
  }

  function editarVacina(id) {
    fetch(`../api/vacinas/obter_vacina.php?id=${id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.vacina) {
          const vacina = data.vacina

          // Preencher formulário
          document.getElementById("modalTitle").textContent = "Editar Vacina"
          document.getElementById("vacinaId").value = vacina.id
          document.getElementById("petIdModal").value = vacina.pet_id
          document.getElementById("tipoVacina").value = vacina.tipo_vacina

          if (vacina.tipo_vacina === "outra") {
            outroTipoGroup.style.display = "block"
            document.getElementById("outroTipo").value = vacina.outro_tipo || ""
          } else {
            outroTipoGroup.style.display = "none"
          }

          // Formatar datas
          const dataAplicacao = vacina.data_aplicacao ? vacina.data_aplicacao.split(" ")[0] : ""
          document.getElementById("dataAplicacao").value = dataAplicacao

          const proximaDose = vacina.proxima_dose ? vacina.proxima_dose.split(" ")[0] : ""
          document.getElementById("proximaDose").value = proximaDose

          document.getElementById("lote").value = vacina.lote || ""
          document.getElementById("fabricante").value = vacina.fabricante || ""
          document.getElementById("dose").value = vacina.dose || "1"
          document.getElementById("veterinarioIdModal").value = vacina.veterinario_id || ""
          document.getElementById("observacoes").value = vacina.observacoes || ""

          vacinaModal.style.display = "block"
        } else {
          alert("Erro ao carregar dados da vacina.")
        }
      })
      .catch((error) => {
        console.error("Erro ao carregar dados da vacina:", error)
        alert("Erro ao carregar dados da vacina.")
      })
  }

  function confirmarExclusaoVacina(id) {
    vacinaParaExcluir = id
    confirmacaoModal.style.display = "block"
  }

  function salvarVacina() {
    // Validar formulário
    const form = document.getElementById("vacinaForm")
    if (!form.checkValidity()) {
      form.reportValidity()
      return
    }

    // Obter dados do formulário
    const id = document.getElementById("vacinaId").value
    const petId = document.getElementById("petIdModal").value
    const tipoVacinaValue = document.getElementById("tipoVacina").value
    const outroTipo = document.getElementById("outroTipo").value
    const dataAplicacao = document.getElementById("dataAplicacao").value
    const lote = document.getElementById("lote").value
    const fabricante = document.getElementById("fabricante").value
    const dose = document.getElementById("dose").value
    const proximaDose = document.getElementById("proximaDose").value
    const veterinarioId = document.getElementById("veterinarioIdModal").value
    const observacoes = document.getElementById("observacoes").value

    // Validar tipo de vacina "outra"
    if (tipoVacinaValue === "outra" && !outroTipo) {
      alert("Por favor, especifique o tipo de vacina.")
      document.getElementById("outroTipo").focus()
      return
    }

    // Criar objeto com dados da vacina
    const dadosVacina = {
      id: id,
      pet_id: petId,
      tipo_vacina: tipoVacinaValue,
      outro_tipo: outroTipo,
      data_aplicacao: dataAplicacao,
      lote: lote,
      fabricante: fabricante,
      dose: dose,
      proxima_dose: proximaDose,
      veterinario_id: veterinarioId || null,
      observacoes: observacoes,
    }

    // Determinar URL da API (cadastrar ou editar)
    const url = id ? "../api/vacinas/editar_vacina.php" : "../api/vacinas/cadastrar_vacina.php"

    // Enviar dados para a API
    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(dadosVacina),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert(id ? "Vacina atualizada com sucesso!" : "Vacina registrada com sucesso!")
          fecharModais()
          carregarVacinas(paginaAtual)
          carregarProximasVacinas()
          carregarEstatisticas()
        } else {
          alert(`Erro: ${data.message || "Ocorreu um erro ao salvar a vacina."}`)
        }
      })
      .catch((error) => {
        console.error("Erro ao salvar vacina:", error)
        alert("Ocorreu um erro ao salvar a vacina. Tente novamente mais tarde.")
      })
  }

  function excluirVacina() {
    if (!vacinaParaExcluir) return

    fetch("../api/vacinas/excluir_vacina.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: vacinaParaExcluir }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          alert("Vacina excluída com sucesso!")
          fecharModais()
          carregarVacinas(paginaAtual)
          carregarProximasVacinas()
          carregarEstatisticas()
        } else {
          alert(`Erro: ${data.message || "Ocorreu um erro ao excluir a vacina."}`)
        }
      })
      .catch((error) => {
        console.error("Erro ao excluir vacina:", error)
        alert("Ocorreu um erro ao excluir a vacina. Tente novamente mais tarde.")
      })
  }

  function fecharModais() {
    vacinaModal.style.display = "none"
    confirmacaoModal.style.display = "none"
  }
})
