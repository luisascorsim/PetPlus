<?php
// Incluir arquivos necessários
require_once '../config/database.php';
require_once '../includes/funcoes.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Verificar se o ID do cliente foi fornecido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: clientes.php');
    exit;
}

$cliente_id = intval($_GET['id']);

// Obter dados do cliente
$pdo = Database::conexao();
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Se o cliente não existir, redirecionar
if (!$cliente) {
    header('Location: clientes.php');
    exit;
}

// Título da página
$titulo = "Editar Cliente - PetPlus";

// Incluir o cabeçalho
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2>Editar Cliente</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="clientes.php">Clientes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar Cliente</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Informações do Cliente</h5>
                </div>
                <div class="card-body">
                    <form id="formEditarCliente">
                        <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                        
                        <div class="form-group mb-3">
                            <label for="nome">Nome Completo*</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="cpf">CPF*</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($cliente['cpf']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="rg">RG</label>
                                    <input type="text" class="form-control" id="rg" name="rg" value="<?php echo htmlspecialchars($cliente['rg'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="email">E-mail*</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="telefone">Telefone*</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="endereco">Endereço*</label>
                            <input type="text" class="form-control" id="endereco" name="endereco" value="<?php echo htmlspecialchars($cliente['endereco']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="cidade">Cidade*</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cliente['cidade']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="estado">Estado*</label>
                                    <select class="form-control" id="estado" name="estado" required>
                                        <option value="">Selecione...</option>
                                        <?php
                                        $estados = [
                                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
                                            'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
                                            'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
                                            'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
                                            'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
                                            'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                        ];
                                        
                                        foreach ($estados as $sigla => $nome) {
                                            $selected = ($cliente['estado'] === $sigla) ? 'selected' : '';
                                            echo "<option value=\"$sigla\" $selected>$nome</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="cep">CEP*</label>
                                    <input type="text" class="form-control" id="cep" name="cep" value="<?php echo htmlspecialchars($cliente['cep']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="observacoes">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"><?php echo htmlspecialchars($cliente['observacoes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo ($cliente['ativo'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">
                                    Cliente Ativo
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="clientes.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informações Adicionais</h5>
                </div>
                <div class="card-body">
                    <p>Preencha todos os campos marcados com * (asterisco), pois são obrigatórios.</p>
                    <p>O CPF deve ser único no sistema e será utilizado para identificar o cliente.</p>
                    <hr>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Dica: Utilize o CEP para preencher automaticamente o endereço.
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Pets do Cliente</h5>
                </div>
                <div class="card-body">
                    <div id="listaPets">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="../cadastrar_pet/cadastrar_pets.php?cliente_id=<?php echo $cliente_id; ?>" class="btn btn-success btn-sm w-100">
                            <i class="fas fa-plus-circle"></i> Adicionar Novo Pet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CPF
    $('#cpf').mask('000.000.000-00');
    
    // Máscara para telefone
    $('#telefone').mask('(00) 00000-0000');
    
    // Máscara para CEP
    $('#cep').mask('00000-000');
    
    // Buscar endereço pelo CEP
    $('#cep').blur(function() {
        const cep = $(this).val().replace(/\D/g, '');
        
        if (cep.length === 8) {
            $.getJSON(`https://viacep.com.br/ws/${cep}/json/`, function(data) {
                if (!data.erro) {
                    $('#endereco').val(data.logradouro);
                    $('#cidade').val(data.localidade);
                    $('#estado').val(data.uf);
                }
            });
        }
    });
    
    // Carregar lista de pets do cliente
    carregarPetsDoCliente();
    
    // Envio do formulário
    $('#formEditarCliente').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '../api/clientes/editar_cliente.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Cliente atualizado com sucesso!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        window.location.href = 'clientes.php';
                    });
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message || 'Ocorreu um erro ao atualizar o cliente.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Ocorreu um erro na comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
    
    // Função para carregar os pets do cliente
    function carregarPetsDoCliente() {
        $.ajax({
            url: '../api/pets/listar_pets.php',
            type: 'GET',
            data: { cliente_id: <?php echo $cliente_id; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    exibirPets(response.pets);
                } else {
                    $('#listaPets').html('<div class="alert alert-warning">Nenhum pet encontrado para este cliente.</div>');
                }
            },
            error: function() {
                $('#listaPets').html('<div class="alert alert-danger">Erro ao carregar os pets.</div>');
            }
        });
    }
    
    // Função para exibir os pets na lista
    function exibirPets(pets) {
        if (pets.length === 0) {
            $('#listaPets').html('<div class="alert alert-warning">Nenhum pet cadastrado para este cliente.</div>');
            return;
        }
        
        let html = '<ul class="list-group">';
        
        pets.forEach(function(pet) {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${pet.nome}</strong>
                        <br>
                        <small>${pet.especie} - ${pet.raca}</small>
                    </div>
                    <div>
                        <a href="../cadastrar_pet/cadastrar_pets.php?id=${pet.id}" class="btn btn-sm btn-outline-primary me-1">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-danger btn-excluir-pet" data-id="${pet.id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </li>
            `;
        });
        
        html += '</ul>';
        $('#listaPets').html(html);
        
        // Adicionar evento para excluir pet
        $('.btn-excluir-pet').click(function() {
            const petId = $(this).data('id');
            
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser revertida!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    excluirPet(petId);
                }
            });
        });
    }
    
    // Função para excluir um pet
    function excluirPet(petId) {
        $.ajax({
            url: '../api/pets/excluir_pet.php',
            type: 'POST',
            data: { id: petId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        title: 'Excluído!',
                        text: 'O pet foi excluído com sucesso.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    carregarPetsDoCliente();
                } else {
                    Swal.fire({
                        title: 'Erro!',
                        text: response.message || 'Ocorreu um erro ao excluir o pet.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Erro!',
                    text: 'Ocorreu um erro na comunicação com o servidor.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    }
});
</script>

<?php
// Incluir o rodapé
include_once '../includes/footer.php';
?>
