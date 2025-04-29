document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('formCadastro'); // Supondo que o formulário tenha o ID 'formCadastro'

    form.addEventListener('submit', function (event) {
        event.preventDefault(); // Evita o envio tradicional do formulário

        const nome = document.getElementById('nome').value;
        const email = document.getElementById('email').value;
        const senha = document.getElementById('senha').value;

        // Validação simples dos campos
        if (!nome || !email || !senha) {
            alert("Todos os campos são obrigatórios!");
            return;
        }

        // Validando o formato do email
        const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailRegex.test(email)) {
            alert("Por favor, insira um email válido.");
            return;
        }

        // Criando o objeto para enviar os dados via AJAX
        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('email', email);
        formData.append('senha', senha);

        // Enviar dados usando fetch
        fetch('cadastrar.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(data => {
                // Se a inserção for bem-sucedida, redireciona para a tela principal
                if (data.includes('Erro')) {
                    alert('Erro ao cadastrar: ' + data);
                } else {
                    window.location.href = 'Tela_Principal.php'; // Redireciona após o sucesso
                }
            })
            .catch(error => {
                console.error('Erro ao enviar dados:', error);
                alert('Ocorreu um erro. Tente novamente.');
            });
    });
});
