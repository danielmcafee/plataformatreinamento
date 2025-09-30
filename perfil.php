<?php
session_start();
require_once 'config/database.php';

// Verificar se está logado
if(!isset($_SESSION['usuario_id'])) {
    header('Location: auth/login.php');
    exit;
}

$page_title = 'Meu Perfil - Plataforma de Treinamento';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Processar atualização do perfil
if($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if(empty($nome) || empty($email)) {
        $error = 'Nome e email são obrigatórios.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        // Verificar se email já existe em outro usuário
        $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email, $_SESSION['usuario_id']]);
        
        if($stmt->fetch()) {
            $error = 'Este email já está sendo usado por outro usuário.';
        } else {
            // Atualizar dados básicos
            $query = "UPDATE usuarios SET nome = ?, email = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$nome, $email, $_SESSION['usuario_id']]);
            
            // Atualizar senha se fornecida
            if(!empty($nova_senha)) {
                if(empty($senha_atual)) {
                    $error = 'Para alterar a senha, informe a senha atual.';
                } elseif(strlen($nova_senha) < 6) {
                    $error = 'A nova senha deve ter pelo menos 6 caracteres.';
                } elseif($nova_senha !== $confirmar_senha) {
                    $error = 'As senhas não coincidem.';
                } else {
                    // Verificar senha atual
                    $query = "SELECT senha FROM usuarios WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$_SESSION['usuario_id']]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if(password_verify($senha_atual, $usuario['senha'])) {
                        $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET senha = ? WHERE id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$nova_senha_hash, $_SESSION['usuario_id']]);
                        $message = 'Perfil e senha atualizados com sucesso!';
                    } else {
                        $error = 'Senha atual incorreta.';
                    }
                }
            } else {
                $message = 'Perfil atualizado com sucesso!';
            }
            
            // Atualizar dados na sessão
            $_SESSION['nome'] = $nome;
            $_SESSION['email'] = $email;
        }
    }
}

// Buscar dados do usuário
$usuario = null;
if($db) {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar estatísticas do usuário
$stats = [
    'cursos_inscritos' => 0,
    'aulas_concluidas' => 0,
    'questionarios_respondidos' => 0
];

if($db) {
    // Cursos em que o usuário está inscrito
    $query = "SELECT COUNT(DISTINCT pu.curso_id) as total 
              FROM progresso_usuarios pu
              INNER JOIN cursos c ON pu.curso_id = c.id
              WHERE pu.usuario_id = ? AND c.status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['cursos_inscritos'] = $result['total'];
    
    // Aulas concluídas
    $query = "SELECT COUNT(*) as total FROM progresso_usuarios WHERE usuario_id = ? AND concluida = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['aulas_concluidas'] = $result['total'];
    
    // Questionários respondidos
    $query = "SELECT COUNT(*) as total FROM respostas_questionarios WHERE usuario_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['questionarios_respondidos'] = $result['total'];
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Meu Perfil
                    </h4>
                </div>
                <div class="card-body">
                    <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor, insira seu nome completo.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor, insira um email válido.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Usuário</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($usuario['tipo']); ?>" readonly>
                            <div class="form-text">
                                O tipo de usuário não pode ser alterado.
                            </div>
                        </div>

                        <hr>

                        <h5 class="mb-3">Alterar Senha</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="senha_atual" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control" id="senha_atual" name="senha_atual">
                                    <div class="form-text">
                                        Deixe em branco para não alterar a senha.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="nova_senha" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control" id="nova_senha" name="nova_senha" minlength="6">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirmar_senha" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Estatísticas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Minhas Estatísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <h3><?php echo $stats['cursos_inscritos']; ?></h3>
                                <p>Cursos Inscritos</p>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stats-card">
                                <h3><?php echo $stats['aulas_concluidas']; ?></h3>
                                <p>Aulas Concluídas</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stats-card">
                                <h3><?php echo $stats['questionarios_respondidos']; ?></h3>
                                <p>Questionários Respondidos</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações da Conta -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informações da Conta
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong>Membro desde:</strong><br>
                            <?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?>
                        </li>
                        <li class="mb-2">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?php echo $usuario['ativo'] ? 'success' : 'danger'; ?>">
                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </span>
                        </li>
                        <li>
                            <strong>Último acesso:</strong><br>
                            <?php echo date('d/m/Y H:i'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de confirmação de senha
document.getElementById('confirmar_senha').addEventListener('input', function() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmarSenha = this.value;
    
    if(novaSenha !== confirmarSenha) {
        this.setCustomValidity('As senhas não coincidem');
    } else {
        this.setCustomValidity('');
    }
});

document.getElementById('nova_senha').addEventListener('input', function() {
    const confirmarSenha = document.getElementById('confirmar_senha');
    if(confirmarSenha.value) {
        confirmarSenha.dispatchEvent(new Event('input'));
    }
});
</script>

<?php include 'includes/footer.php'; ?>
