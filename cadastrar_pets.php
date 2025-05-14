<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.html');
    exit;
}

$nome_usuario = $_SESSION['usuario_nome'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Pets - PetPlus</title>
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/cadastrar-pets.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Conteúdo principal -->
        <div class="main-content">
            <header class="dashboard-header">
                <h1>Cadastrar Pets</h1>
                <div class="header-actions">
                    <div class="search-box">
                        <input type="text" placeholder="Pesquisar pet...">
                        <button type="button" class="search-btn">
                            <i class="search-icon"></i>
                        </button>
                    </div>
                    <button class="btn btn-primary" id="new-pet-btn">Novo Pet</button>
                </div>
            </header>

            <div class="dashboard-content">
                <div class="card">
                    <div class="card-header">
                        <h2>Lista de Pets</h2>
                        <div class="filter-options">
                            <select id="filter-type">
                                <option value="">Todos os tipos</option>
                                <option value="Cachorro">Cachorro</option>
                                <option value="Gato">Gato</option>
                                <option value="Ave">Ave</option>
                                <option value="Roedor">Roedor</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table pets-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Foto</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Raça</th>
                                    <th>Idade</th>
                                    <th>Proprietário</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="pets-list">
                                <!-- Dados serão carregados via JavaScript -->
                            </tbody>
                        </table>
                        <div class="pagination">
                            <button class="btn btn-secondary" id="prev-page" disabled>Anterior</button>
                            <span id="page-info">Página 1 de 1</span>
                            <button class="btn btn-secondary" id="next-page" disabled>Próximo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição de Pet -->
    <div class="modal" id="pet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-title">Novo Pet</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pet-form">
                    <input type="hidden" id="pet-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pet-name">Nome do Pet</label>
                            <input type="text" id="pet-name" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="pet-type">Tipo</label>
                            <select id="pet-type" name="tipo" required>
                                <option value="">Selecione</option>
                                <option value="Cachorro">Cachorro</option>
                                <option value="Gato">Gato</option>
                                <option value="Ave">Ave</option>
                                <option value="Roedor">Roedor</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pet-breed">Raça</label>
                            <input type="text" id="pet-breed" name="raca">
                        </div>
                        <div class="form-group">
                            <label for="pet-age">Idade (anos)</label>
                            <input type="number" id="pet-age" name="idade" min="0" step="0.1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pet-weight">Peso (kg)</label>
                            <input type="number" id="pet-weight" name="peso" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label for="pet-gender">Sexo</label>
                            <select id="pet-gender" name="sexo">
                                <option value="">Selecione</option>
                                <option value="M">Macho</option>
                                <option value="F">Fêmea</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pet-owner">Proprietário</label>
                            <select id="pet-owner" name="tutor_id" required>
                                <option value="">Selecione</option>
                                <!-- Opções serão carregadas via JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="pet-birth">Data de Nascimento</label>
                            <input type="date" id="pet-birth" name="data_nascimento">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="pet-photo">Foto</label>
                        <input type="file" id="pet-photo" name="foto" accept="image/*">
                        <div id="photo-preview" class="photo-preview"></div>
                    </div>
                    <div class="form-group">
                        <label for="pet-notes">Observações</label>
                        <textarea id="pet-notes" name="observacoes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-pet">Cancelar</button>
                <button class="btn btn-primary" id="save-pet">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal" id="confirm-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmar Exclusão</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este pet? Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-delete">Cancelar</button>
                <button class="btn btn-danger" id="confirm-delete">Excluir</button>
            </div>
        </div>
    </div>

    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/cadastrar-pets.js"></script>
</body>
</html>
