<?php
session_start();

$success_message = '';
$error_message = '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1;
}

require_once('../conecta_db.php');
$conn = conecta_db();

if (!$conn) {
    $error_message = 'Erro fatal: Não foi possível conectar ao banco de dados.';
}

// --- Funções de Validação PHP ---
function isValidCPFPHP($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

function isValidDatePHP($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function isValidAgePHP($dateOfBirth, $minAge = 0, $maxAge = 120) {
    if (!isValidDatePHP($dateOfBirth)) return false;
    try {
        $birthDate = new DateTime($dateOfBirth);
        $today = new DateTime('today');
        if ($birthDate > $today) return false; // Data de nascimento não pode ser no futuro
        $age = $birthDate->diff($today)->y;
        return $age >= $minAge && $age <= $maxAge;
    } catch (Exception $e) {
        return false;
    }
}
// --- Fim das Funções de Validação PHP ---

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cadastrar_usuario') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $cpf_form = trim($_POST['cpf']); // CPF com máscara
    $data_nasc = $_POST['data_nasc'] ?? null;
    $funcao_form = $_POST['funcao'];
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    $cpf_numeros = preg_replace('/[^0-9]/', '', $cpf_form); // CPF apenas números

    $tipo_usuario = 'administrador';
    if ($funcao_form === 'secretario') {
        $tipo_usuario = 'secretario';
    }  elseif ($funcao_form === 'auxiliar_veterinario') { // Ajuste conforme o nome no select
        $tipo_usuario = 'auxiliar_veterinario';
    } elseif ($funcao_form === 'veterinario') {
        $tipo_usuario = 'veterinario';
    }

    // Validações
    if (empty($nome) || empty($email) || empty($cpf_form) || empty($data_nasc) || empty($funcao_form) || empty($senha) || empty($confirmar_senha)) {
        $error_message = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (strlen($nome) < 2 || !preg_match("/^[a-zA-ZÀ-ÿ\s']+$/u", $nome)) {
        $error_message = 'Nome inválido. Deve conter apenas letras, espaços e apóstrofos, e ter pelo menos 2 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Formato de e-mail inválido.';
    } elseif (!isValidCPFPHP($cpf_numeros)) {
        $error_message = 'CPF inválido.';
    } elseif (!isValidDatePHP($data_nasc) || !isValidAgePHP($data_nasc)) {
        $error_message = 'Data de nascimento inválida ou idade fora do permitido (0-120 anos e não futura).';
    } elseif ($senha !== $confirmar_senha) {
        $error_message = 'As senhas não coincidem.';
    } elseif (
        strlen($senha) < 8 ||
        !preg_match('/[A-Z]/', $senha) ||
        !preg_match('/[a-z]/', $senha) ||
        !preg_match('/[0-9]/', $senha) ||
        !preg_match('/[\W_]/', $senha)      // \W é não-palavra (inclui _), _ também é bom ter
    ) {
        $error_message = 'A senha não atende aos requisitos: mínimo 8 caracteres, uma letra maiúscula, uma minúscula, um número e um símbolo.';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error_message = 'Este email já está cadastrado.';
                $stmt->close();
            } else {
                $stmt->close();

                if (!empty($cpf_numeros)) {
                    $stmt = $conn->prepare("SELECT id_usuario FROM Usuarios WHERE cpf = ?");
                    $stmt->bind_param("s", $cpf_numeros);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $error_message = 'Este CPF já está cadastrado.';
                    }
                    $stmt->close();
                }
                
                if (empty($error_message)) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO Usuarios (nome, email, cpf, data_nasc, senha, tipo_usuario) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssss", $nome, $email, $cpf_numeros, $data_nasc, $senha_hash, $tipo_usuario);
                    
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $success_message = 'Usuário cadastrado com sucesso!';
                            $_POST = array();
                        } else {
                            $error_message = 'Nenhum usuário foi cadastrado. Verifique os dados.';
                        }
                    } else {
                         $error_message = 'Erro ao executar cadastro de usuário (Insert).';
                    }
                    $stmt->close();
                }
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                if (strpos(strtolower($e->getMessage()), 'email') !== false) {
                    $error_message = 'Este email já está cadastrado.';
                } elseif (strpos(strtolower($e->getMessage()), 'cpf') !== false) {
                    $error_message = 'Este CPF já está cadastrado.';
                } else {
                    $error_message = 'Erro de duplicidade ao cadastrar usuário: ' . $e->getMessage();
                }
            } else {
                $error_message = 'Erro de banco de dados: ' . $e->getMessage();
            }
        }
    }
}

$usuarios = [];
if ($conn) {
    try {
        $query = "SELECT id_usuario, nome, email, cpf, data_nasc, tipo_usuario FROM Usuarios ORDER BY nome ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $usuarios = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        if(empty($error_message)) {
            $error_message = 'Erro ao carregar usuários: ' . $e->getMessage();
        }
    }
} elseif (empty($error_message)) {
    $error_message = 'Não foi possível carregar usuários: problema de conexão.';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - PetPlus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../includes/global.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { margin-left: 250px; padding: 20px; min-height: 100vh; transition: margin-left 0.3s; }
        .page-header { background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .form-card, .list-card { background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); border: none; }
        .card-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; border-radius: 10px 10px 0 0 !important; }
        .btn-primary { background: linear-gradient(135deg, #007bff, #0056b3); border: none; border-radius: 8px; padding: 12px 24px; font-weight: 500; }
        .btn-primary:hover { background: linear-gradient(135deg, #0056b3, #003d80); }
        .form-control, .form-select { border-radius: 8px; border: 2px solid #e9ecef; padding: 12px; }
        .form-control:focus, .form-select:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25); }
        .table { border-radius: 8px; overflow: hidden; }
        .table thead th { background-color: #f8f9fa; border: none; font-weight: 600; color: #495057; }
        .tipo-usuario-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .tipo-administrador { background-color: #cce5ff; color: #004085; }
        .tipo-veterinario { background-color: #d4edda; color: #155724; }
        .tipo-secretario { background-color: #fff3cd; color: #856404; } /* Estilo para Secretário */
        .tipo-auxiliar_veterinario { background-color: #e2e3e5; color: #383d41; } /* Estilo para Auxiliar Veterinário */
        .no-usuarios { text-align: center; color: #6c757d; padding: 60px 20px; }
        /* .no-usuarios i { font-size: 64px; color: #dee2e6; margin-bottom: 20px; } */ /* Removido pois o ícone foi removido */
        .password-requirements-info { font-size: 0.875em; color: #6c757d; margin-top: .25rem;}
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1 class="mb-2">Gerenciamento de Usuários</h1>
            <p class="text-muted mb-0">Cadastre e gerencie usuários do sistema</p>
        </div>

        <div class="row">
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="card form-card">
                    <div class="card-header">
                        <h5 class="mb-0">Cadastrar Usuário</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="formCadastro" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
                            <input type="hidden" name="action" value="cadastrar_usuario">

                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                <div class="invalid-feedback">Por favor, insira um nome válido (pelo menos 2 caracteres, letras e espaços).</div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="invalid-feedback">Por favor, insira um e-mail válido.</div>
                            </div>

                            <div class="mb-3">
                                <label for="cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" required maxlength="14" placeholder="000.000.000-00" value="<?php echo isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : ''; ?>">
                                <div class="invalid-feedback">Por favor, insira um CPF válido.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="data_nasc" class="form-label">Data de Nascimento *</label>
                                <input type="date" class="form-control" id="data_nasc" name="data_nasc" required value="<?php echo isset($_POST['data_nasc']) ? htmlspecialchars($_POST['data_nasc']) : ''; ?>">
                                <div class="invalid-feedback">Por favor, insira uma data de nascimento válida (idade entre 0 e 120 anos).</div>
                            </div>

                            <div class="mb-3">
                                <label for="funcao" class="form-label">Tipo de Usuário (Função Interna) *</label>
                                <select class="form-select" id="funcao" name="funcao" required>
                                    <option value="">Selecione um tipo/função</option>
                                    <option value="secretario" <?php echo (isset($_POST['funcao']) && $_POST['funcao'] == 'secretario') ? 'selected' : ''; ?>>Secretário(a)</option>
                                    <option value="auxiliar_veterinario" <?php echo (isset($_POST['funcao']) && $_POST['funcao'] == 'auxiliar_veterinario') ? 'selected' : ''; ?>>Auxiliar Veterinário</option>
                                    <option value="veterinario" <?php echo (isset($_POST['funcao']) && $_POST['funcao'] == 'veterinario') ? 'selected' : ''; ?>>Veterinário(a)</option>
                                </select>
                                <div class="invalid-feedback">Por favor, selecione um tipo de usuário.</div>
                            </div>

                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <input type="password" class="form-control" id="senha" name="senha" required minlength="8">
                                <div class="password-requirements-info">
                                    Mínimo 8 caracteres, incluindo: letra maiúscula, minúscula, número e símbolo.
                                </div>
                                <div class="invalid-feedback" id="senha-feedback">A senha não atende aos requisitos.</div>
                            </div>

                            <div class="mb-4">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="8">
                                <div class="invalid-feedback">As senhas não coincidem.</div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                Cadastrar Usuário
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card list-card">
                    <div class="card-header">
                        <h5 class="mb-0">Usuários Cadastrados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$conn && !empty($error_message) && strpos($error_message, 'Erro fatal') !== false): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php elseif (empty($usuarios) && $conn && empty($error_message)): ?>
                            <div class="no-usuarios">
                                <h5>Nenhum usuário cadastrado</h5>
                                <p class="text-muted">Cadastre o primeiro usuário.</p>
                            </div>
                        <?php elseif (!empty($usuarios)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nome / CPF</th>
                                            <th>Email</th>
                                            <th>Data Nasc.</th>
                                            <th>Tipo de Usuário</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['cpf'] ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $usuario['cpf']) : 'CPF não informado'); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                                <td><?php echo htmlspecialchars($usuario['data_nasc'] ? date('d/m/Y', strtotime($usuario['data_nasc'])) : 'N/A'); ?></td>
                                                <td>
                                                    <span class="tipo-usuario-badge tipo-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $usuario['tipo_usuario']))); ?>">
                                                        <?php echo ucfirst(htmlspecialchars(str_replace('_', ' ', $usuario['tipo_usuario']))); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($conn && !empty($error_message)): ?>
                            <div class="alert alert-warning"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (!empty($success_message)): ?>
        Swal.fire({ icon: 'success', title: 'Sucesso!', text: '<?php echo addslashes($success_message); ?>', confirmButtonColor: '#007bff' });
        <?php $success_message = ''; ?>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        Swal.fire({ icon: 'error', title: 'Erro!', text: '<?php echo addslashes($error_message); ?>', confirmButtonColor: '#dc3545' });
        <?php $error_message = ''; ?>
        <?php endif; ?>

        const cpfInput = document.getElementById('cpf');
        if (cpfInput) {
            cpfInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);
                
                let formattedValue = '';
                if (value.length > 9) formattedValue = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, '$1.$2.$3-$4');
                else if (value.length > 6) formattedValue = value.replace(/^(\d{3})(\d{3})(\d{1,3})$/, '$1.$2.$3');
                else if (value.length > 3) formattedValue = value.replace(/^(\d{3})(\d{1,3})$/, '$1.$2');
                else formattedValue = value;
                e.target.value = formattedValue;
            });
        }

        const formCadastro = document.getElementById('formCadastro');
        const senhaInput = document.getElementById('senha');
        const confirmarSenhaInput = document.getElementById('confirmar_senha');
        const nomeInput = document.getElementById('nome');
        const emailInput = document.getElementById('email');
        const dataNascInput = document.getElementById('data_nasc');

        function validatePasswordMatch() {
            if (senhaInput.value !== confirmarSenhaInput.value && confirmarSenhaInput.value !== '') {
                confirmarSenhaInput.classList.add('is-invalid');
                confirmarSenhaInput.classList.remove('is-valid');
            } else if (confirmarSenhaInput.value !== '') {
                confirmarSenhaInput.classList.remove('is-invalid');
                confirmarSenhaInput.classList.add('is-valid');
            } else {
                 confirmarSenhaInput.classList.remove('is-invalid', 'is-valid');
            }
        }
        
        function validatePasswordStrength() {
            const senha = senhaInput.value;
            const feedback = document.getElementById('senha-feedback');
            const hasUpperCase = /[A-Z]/.test(senha);
            const hasLowerCase = /[a-z]/.test(senha);
            const hasNumbers = /\d/.test(senha);
            const hasSpecialChar = /[\W_]/.test(senha);
            const hasMinLength = senha.length >= 8;

            if (senha.length > 0 && (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar || !hasMinLength)) {
                senhaInput.classList.add('is-invalid');
                senhaInput.classList.remove('is-valid');
                feedback.style.display = 'block'; // Mostra feedback de erro customizado
            } else if (senha.length > 0) {
                senhaInput.classList.remove('is-invalid');
                senhaInput.classList.add('is-valid');
                feedback.style.display = 'none';
            } else {
                 senhaInput.classList.remove('is-invalid', 'is-valid');
                 feedback.style.display = 'none';
            }
        }

        if (senhaInput) {
            senhaInput.addEventListener('input', validatePasswordStrength);
            senhaInput.addEventListener('input', validatePasswordMatch); // Validar match ao mudar senha principal também
        }
        if (confirmarSenhaInput) confirmarSenhaInput.addEventListener('input', validatePasswordMatch);


        // Validação Bootstrap no submit
        if (formCadastro) {
            formCadastro.addEventListener('submit', function(event) {
                // Força validação de força da senha e match no submit também
                validatePasswordStrength(); 
                validatePasswordMatch();

                if (!formCadastro.checkValidity() || senhaInput.classList.contains('is-invalid') || confirmarSenhaInput.classList.contains('is-invalid')) {
                    event.preventDefault();
                    event.stopPropagation();
                    // SweetAlert para erro geral já é tratado pelo PHP se chegar ao backend
                    // O foco aqui é a validação do browser e feedback visual imediato
                }
                formCadastro.classList.add('was-validated'); // Mostra feedback do Bootstrap
            }, false);
        }
        
        // Adicionar validações on-blur/on-input para feedback imediato (opcional, mas bom para UX)
        [nomeInput, emailInput, dataNascInput, cpfInput].forEach(input => {
            if(input) {
                input.addEventListener('blur', () => {
                    if (!input.checkValidity()) {
                        input.classList.add('is-invalid');
                        input.classList.remove('is-valid');
                    } else {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    }
                });
                 input.addEventListener('input', () => { // Remove o 'is-invalid' ao começar a digitar
                    if (input.classList.contains('is-invalid')) {
                        input.classList.remove('is-invalid');
                    }
                });
            }
        });
    });
    </script>
</body>
</html>