document.addEventListener("DOMContentLoaded", () => {
    // Inicializar máscaras para CPF e telefone
    inicializarMascaras()
  
    // Verificar se há mensagens na URL
    const urlParams = new URLSearchParams(window.location.search)
    const mensagem = urlParams.get("mensagem")
    const tipo = urlParams.get("tipo")
  
    if (mensagem) {
      mostrarMensagem(mensagem, tipo)
    }
  
    // Inicializar validação de CPF
    const cpfInput = document.getElementById("cpf_tutor")
    if (cpfInput) {
      cpfInput.addEventListener("blur", function () {
        validarCPF(this.value)
      })
    }
  })
  
  // Função para inicializar máscaras
  function inicializarMascaras() {
    // Máscara para CPF
    const cpfInput = document.getElementById("cpf_tutor")
    if (cpfInput) {
      cpfInput.addEventListener("input", (e) => {
        let value = e.target.value.replace(/\D/g, "")
        if (value.length > 11) {
          value = value.slice(0, 11)
        }
  
        if (value.length > 9) {
          value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, "$1.$2.$3-$4")
        } else if (value.length > 6) {
          value = value.replace(/^(\d{3})(\d{3})(\d{1,3})$/, "$1.$2.$3")
        } else if (value.length > 3) {
          value = value.replace(/^(\d{3})(\d{1,3})$/, "$1.$2")
        }
  
        e.target.value = value
      })
    }
  
    // Máscara para telefone
    const telefoneInput = document.getElementById("telefone_tutor")
    if (telefoneInput) {
      telefoneInput.addEventListener("input", (e) => {
        let value = e.target.value.replace(/\D/g, "")
        if (value.length > 11) {
          value = value.slice(0, 11)
        }
  
        if (value.length > 10) {
          value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, "($1) $2-$3")
        } else if (value.length > 6) {
          value = value.replace(/^(\d{2})(\d{4})(\d{0,4})$/, "($1) $2-$3")
        } else if (value.length > 2) {
          value = value.replace(/^(\d{2})(\d{0,5})$/, "($1) $2")
        }
  
        e.target.value = value
      })
    }
  }
  
  // Função para validar CPF
  function validarCPF(cpf) {
    // Elimina possível máscara
    cpf = cpf.replace(/[^\d]+/g, "")
  
    // Verifica se o número de dígitos informados é igual a 11
    if (cpf.length !== 11) {
      mostrarErro("CPF inválido. O CPF deve conter 11 dígitos.")
      return false
    }
  
    // Verifica se nenhuma das sequências inválidas abaixo foi digitada
    if (
      cpf === "00000000000" ||
      cpf === "11111111111" ||
      cpf === "22222222222" ||
      cpf === "33333333333" ||
      cpf === "44444444444" ||
      cpf === "55555555555" ||
      cpf === "66666666666" ||
      cpf === "77777777777" ||
      cpf === "88888888888" ||
      cpf === "99999999999"
    ) {
      mostrarErro("CPF inválido.")
      return false
    }
  
    // Calcula os dígitos verificadores para verificar se o CPF é válido
    let soma = 0
    let resto
  
    for (let i = 1; i <= 9; i++) {
      soma = soma + Number.parseInt(cpf.substring(i - 1, i)) * (11 - i)
    }
  
    resto = (soma * 10) % 11
  
    if (resto === 10 || resto === 11) {
      resto = 0
    }
  
    if (resto !== Number.parseInt(cpf.substring(9, 10))) {
      mostrarErro("CPF inválido.")
      return false
    }
  
    soma = 0
  
    for (let i = 1; i <= 10; i++) {
      soma = soma + Number.parseInt(cpf.substring(i - 1, i)) * (12 - i)
    }
  
    resto = (soma * 10) % 11
  
    if (resto === 10 || resto === 11) {
      resto = 0
    }
  
    if (resto !== Number.parseInt(cpf.substring(10, 11))) {
      mostrarErro("CPF inválido.")
      return false
    }
  
    return true
  }
  
  // Função para mostrar mensagem de erro
  function mostrarErro(mensagem) {
    const cpfInput = document.getElementById("cpf_tutor")
    if (cpfInput) {
      const errorDiv = document.createElement("div")
      errorDiv.className = "mensagem mensagem-erro"
      errorDiv.textContent = mensagem
  
      // Remover mensagens de erro anteriores
      const existingError = cpfInput.parentNode.querySelector(".mensagem-erro")
      if (existingError) {
        existingError.remove()
      }
  
      // Inserir mensagem de erro após o input
      cpfInput.parentNode.insertBefore(errorDiv, cpfInput.nextSibling)
  
      // Destacar o campo com erro
      cpfInput.style.borderColor = "#dc3545"
    }
  }
  
  // Função para mostrar mensagem
  function mostrarMensagem(mensagem, tipo) {
    const container = document.querySelector(".card")
    if (container) {
      const mensagemDiv = document.createElement("div")
      mensagemDiv.className = "mensagem " + (tipo === "sucesso" ? "mensagem-sucesso" : "mensagem-erro")
      mensagemDiv.textContent = mensagem
  
      // Inserir no início do container, após o título
      const titulo = container.querySelector("h1")
      if (titulo) {
        container.insertBefore(mensagemDiv, titulo.nextSibling)
      } else {
        container.insertBefore(mensagemDiv, container.firstChild)
      }
  
      // Remover a mensagem após 5 segundos
      setTimeout(() => {
        mensagemDiv.remove()
      }, 5000)
    }
  }
  
  // Função para alternar entre as abas
  function showTab(tabName) {
    // Esconde todas as abas
    document.querySelectorAll(".tab-content").forEach((tab) => {
      tab.classList.remove("active")
    })
  
    // Mostra a aba selecionada
    document.getElementById("tab-" + tabName).classList.add("active")
  
    // Atualiza o estado ativo das abas
    document.querySelectorAll(".tab").forEach((tab) => {
      tab.classList.remove("active")
    })
  
    // Encontra a aba clicada e adiciona a classe active
    document.querySelector(`.tab[onclick="showTab('${tabName}')"]`).classList.add("active")
  }
  
  // Função para confirmar exclusão
  function confirmarExclusao(id, tipo) {
    let mensagem = ""
    let url = ""
  
    if (tipo === "pet") {
      mensagem = "Tem certeza que deseja excluir este pet?"
      url = "cadastrar_pets.php?excluir=" + id
    } else if (tipo === "tutor") {
      mensagem = "Tem certeza que deseja excluir este tutor? Esta ação também excluirá todos os pets associados a ele."
      url = "excluir_tutor.php?id=" + id
    }
  
    if (confirm(mensagem)) {
      window.location.href = url
    }
  }
  