<?php
session_start();
require_once '../config/database.php';

// Se já estiver logado, redirecionar
if(isset($_SESSION['usuario_id'])) {
    header('Location: ../index.php');
    exit;
}

$error = '';

if($_POST) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if(empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        if($db) {
            $query = "SELECT id, nome, email, senha, tipo, ativo FROM usuarios WHERE email = ? AND ativo = 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['tipo'] = $usuario['tipo'];
                
                // Redirecionar baseado no tipo de usuário
                if($usuario['tipo'] == 'gestor') {
                    header('Location: ../admin/');
                } else {
                    header('Location: ../index.php');
                }
                exit;
            } else {
                $error = 'Email ou senha incorretos.';
            }
        } else {
            $error = 'Erro de conexão com o banco de dados.';
        }
    }
}

$page_title = 'Login - Plataforma de Treinamento';
include '../includes/header.php';
?>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card">
                    <h2><i class="fas fa-sign-in-alt me-2"></i>Entrar</h2>
                    
                    <?php if($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            <div class="invalid-feedback">
                                Por favor, insira um email válido.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <div class="invalid-feedback">
                                Por favor, insira sua senha.
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Entrar
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Não tem uma conta? 
                            <a href="cadastro.php" class="text-decoration-none">Cadastre-se aqui</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
