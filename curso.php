<?php
session_start();
require_once 'config/database.php';

$curso_id = $_GET['id'] ?? 0;

if(!$curso_id) {
    header('Location: index.php');
    exit;
}

$page_title = 'Curso - Plataforma de Treinamento';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Buscar curso
$curso = null;
if($db) {
    $query = "SELECT c.*, u.nome as gestor_nome 
              FROM cursos c 
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              WHERE c.id = ? AND c.status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute([$curso_id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
}

if(!$curso) {
    echo '<div class="container py-5">
            <div class="alert alert-danger text-center">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h4>Curso não encontrado</h4>
                <p>O curso solicitado não existe ou não está disponível.</p>
                <a href="index.php" class="btn btn-primary">Voltar ao Início</a>
            </div>
          </div>';
    include 'includes/footer.php';
    exit;
}

// Buscar aulas do curso
$aulas = [];
if($db) {
    $query = "SELECT a.*, 
              (SELECT COUNT(*) FROM progresso_usuarios pu 
               WHERE pu.aula_id = a.id AND pu.usuario_id = ? AND pu.concluida = 1) as concluida
              FROM aulas a 
              WHERE a.curso_id = ? AND a.ativo = 1
              ORDER BY a.ordem ASC";
    
    $stmt = $db->prepare($query);
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt->execute([$usuario_id, $curso_id]);
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calcular progresso
$total_aulas = count($aulas);
$aulas_concluidas = 0;
foreach($aulas as $aula) {
    if($aula['concluida']) {
        $aulas_concluidas++;
    }
}
$progresso = $total_aulas > 0 ? round(($aulas_concluidas / $total_aulas) * 100) : 0;
?>

<div class="container py-4">
    <!-- Cabeçalho do Curso -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                            <div class="d-flex align-items-center text-muted">
                                <i class="fas fa-user me-2"></i>
                                <span>Instrutor: <?php echo htmlspecialchars($curso['gestor_nome']); ?></span>
                                <i class="fas fa-play-circle ms-3 me-2"></i>
                                <span><?php echo $total_aulas; ?> aulas</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <?php if(isset($_SESSION['usuario_id'])): ?>
                            <div class="mb-3">
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progresso; ?>%" 
                                         aria-valuenow="<?php echo $progresso; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted"><?php echo $progresso; ?>% concluído</small>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!isset($_SESSION['usuario_id'])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <a href="auth/login.php" class="alert-link">Faça login</a> para acessar o conteúdo do curso.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Aulas -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Conteúdo do Curso
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($aulas)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-play-circle fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhuma aula disponível</h5>
                        <p class="text-muted">O instrutor ainda não adicionou aulas a este curso.</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($aulas as $index => $aula): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-md-1 text-center">
                                    <span class="badge bg-primary rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                        <?php echo $index + 1; ?>
                                    </span>
                                </div>
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($aula['titulo']); ?>
                                        <?php if($aula['concluida']): ?>
                                        <i class="fas fa-check-circle text-success ms-2"></i>
                                        <?php endif; ?>
                                    </h6>
                                    <?php if($aula['descricao']): ?>
                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($aula['descricao']); ?></p>
                                    <?php endif; ?>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-<?php echo $aula['tipo'] == 'video' ? 'danger' : ($aula['tipo'] == 'documento' ? 'info' : 'warning'); ?> me-2">
                                            <i class="fas fa-<?php echo $aula['tipo'] == 'video' ? 'play' : ($aula['tipo'] == 'documento' ? 'file' : 'question-circle'); ?> me-1"></i>
                                            <?php echo ucfirst($aula['tipo']); ?>
                                        </span>
                                        <?php if($aula['duracao'] > 0): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $aula['duracao']; ?> min
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="abrirAula(<?php echo $aula['id']; ?>, '<?php echo $aula['tipo']; ?>')">
                                        <i class="fas fa-<?php echo $aula['concluida'] ? 'redo' : 'play'; ?> me-1"></i>
                                        <?php echo $aula['concluida'] ? 'Revisar' : 'Iniciar'; ?>
                                    </button>
                                    <?php else: ?>
                                    <a href="auth/login.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-lock me-1"></i>
                                        Fazer Login
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Aulas -->
<div class="modal fade" id="aulaModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="aulaModalTitle">Aula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="aulaModalBody">
                <!-- Conteúdo da aula será carregado aqui -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" id="btnConcluirAula" style="display: none;">
                    <i class="fas fa-check me-2"></i>Marcar como Concluída
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let aulaAtual = null;

function abrirAula(aulaId, tipo) {
    aulaAtual = aulaId;
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('aulaModal'));
    modal.show();
    
    // Carregar conteúdo da aula
    carregarAula(aulaId, tipo);
}

function carregarAula(aulaId, tipo) {
    const modalBody = document.getElementById('aulaModalBody');
    const modalTitle = document.getElementById('aulaModalTitle');
    const btnConcluir = document.getElementById('btnConcluirAula');
    
    // Mostrar loading
    modalBody.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div><p class="mt-3">Carregando aula...</p></div>';
    
    // Simular carregamento (substituir por AJAX real)
    setTimeout(() => {
        if(tipo === 'video') {
            modalTitle.textContent = 'Videoaula';
            modalBody.innerHTML = `
                <div class="video-container">
                    <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" 
                            frameborder="0" allowfullscreen></iframe>
                </div>
                <div class="mt-3">
                    <h6>Instruções:</h6>
                    <ul>
                        <li>Assista ao vídeo completo</li>
                        <li>Preste atenção aos pontos principais</li>
                        <li>Anote dúvidas para discussão posterior</li>
                    </ul>
                </div>
            `;
            btnConcluir.style.display = 'inline-block';
        } else if(tipo === 'documento') {
            modalTitle.textContent = 'Documento';
            modalBody.innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-file-pdf me-2"></i>
                    <strong>Documento:</strong> Material de apoio da aula
                </div>
                <div class="text-center">
                    <i class="fas fa-file-pdf fa-5x text-danger mb-3"></i>
                    <p>Clique no botão abaixo para baixar o documento.</p>
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Baixar Documento
                    </a>
                </div>
            `;
            btnConcluir.style.display = 'inline-block';
        } else if(tipo === 'questionario') {
            modalTitle.textContent = 'Questionário';
            modalBody.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-question-circle me-2"></i>
                    <strong>Questionário:</strong> Responda as perguntas para testar seus conhecimentos
                </div>
                <form id="questionarioForm">
                    <div class="mb-3">
                        <label class="form-label">1. Qual é a capital do Brasil?</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pergunta1" value="a" id="p1a">
                            <label class="form-check-label" for="p1a">São Paulo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pergunta1" value="b" id="p1b">
                            <label class="form-check-label" for="p1b">Rio de Janeiro</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pergunta1" value="c" id="p1c">
                            <label class="form-check-label" for="p1c">Brasília</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">2. Complete: A Prefeitura de Santa Rosa está localizada no estado do:</label>
                        <input type="text" class="form-control" name="pergunta2" placeholder="Digite sua resposta">
                    </div>
                </form>
            `;
            btnConcluir.style.display = 'inline-block';
        }
    }, 1000);
}

// Event listener para concluir aula
document.getElementById('btnConcluirAula').addEventListener('click', function() {
    if(aulaAtual) {
        // Simular conclusão da aula
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
        this.disabled = true;
        
        setTimeout(() => {
            alert('Aula marcada como concluída!');
            bootstrap.Modal.getInstance(document.getElementById('aulaModal')).hide();
            location.reload(); // Recarregar para atualizar progresso
        }, 2000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
