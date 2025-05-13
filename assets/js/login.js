document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form")

  if (loginForm) {
    // Adiciona validação básica do formulário
    loginForm.addEventListener("submit", (e) => {
      e.preventDefault()

      const username = document.getElementById("username").value
      const password = document.getElementById("password").value

      if (!username || !password) {
        showAlert("Por favor, preencha todos os campos", "error")
        return
      }

      // Enviar formulário via AJAX
      const formData = new FormData()
      formData.append("username", username)
      formData.append("password", password)

      fetch("login.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            window.location.href = "dashboard.php"
          } else {
            showAlert(data.message || "Erro ao fazer login. Verifique suas credenciais.", "error")
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

    const form = document.getElementById("login-form")
    form.insertBefore(alertDiv, form.firstChild)

    setTimeout(() => {
      alertDiv.remove()
    }, 5000)
  }
})
