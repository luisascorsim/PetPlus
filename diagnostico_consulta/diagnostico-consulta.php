<?php
require_once('../conecta_db.php');
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    // Definir uma sessão temporária para desenvolvimento
    $_SESSION['usuario_id'] = 1;

}

// Define o caminho base para os recursos
$caminhoBase = '../';

// Inclui o header e sidebar
require_once('../includes/header.php');
require_once('../includes/sidebar.php');
?>

<div class="container">
    <div class="card">
        <div class="header-card">
            <h1>Prontuários</h1>
            <button class="btn-novo" onclick="abrirModalProntuario()">Novo Prontuário</button>
        </div>
        
        <div class="filtro-container">
            <form action="" method="GET" class="form-busca">
                <input type="text" name="busca" placeholder="Buscar por pet, tutor ou diagnóstico..." value="<?php echo isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : ''; ?>">
                <button type="submit" class="btn-buscar">Buscar</button>
                <?php if (isset($_GET['busca']) && !empty($_GET['busca'])): ?>
                    <a href="diagnostico-consulta.php" class="btn-limpar">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="prontuarios-lista">
            <div class="mensagem-vazia">
                <p>Nenhum prontuário encontrado. Clique em "Novo Prontuário" para adicionar.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Prontuário -->
<div id="modalProntuario" class="modal">
    <div class="modal-content">
        <span class="fechar" onclick="fecharModalProntuario()">&times;</span>
        <h2 id="tituloModal">Novo Prontuário</h2>
        <form id="formProntuario" method="POST" action="processar_prontuario.php">
            <input type="hidden" id="prontuario_id" name="prontuario_id" value="">
            
            <div class="form-group">
                <label for="pet_id">Pet:</label>
                <select id="pet_id" name="pet_id" required>
                    <option value="">Selecione um pet</option>
                    <!-- Opções serão carregadas via JavaScript -->
                </select>
            </div>
            
            <div class="form-group">
                <label for="data_consulta">Data da Consulta:</label>
                <input type="datetime-local" id="data_consulta" name="data_consulta" required>
            </div>
            
            <div class="form-group">
                <label for="sintomas">Sintomas:</label>
                <textarea id="sintomas" name="sintomas" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="diagnostico">Diagnóstico:</label>
                <textarea id="diagnostico" name="diagnostico" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="tratamento">Tratamento:</label>
                <textarea id="tratamento" name="tratamento" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-cancelar" onclick="fecharModalProntuario()">Cancelar</button>
                <button type="submit" class="btn-salvar">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .container {
        margin-left: 220px;
        padding: 80px 30px 30px;
        transition: margin-left 0.3s;
    }
    
    .card {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .header-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    h1 {
        color: #0b3556;
        margin: 0;
    }
    
    .btn-novo {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
        font-weight: 500;
    }
    
    .btn-novo:hover {
        background-color: #0d4371;
    }
    
    .filtro-container {
        margin-bottom: 20px;
    }
    
    .form-busca {
        display: flex;
        gap: 10px;
    }
    
    .form-busca input {
        flex: 1;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-buscar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        cursor: pointer;
    }
    
    .btn-limpar {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        text-decoration: none;
    }
    
    .mensagem-vazia {
        padding: 20px;
        text-align: center;
        color: #666;
    }
    
    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 600px;
        border-radius: 8px;
        position: relative;
    }
    
    .fechar {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .fechar:hover {
        color: black;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    
    .btn-cancelar {
        background-color: #f0f0f0;
        color: #333;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    .btn-salvar {
        background-color: #0b3556;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 15px;
        cursor: pointer;
    }
    
    /* Responsividade */
    @media (max-width: 768px) {
        .container {
            margin-left: 60px;
            padding: 70px 15px 15px;
        }
        
        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }
</style>

<script>
    // Função para abrir o modal de prontuário
    function abrirModalProntuario(prontuarioId = null) {
        const modal = document.getElementById('modalProntuario');
        const tituloModal = document.getElementById('tituloModal');
        const form = document.getElementById('formProntuario');
        
        modal.style.display = 'block';
        
        if (prontuarioId) {
            tituloModal.textContent = 'Editar Prontuário';
            document.getElementById('prontuario_id').value = prontuarioId;
            
            // Aqui você carregaria os dados do prontuário para edição
            // via AJAX ou de um objeto JavaScript
        } else {
            tituloModal.textContent = 'Novo Prontuário';
            form.reset();
        }
        
        // Carregar lista de pets
        carregarPets();
    }
    
    // Função para fechar o modal
    function fecharModalProntuario() {
        const modal = document.getElementById('modalProntuario');
        modal.style.display = 'none';
    }
    
    // Função para carregar a lista de pets
    function carregarPets() {
        // Simulação de dados
        const pets = [
            { id: 1, nome: 'Rex (João Silva)' },
            { id: 2, nome: 'Luna (João Silva)' },
            { id: 3, nome: 'Mel (Maria Oliveira)' },
            { id: 4, nome: 'Thor (Carlos Santos)' }
        ];
        
        const selectPet = document.getElementById('pet_id');
        selectPet.innerHTML = '<option value="">Selecione um pet</option>';
        
        pets.forEach(pet => {
            const option = document.createElement('option');
            option.value = pet.id;
            option.textContent = pet.nome;
            selectPet.appendChild(option);
        });
    }
    
    // Fecha o modal se o usuário clicar fora dele
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('modalProntuario');
        if (event.target == modal) {
            fecharModalProntuario();
        }
    });
</script>

</body>
</html>
