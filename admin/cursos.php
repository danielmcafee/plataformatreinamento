<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado e é gestor
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'gestor') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Gerenciar Cursos - Painel Administrativo';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$curso_id = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Processar formulário
if($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $status = $_POST['status'] ?? 'ativo';
    
    if(empty($titulo)) {
        $error = 'O título do curso é obrigatório.';
    } else {
        if($action == 'create') {
            $query = "INSERT INTO cursos (titulo, descricao, status, gestor_id) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            if($stmt->execute([$titulo, $descricao, $status, $_SESSION['usuario_id']])) {
                $message = 'Curso criado com sucesso!';
                $action = 'list';
            } else {
                $error = 'Erro ao criar curso.';
            }
        } elseif($action == 'edit' && $curso_id) {
            $query = "UPDATE cursos SET titulo = ?, descricao = ?, status = ? WHERE id = ? AND gestor_id = ?";
            $stmt = $db->prepare($query);
            if($stmt->execute([$titulo, $descricao, $status, $curso_id, $_SESSION['usuario_id']])) {
                $message = 'Curso atualizado com sucesso!';
                $action = 'list';
            } else {
                $error = 'Erro ao atualizar curso.';
            }
        }
    }
}

// Buscar curso para edição
$curso = null;
if($action == 'edit' && $curso_id) {
    $query = "SELECT * FROM cursos WHERE id = ? AND gestor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$curso_id, $_SESSION['usuario_id']]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$curso) {
        $error = 'Curso não encontrado.';
        $action = 'list';
    }
}

// Listar cursos
$cursos = [];
if($action == 'list' && $db) {
    $query = "SELECT c.*, u.nome as gestor_nome, 
              COUNT(a.id) as total_aulas
              FROM cursos c 
              LEFT JOIN aulas a ON c.id = a.curso_id AND a.ativo = 1
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              WHERE c.gestor_id = ?
              GROUP BY c.id
              ORDER BY c.data_criacao DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <a class="nav-link active" href="cursos.php">
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
                        <a class="nav-link" href="usuarios.php">
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
                    echo $action == 'create' ? 'Criar Curso' : 
                         ($action == 'edit' ? 'Editar Curso' : 'Gerenciar Cursos'); 
                    ?>
                </h1>
                <?php if($action == 'list'): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="cursos.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Novo Curso
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
            <!-- Lista de Cursos -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if(empty($cursos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhum curso cadastrado</h4>
                        <p class="text-muted">Comece criando seu primeiro curso.</p>
                        <a href="cursos.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Criar Primeiro Curso
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Status</th>
                                    <th>Aulas</th>
                                    <th>Data Criação</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cursos as $curso): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong>
                                        <?php if($curso['descricao']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($curso['descricao'], 0, 100)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $curso['status'] == 'ativo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($curso['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $curso['total_aulas']; ?> aulas</span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($curso['data_criacao'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="cursos.php?action=edit&id=<?php echo $curso['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="aulas.php?curso_id=<?php echo $curso['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="Gerenciar Aulas">
                                                <i class="fas fa-play-circle"></i>
                                            </a>
                                            <a href="../curso.php?id=<?php echo $curso['id']; ?>" 
                                               class="btn btn-sm btn-outline-success" title="Visualizar" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
            <!-- Formulário de Curso -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-<?php echo $action == 'create' ? 'plus' : 'edit'; ?> me-2"></i>
                                <?php echo $action == 'create' ? 'Criar Novo Curso' : 'Editar Curso'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="titulo" class="form-label">Título do Curso *</label>
                                    <input type="text" class="form-control" id="titulo" name="titulo" 
                                           value="<?php echo htmlspecialchars($curso['titulo'] ?? ''); ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor, insira o título do curso.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($curso['descricao'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="ativo" <?php echo ($curso['status'] ?? 'ativo') == 'ativo' ? 'selected' : ''; ?>>
                                            Ativo
                                        </option>
                                        <option value="inativo" <?php echo ($curso['status'] ?? '') == 'inativo' ? 'selected' : ''; ?>>
                                            Inativo
                                        </option>
                                    </select>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="cursos.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action == 'create' ? 'Criar Curso' : 'Salvar Alterações'; ?>
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
                            <p><strong>Dicas para criar um bom curso:</strong></p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Use um título claro e objetivo</li>
                                <li><i class="fas fa-check text-success me-2"></i> Descreva o que o aluno aprenderá</li>
                                <li><i class="fas fa-check text-success me-2"></i> Organize as aulas em sequência lógica</li>
                                <li><i class="fas fa-check text-success me-2"></i> Inclua materiais de apoio</li>
                                <li><i class="fas fa-check text-success me-2"></i> Adicione questionários de avaliação</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
