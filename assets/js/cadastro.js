document.addEventListener("DOMContentLoaded", () => {
  const cadastroForm = document.getElementById("cadastro-form")

  if (cadastroForm) {
    cadastroForm.addEventListener("submit", (e) => {
      e.preventDefault()

      const nome = document.getElementById("nome").value
      const email = document.getElementById("email").value
      const usuario = document.getElementById("usuario").value
      const senha = document.getElementById("senha").value
      const confirmarSenha = document.getElementById("confirmar_senha").value

      // Validações básicas
      if (!nome || !email || !usuario || !senha || !confirmarSenha) {
        showAlert("Por favor, preencha todos os campos", "error")
        return
      }

      if (senha !== confirmarSenha) {
        showAlert("As senhas não coincidem", "error")
        return
      }

      if (senha.length < 6) {
        showAlert("A senha deve ter pelo menos 6 caracteres", "error")
        return
      }

      // Enviar formulário via AJAX
      const formData = new FormData(cadastroForm)

      fetch("cadastro.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showAlert("Cadastro realizado com sucesso! Redirecionando para o login...", "success")
            setTimeout(() => {
              window.location.href = "index.html"
            }, 2000)
          } else {
            showAlert(data.message || "Erro ao realizar cadastro.", "error")
          }
        })
        .catch((error) => {
          console.error("Erro:", error)
          showAlert("Ocorreu um erro ao processar sua solicitação.", "error")
        })
    })
  }

  function showAlert(message, type) {
    const alertDiv = document.createElement("div")
    alertDiv.className = `alert alert-${type === "error" ? "danger" : "success"}`
    alertDiv.textContent = message

    const form = document.getElementById("cadastro-form")
    form.insertBefore(alertDiv, form.firstChild)

    setTimeout(() => {
      alertDiv.remove()
    }, 5000)
  }
})
