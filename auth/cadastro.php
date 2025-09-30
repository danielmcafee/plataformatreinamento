<?php
session_start();
require_once '../config/database.php';

// Se já estiver logado, redirecionar
if(isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

if($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $tipo = $_POST['tipo'] ?? 'colaborador';
    
    // Validações
    if(empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } elseif(strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if($db) {
            // Verificar se email já existe
            $query = "SELECT id FROM usuarios WHERE email = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            
            if($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                // Inserir novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $query = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if($stmt->execute([$nome, $email, $senha_hash, $tipo])) {
                    $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                } else {
                    $error = 'Erro ao cadastrar usuário. Tente novamente.';
                }
            }
        } else {
            $error = 'Erro de conexão com o banco de dados.';
        }
    }
}

$page_title = 'Cadastro - Plataforma de Treinamento';
include '../includes/header.php';
?>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <h2><i class="fas fa-user-plus me-2"></i>Cadastrar</h2>
                    
                    <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, insira seu nome completo.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, insira um email válido.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Usuário</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="colaborador" <?php echo ($_POST['tipo'] ?? '') == 'colaborador' ? 'selected' : ''; ?>>
                                    Colaborador
                                </option>
                                <option value="gestor" <?php echo ($_POST['tipo'] ?? '') == 'gestor' ? 'selected' : ''; ?>>
                                    Gestor
                                </option>
                            </select>
                            <div class="form-text">
                                <small>Gestores podem criar e gerenciar cursos. Colaboradores podem participar dos cursos.</small>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" 
                                   minlength="6" required>
                            <div class="invalid-feedback">
                                A senha deve ter pelo menos 6 caracteres.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                                   minlength="6" required>
                            <div class="invalid-feedback">
                                As senhas devem coincidir.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Cadastrar
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Já tem uma conta? 
                            <a href="login.php" class="text-decoration-none">Faça login aqui</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const senha = document.getElementById('senha').value;
    const confirmarSenha = this.value;
    
    if(senha !== confirmarSenha) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
