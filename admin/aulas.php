<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado e é gestor
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'gestor') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Gerenciar Aulas - Painel Administrativo';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? 'list';
$aula_id = $_GET['id'] ?? 0;
$curso_id = $_GET['curso_id'] ?? 0;
$message = '';
$error = '';

// Processar formulário
if($_POST) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $tipo = $_POST['tipo'] ?? 'video';
    $conteudo = trim($_POST['conteudo'] ?? '');
    $duracao = (int)($_POST['duracao'] ?? 0);
    $ordem = (int)($_POST['ordem'] ?? 1);
    $curso_id = (int)($_POST['curso_id'] ?? $curso_id);
    
    if(empty($titulo) || empty($curso_id)) {
        $error = 'Título e curso são obrigatórios.';
    } elseif($tipo == 'video' && empty($conteudo)) {
        $error = 'URL do YouTube é obrigatória para videoaulas.';
    } else {
        if($action == 'create') {
            $query = "INSERT INTO aulas (curso_id, titulo, descricao, tipo, conteudo, duracao, ordem) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            if($stmt->execute([$curso_id, $titulo, $descricao, $tipo, $conteudo, $duracao, $ordem])) {
                $message = 'Aula criada com sucesso!';
                $action = 'list';
            } else {
                $error = 'Erro ao criar aula.';
            }
        } elseif($action == 'edit' && $aula_id) {
            $query = "UPDATE aulas SET titulo = ?, descricao = ?, tipo = ?, conteudo = ?, duracao = ?, ordem = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            if($stmt->execute([$titulo, $descricao, $tipo, $conteudo, $duracao, $ordem, $aula_id])) {
                $message = 'Aula atualizada com sucesso!';
                $action = 'list';
            } else {
                $error = 'Erro ao atualizar aula.';
            }
        }
    }
}

// Buscar aula para edição
$aula = null;
if($action == 'edit' && $aula_id) {
    $query = "SELECT a.*, c.titulo as curso_titulo FROM aulas a 
              INNER JOIN cursos c ON a.curso_id = c.id 
              WHERE a.id = ? AND c.gestor_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$aula_id, $_SESSION['usuario_id']]);
    $aula = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$aula) {
        $error = 'Aula não encontrada.';
        $action = 'list';
    } else {
        $curso_id = $aula['curso_id'];
    }
}

// Buscar cursos do gestor
$cursos = [];
if($db) {
    $query = "SELECT id, titulo FROM cursos WHERE gestor_id = ? AND status = 'ativo' ORDER BY titulo";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Listar aulas
$aulas = [];
if($action == 'list' && $db) {
    $where_clause = "c.gestor_id = ?";
    $params = [$_SESSION['usuario_id']];
    
    if($curso_id) {
        $where_clause .= " AND a.curso_id = ?";
        $params[] = $curso_id;
    }
    
    $query = "SELECT a.*, c.titulo as curso_titulo 
              FROM aulas a 
              INNER JOIN cursos c ON a.curso_id = c.id 
              WHERE $where_clause AND a.ativo = 1
              ORDER BY c.titulo, a.ordem";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <a class="nav-link active" href="aulas.php">
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
                    echo $action == 'create' ? 'Criar Aula' : 
                         ($action == 'edit' ? 'Editar Aula' : 'Gerenciar Aulas'); 
                    ?>
                </h1>
                <?php if($action == 'list'): ?>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="aulas.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nova Aula
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
            <!-- Filtro por Curso -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <form method="GET" class="d-flex">
                        <select name="curso_id" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">Todos os cursos</option>
                            <?php foreach($cursos as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>" 
                                    <?php echo $curso_id == $curso['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($curso['titulo']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if($curso_id): ?>
                        <a href="aulas.php" class="btn btn-outline-secondary">Limpar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Lista de Aulas -->
            <div class="card shadow">
                <div class="card-body">
                    <?php if(empty($aulas)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-play-circle fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Nenhuma aula cadastrada</h4>
                        <p class="text-muted">Comece criando sua primeira aula.</p>
                        <a href="aulas.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Criar Primeira Aula
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ordem</th>
                                    <th>Título</th>
                                    <th>Curso</th>
                                    <th>Tipo</th>
                                    <th>Duração</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($aulas as $aula): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $aula['ordem']; ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($aula['titulo']); ?></strong>
                                        <?php if($aula['descricao']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($aula['descricao'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($aula['curso_titulo']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $aula['tipo'] == 'video' ? 'danger' : ($aula['tipo'] == 'documento' ? 'info' : 'warning'); ?>">
                                            <i class="fas fa-<?php echo $aula['tipo'] == 'video' ? 'play' : ($aula['tipo'] == 'documento' ? 'file' : 'question-circle'); ?> me-1"></i>
                                            <?php echo ucfirst($aula['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $aula['duracao'] > 0 ? $aula['duracao'] . ' min' : '-'; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="aulas.php?action=edit&id=<?php echo $aula['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="../curso.php?id=<?php echo $aula['curso_id']; ?>" 
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
            <!-- Formulário de Aula -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-<?php echo $action == 'create' ? 'plus' : 'edit'; ?> me-2"></i>
                                <?php echo $action == 'create' ? 'Criar Nova Aula' : 'Editar Aula'; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                                
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="titulo" class="form-label">Título da Aula *</label>
                                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                                   value="<?php echo htmlspecialchars($aula['titulo'] ?? ''); ?>" required>
                                            <div class="invalid-feedback">
                                                Por favor, insira o título da aula.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="ordem" class="form-label">Ordem</label>
                                            <input type="number" class="form-control" id="ordem" name="ordem" 
                                                   value="<?php echo $aula['ordem'] ?? 1; ?>" min="1">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="descricao" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($aula['descricao'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipo" class="form-label">Tipo de Aula *</label>
                                            <select class="form-control" id="tipo" name="tipo" required onchange="toggleConteudo()">
                                                <option value="video" <?php echo ($aula['tipo'] ?? 'video') == 'video' ? 'selected' : ''; ?>>
                                                    Videoaula (YouTube)
                                                </option>
                                                <option value="documento" <?php echo ($aula['tipo'] ?? '') == 'documento' ? 'selected' : ''; ?>>
                                                    Documento
                                                </option>
                                                <option value="questionario" <?php echo ($aula['tipo'] ?? '') == 'questionario' ? 'selected' : ''; ?>>
                                                    Questionário
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duracao" class="form-label">Duração (minutos)</label>
                                            <input type="number" class="form-control" id="duracao" name="duracao" 
                                                   value="<?php echo $aula['duracao'] ?? 0; ?>" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3" id="conteudoContainer">
                                    <label for="conteudo" class="form-label" id="conteudoLabel">URL do YouTube *</label>
                                    <input type="text" class="form-control" id="conteudo" name="conteudo" 
                                           value="<?php echo htmlspecialchars($aula['conteudo'] ?? ''); ?>" 
                                           placeholder="https://www.youtube.com/watch?v=...">
                                    <div class="form-text" id="conteudoHelp">
                                        Cole aqui a URL completa do vídeo do YouTube
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="aulas.php<?php echo $curso_id ? '?curso_id=' . $curso_id : ''; ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Voltar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action == 'create' ? 'Criar Aula' : 'Salvar Alterações'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="mb-0">Dicas</h6>
                        </div>
                        <div class="card-body">
                            <div id="dicasVideo" class="dicas-tipo">
                                <h6>Videoaulas do YouTube:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success me-2"></i> Use URLs completas do YouTube</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Vídeos devem ser públicos</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Defina duração estimada</li>
                                </ul>
                            </div>
                            
                            <div id="dicasDocumento" class="dicas-tipo" style="display: none;">
                                <h6>Documentos:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success me-2"></i> Upload será implementado</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Formatos: PDF, DOC, PPT</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Tamanho máximo: 10MB</li>
                                </ul>
                            </div>
                            
                            <div id="dicasQuestionario" class="dicas-tipo" style="display: none;">
                                <h6>Questionários:</h6>
                                <ul class="list-unstyled small">
                                    <li><i class="fas fa-check text-success me-2"></i> Múltipla escolha</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Verdadeiro/Falso</li>
                                    <li><i class="fas fa-check text-success me-2"></i> Questões abertas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
function toggleConteudo() {
    const tipo = document.getElementById('tipo').value;
    const label = document.getElementById('conteudoLabel');
    const input = document.getElementById('conteudo');
    const help = document.getElementById('conteudoHelp');
    
    // Mostrar/esconder dicas
    document.querySelectorAll('.dicas-tipo').forEach(div => div.style.display = 'none');
    document.getElementById('dicas' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'block';
    
    if(tipo === 'video') {
        label.textContent = 'URL do YouTube *';
        input.placeholder = 'https://www.youtube.com/watch?v=...';
        help.textContent = 'Cole aqui a URL completa do vídeo do YouTube';
        input.required = true;
    } else if(tipo === 'documento') {
        label.textContent = 'Caminho do Documento';
        input.placeholder = '/uploads/documento.pdf';
        help.textContent = 'Caminho do arquivo no servidor (upload será implementado)';
        input.required = false;
    } else if(tipo === 'questionario') {
        label.textContent = 'Configuração do Questionário';
        input.placeholder = 'JSON com perguntas e respostas';
        help.textContent = 'Configuração JSON do questionário (será implementado)';
        input.required = false;
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    toggleConteudo();
});
</script>

<?php include '../includes/footer.php'; ?>
