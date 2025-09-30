<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado e é gestor
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'gestor') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Gerenciar Usuários - Painel Administrativo';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$usuario_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Processar formulário
if($_POST) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo = $_POST['tipo'] ?? 'colaborador';
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $senha = $_POST['senha'] ?? '';
    
    if(empty($nome) || empty($email)) {
        $error = 'Nome e email são obrigatórios.';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um email válido.';
    } else {
        if($action == 'create') {
            if(empty($senha)) {
                $error = 'Senha é obrigatória para novos usuários.';
            } elseif(strlen($senha) < 6) {
                $error = 'A senha deve ter pelo menos 6 caracteres.';
            } else {
                // Verificar se email já existe
                $query = "SELECT id FROM usuarios WHERE email = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$email]);
                
                if($stmt->fetch()) {
                    $error = 'Este email já está sendo usado.';
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $query = "INSERT INTO usuarios (nome, email, senha, tipo, ativo) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    if($stmt->execute([$nome, $email, $senha_hash, $tipo, $ativo])) {
                        $message = 'Usuário criado com sucesso!';
                        $action = 'list';
                    } else {
                        $error = 'Erro ao criar usuário.';
                    }
                }
            }
        } elseif($action == 'edit' && $usuario_id) {
            // Verificar se email já existe em outro usuário
            $query = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$email, $usuario_id]);
            
            if($stmt->fetch()) {
                $error = 'Este email já está sendo usado por outro usuário.';
            } else {
                $query = "UPDATE usuarios SET nome = ?, email = ?, tipo = ?, ativo = ? WHERE id = ?";
                $params = [$nome, $email, $tipo, $ativo, $usuario_id];
                
                // Atualizar senha se fornecida
                if(!empty($senha)) {
                    if(strlen($senha) < 6) {
                        $error = 'A senha deve ter pelo menos 6 caracteres.';
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $query = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, tipo = ?, ativo = ? WHERE id = ?";
                        $params = [$nome, $email, $senha_hash, $tipo, $ativo, $usuario_id];
                    }
                }
                
                if(empty($error)) {
                    $stmt = $db->prepare($query);
                    if($stmt->execute($params)) {
                        $message = 'Usuário atualizado com sucesso!';
                        $action = 'list';
                    } else {
                        $error = 'Erro ao atualizar usuário.';
                    }
                }
            }
        }
    }
}

// Buscar usuário para edição
$usuario = null;
if($action == 'edit' && $usuario_id) {
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$usuario) {
        $error = 'Usuário não encontrado.';
        $action = 'list';
    }
}

// Listar usuários
$usuarios = [];
if($action == 'list' && $db) {
    $query = "SELECT u.*, 
              (SELECT COUNT(*) FROM cursos c WHERE c.gestor_id = u.id) as total_cursos,
              (SELECT COUNT(*) FROM progresso_usuarios pu WHERE pu.usuario_id = u.id) as total_progresso
              FROM usuarios u 
              ORDER BY u.data_cadastro DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cursos.php">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Cursos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="aulas.php">
                            <i class="fas fa-play-circle me-2"></i>
                            Aulas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>
                            Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Relatórios
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <?php 
                    echo $action == 'create' ? 'Criar Usuário' : 
                         ($action == 'edit' ? 'Editar Usuário' : 'Gerenciar Usuários'); 
                    ?>
                </h1>
                <?php if($action == 'list'): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="usuarios.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Novo Usuário
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>

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

            <?php if($action == 'list'): ?>
            <!-- Lista de Usuários -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if(empty($usuarios)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhum usuário cadastrado</h4>
                        <p class="text-muted">Comece criando o primeiro usuário.</p>
                        <a href="usuarios.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Criar Primeiro Usuário
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Cadastro</th>
                                    <th>Estatísticas</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['nome']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['tipo'] == 'gestor' ? 'primary' : 'info'; ?>">
                                            <i class="fas fa-<?php echo $user['tipo'] == 'gestor' ? 'crown' : 'user'; ?> me-1"></i>
                                            <?php echo ucfirst($user['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['ativo'] ? 'success' : 'danger'; ?>">
                                            <?php echo $user['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['data_cadastro'])); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if($user['tipo'] == 'gestor'): ?>
                                                <?php echo $user['total_cursos']; ?> cursos
                                            <?php else: ?>
                                                <?php echo $user['total_progresso']; ?> aulas
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="usuarios.php?action=edit&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($user['id'] != $_SESSION['usuario_id']): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-delete" 
                                                    data-id="<?php echo $user['id']; ?>" 
                                                    data-nome="<?php echo htmlspecialchars($user['nome']); ?>" 
                                                    title="Desativar">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif($action == 'create' || $action == 'edit'): ?>
            <!-- Formulário de Usuário -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-<?php echo $action == 'create' ? 'plus' : 'edit'; ?> me-2"></i>
                                <?php echo $action == 'create' ? 'Criar Novo Usuário' : 'Editar Usuário'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome Completo *</label>
                                            <input type="text" class="form-control" id="nome" name="nome" 
                                                   value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor, insira o nome completo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor, insira um email válido.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipo" class="form-label">Tipo de Usuário *</label>
                                            <select class="form-control" id="tipo" name="tipo" required>
                                                <option value="colaborador" <?php echo ($usuario['tipo'] ?? 'colaborador') == 'colaborador' ? 'selected' : ''; ?>>
                                                    Colaborador
                                                </option>
                                                <option value="gestor" <?php echo ($usuario['tipo'] ?? '') == 'gestor' ? 'selected' : ''; ?>>
                                                    Gestor
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="ativo" class="form-label">Status</label>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                                       <?php echo ($usuario['ativo'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ativo">
                                                    Usuário ativo
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="senha" class="form-label">
                                        Senha <?php echo $action == 'create' ? '*' : '(deixe em branco para não alterar)'; ?>
                                    </label>
                                    <input type="password" class="form-control" id="senha" name="senha" 
                                           <?php echo $action == 'create' ? 'required minlength="6"' : 'minlength="6"'; ?>>
                                    <div class="invalid-feedback">
                                        A senha deve ter pelo menos 6 caracteres.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="usuarios.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action == 'create' ? 'Criar Usuário' : 'Salvar Alterações'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="mb-0">Informações</h6>
                        </div>
                        <div class="card-body">
                            <h6>Tipos de Usuário:</h6>
                            <ul class="list-unstyled">
                                <li><strong>Gestor:</strong> Pode criar e gerenciar cursos</li>
                                <li><strong>Colaborador:</strong> Pode participar dos cursos</li>
                            </ul>
                            
                            <h6 class="mt-3">Segurança:</h6>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-shield-alt text-success me-2"></i> Senhas são criptografadas</li>
                                <li><i class="fas fa-lock text-success me-2"></i> Acesso controlado por tipo</li>
                                <li><i class="fas fa-user-check text-success me-2"></i> Status ativo/inativo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Confirmação de exclusão
document.querySelectorAll('.btn-delete').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const nome = this.dataset.nome;
        
        if(confirm(`Tem certeza que deseja desativar o usuário "${nome}"?`)) {
            // Aqui você pode implementar a desativação via AJAX
            alert('Funcionalidade de desativação será implementada.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
