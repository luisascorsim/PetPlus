document.addEventListener("DOMContentLoaded", () => {
  // Carregar lista de clientes ao iniciar a página
  carregarClientes()

  // Configurar eventos
  const btnNovoCliente = document.getElementById("btnNovoCliente")
  if (btnNovoCliente) {
    btnNovoCliente.addEventListener("click", abrirModalNovoCliente)
  }

  const btnCancelar = document.getElementById("btnCancelar")
  if (btnCancelar) {
    btnCancelar.addEventListener("click", fecharModal)
  }

  const formCliente = document.getElementById("formCliente")
  if (formCliente) {
    formCliente.addEventListener("submit", salvarCliente)
  }

  const btnPesquisar = document.getElementById("btnPesquisar")
  if (btnPesquisar) {
    btnPesquisar.addEventListener("click", pesquisarClientes)
  }

  const inputPesquisa = document.getElementById("inputPesquisa")
  if (inputPesquisa) {
    inputPesquisa.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        pesquisarClientes()
      }
    })
  }

  // Fechar modal ao clicar fora dele
  window.addEventListener("click", (event) => {
    const modal = document.getElementById("clienteModal")
    if (modal && event.target === modal) {
      fecharModal()
    }
  })

  // Fechar modal ao clicar no X
  const closeBtn = document.querySelector(".close")
  if (closeBtn) {
    closeBtn.addEventListener("click", fecharModal)
  }
})

// Função para carregar a lista de clientes
function carregarClientes(pagina = 1, termo = "") {
  const tbody = document.querySelector("#clientesTable tbody")
  if (!tbody) {
    console.error("Elemento tbody não encontrado")
    return
  }

  tbody.innerHTML = '<tr><td colspan="6" class="text-center">Carregando...</td></tr>'

  // Fazer requisição AJAX para buscar clientes
  fetch(`../api/clientes/listar_clientes.php?pagina=${pagina}&termo=${termo}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.erro) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center">Erro: ${data.mensagem}</td></tr>`
        return
      }

      if (data.clientes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center">Nenhum cliente encontrado</td></tr>'
        return
      }

      // Limpar tabela
      tbody.innerHTML = ""

      // Preencher tabela com dados dos clientes
      data.clientes.forEach((cliente) => {
        const row = document.createElement("tr")
        row.innerHTML = `
                    <td>${cliente.id}</td>
                    <td>${cliente.nome}</td>
                    <td>${cliente.email}</td>
                    <td>${cliente.telefone}</td>
                    <td>${cliente.qtd_pets || 0}</td>
                    <td class="action-buttons">
                        <button class="btn-view" onclick="verCliente(${cliente.id})">Ver</button>
                        <button class="btn-edit" onclick="editarCliente(${cliente.id})">Editar</button>
                        <button class="btn-delete" onclick="confirmarExclusao(${cliente.id})">Excluir</button>
                    </td>
                `
        tbody.appendChild(row)
      })

      // Atualizar paginação
      atualizarPaginacao(data.total_paginas, pagina, termo)
    })
    .catch((error) => {
      console.error("Erro ao carregar clientes:", error)
      tbody.innerHTML = '<tr><td colspan="6" class="text-center">Erro ao carregar clientes</td></tr>'
    })
}

// Função para atualizar a paginação
function atualizarPaginacao(totalPaginas, paginaAtual, termo) {
  const paginacao = document.getElementById("paginacao")
  if (!paginacao) {
    console.error("Elemento de paginação não encontrado")
    return
  }

  paginacao.innerHTML = ""

  // Botão anterior
  if (paginaAtual > 1) {
    const anterior = document.createElement("a")
    anterior.href = "#"
    anterior.textContent = "«"
    anterior.addEventListener("click", (e) => {
      e.preventDefault()
      carregarClientes(paginaAtual - 1, termo)
    })
    paginacao.appendChild(anterior)
  }

  // Páginas
  for (let i = 1; i <= totalPaginas; i++) {
    const link = document.createElement("a")
    link.href = "#"
    link.textContent = i
    if (i === paginaAtual) {
      link.classList.add("active")
    }
    link.addEventListener("click", (e) => {
      e.preventDefault()
      carregarClientes(i, termo)
    })
    paginacao.appendChild(link)
  }

  // Botão próximo
  if (paginaAtual < totalPaginas) {
    const proximo = document.createElement("a")
    proximo.href = "#"
    proximo.textContent = "»"
    proximo.addEventListener("click", (e) => {
      e.preventDefault()
      carregarClientes(paginaAtual + 1, termo)
    })
    paginacao.appendChild(proximo)
  }
}

// Função para pesquisar clientes
function pesquisarClientes() {
  const inputPesquisa = document.getElementById("inputPesquisa")
  if (!inputPesquisa) {
    console.error("Elemento de pesquisa não encontrado")
    return
  }

  const termo = inputPesquisa.value.trim()
  carregarClientes(1, termo)
}

// Função para abrir modal de novo cliente
function abrirModalNovoCliente() {
  const clienteId = document.getElementById("clienteId")
  const formCliente = document.getElementById("formCliente")
  const modalTitle = document.getElementById("modalTitle")
  const clienteModal = document.getElementById("clienteModal")

  if (!clienteId || !formCliente || !modalTitle || !clienteModal) {
    console.error("Elementos do modal não encontrados")
    return
  }

  clienteId.value = ""
  formCliente.reset()
  modalTitle.textContent = "Novo Cliente"
  clienteModal.style.display = "block"
}

// Função para fechar o modal
function fecharModal() {
  const clienteModal = document.getElementById("clienteModal")
  if (clienteModal) {
    clienteModal.style.display = "none"
  }
}

// Função para salvar cliente (novo ou edição)
function salvarCliente(e) {
  e.preventDefault()

  const clienteId = document.getElementById("clienteId")
  const formCliente = document.getElementById("formCliente")

  if (!clienteId || !formCliente) {
    console.error("Elementos do formulário não encontrados")
    return
  }

  const formData = new FormData(formCliente)

  // URL da API (cadastrar ou editar)
  const url = clienteId.value ? "../api/clientes/editar_cliente.php" : "../api/clientes/cadastrar_cliente.php"

  // Adicionar ID se for edição
  if (clienteId.value) {
    formData.append("id", clienteId.value)
  }

  // Enviar dados para a API
  fetch(url, {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.erro) {
        alert(`Erro: ${data.mensagem}`)
        return
      }

      alert(data.mensagem)
      fecharModal()
      carregarClientes() // Recarregar lista de clientes
    })
    .catch((error) => {
      console.error("Erro ao salvar cliente:", error)
      alert("Erro ao salvar cliente. Verifique o console para mais detalhes.")
    })
}

// Função para editar cliente
function editarCliente(id) {
  // Buscar dados do cliente
  fetch(`../api/clientes/obter_cliente.php?id=${id}`)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.erro) {
        alert(`Erro: ${data.mensagem}`)
        return
      }

      // Preencher formulário com dados do cliente
      const cliente = data.cliente
      const clienteId = document.getElementById("clienteId")
      const nome = document.getElementById("nome")
      const email = document.getElementById("email")
      const telefone = document.getElementById("telefone")
      const cpf = document.getElementById("cpf")
      const endereco = document.getElementById("endereco")
      const cidade = document.getElementById("cidade")
      const estado = document.getElementById("estado")
      const cep = document.getElementById("cep")
      const modalTitle = document.getElementById("modalTitle")
      const clienteModal = document.getElementById("clienteModal")

      if (
        !clienteId ||
        !nome ||
        !email ||
        !telefone ||
        !cpf ||
        !endereco ||
        !cidade ||
        !estado ||
        !cep ||
        !modalTitle ||
        !clienteModal
      ) {
        console.error("Elementos do formulário não encontrados")
        return
      }

      clienteId.value = cliente.id
      nome.value = cliente.nome
      email.value = cliente.email
      telefone.value = cliente.telefone
      cpf.value = cliente.cpf
      endereco.value = cliente.endereco
      cidade.value = cliente.cidade
      estado.value = cliente.estado
      cep.value = cliente.cep

      // Abrir modal
      modalTitle.textContent = "Editar Cliente"
      clienteModal.style.display = "block"
    })
    .catch((error) => {
      console.error("Erro ao buscar dados do cliente:", error)
      alert("Erro ao buscar dados do cliente")
    })
}

// Função para confirmar exclusão de cliente
function confirmarExclusao(id) {
  if (confirm("Tem certeza que deseja excluir este cliente? Esta ação não pode ser desfeita.")) {
    excluirCliente(id)
  }
}

// Função para excluir cliente
function excluirCliente(id) {
  fetch("../api/clientes/excluir_cliente.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id=${id}`,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.erro) {
        alert(`Erro: ${data.mensagem}`)
        return
      }

      alert(data.mensagem)
      carregarClientes() // Recarregar lista de clientes
    })
    .catch((error) => {
      console.error("Erro ao excluir cliente:", error)
      alert("Erro ao excluir cliente")
    })
}

// Função para ver detalhes do cliente
function verCliente(id) {
  window.location.href = `detalhes_cliente.php?id=${id}`
}
