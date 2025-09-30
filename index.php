<?php
session_start();
require_once 'config/database.php';

$page_title = 'Plataforma de Treinamento - Prefeitura de Santa Rosa';
include 'includes/header.php';

// Buscar cursos ativos
$database = new Database();
$db = $database->getConnection();

$cursos = [];
if($db) {
    $query = "SELECT c.*, u.nome as gestor_nome, 
              COUNT(a.id) as total_aulas,
              (SELECT COUNT(*) FROM progresso_usuarios pu 
               WHERE pu.curso_id = c.id AND pu.usuario_id = ? AND pu.concluida = 1) as aulas_concluidas
              FROM cursos c 
              LEFT JOIN aulas a ON c.id = a.curso_id AND a.ativo = 1
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              WHERE c.status = 'ativo'
              GROUP BY c.id
              ORDER BY c.data_criacao DESC";
    
    $stmt = $db->prepare($query);
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $stmt->execute([$usuario_id]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estatísticas
$stats = [
    'total_cursos' => count($cursos),
    'total_aulas' => 0,
    'cursos_concluidos' => 0
];

if($db && isset($_SESSION['usuario_id'])) {
    // Total de aulas
    $query = "SELECT COUNT(*) as total FROM aulas a 
              INNER JOIN cursos c ON a.curso_id = c.id 
              WHERE c.status = 'ativo' AND a.ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_aulas'] = $result['total'];
    
    // Cursos concluídos pelo usuário
    $query = "SELECT COUNT(DISTINCT pu.curso_id) as concluidos
              FROM progresso_usuarios pu
              INNER JOIN cursos c ON pu.curso_id = c.id
              WHERE pu.usuario_id = ? AND c.status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['cursos_concluidos'] = $result['concluidos'];
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1>Plataforma de Treinamento</h1>
                <p class="lead">Desenvolva suas competências e conhecimentos através dos nossos cursos online. 
                Uma iniciativa da Prefeitura de Santa Rosa para capacitar seus servidores.</p>
                <?php if(!isset($_SESSION['usuario_id'])): ?>
                <div class="mt-4">
                    <a href="auth/login.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </a>
                    <a href="auth/cadastro.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus me-2"></i>Cadastrar
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-graduation-cap" style="font-size: 8rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>
</section>

<!-- Estatísticas -->
<?php if(isset($_SESSION['usuario_id'])): ?>
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <h3><?php echo $stats['total_cursos']; ?></h3>
                    <p>Cursos Disponíveis</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <h3><?php echo $stats['total_aulas']; ?></h3>
                    <p>Total de Aulas</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="stats-card">
                    <h3><?php echo $stats['cursos_concluidos']; ?></h3>
                    <p>Cursos Concluídos</p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Cursos -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="text-center mb-5">Nossos Cursos</h2>
                
                <?php if(empty($cursos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nenhum curso disponível no momento</h4>
                    <p class="text-muted">Novos cursos serão adicionados em breve.</p>
                </div>
                <?php else: ?>
                
                <!-- Filtro de busca -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Buscar cursos...">
                        </div>
                    </div>
                </div>
                
                <div class="row" id="cursosContainer">
                    <?php foreach($cursos as $curso): ?>
                    <div class="col-lg-4 col-md-6 course-card">
                        <div class="card h-100">
                            <?php if($curso['imagem']): ?>
                            <img src="<?php echo htmlspecialchars($curso['imagem']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($curso['titulo']); ?>">
                            <?php else: ?>
                            <div class="card-img-top bg-primary d-flex align-items-center justify-content-center text-white">
                                <i class="fas fa-graduation-cap fa-3x"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                                
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-play-circle me-1"></i>
                                            <?php echo $curso['total_aulas']; ?> aulas
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($curso['gestor_nome']); ?>
                                        </small>
                                    </div>
                                    
                                    <?php if(isset($_SESSION['usuario_id'])): ?>
                                    <?php 
                                    $progresso = $curso['total_aulas'] > 0 ? 
                                        round(($curso['aulas_concluidas'] / $curso['total_aulas']) * 100) : 0;
                                    ?>
                                    <div class="progress mb-3" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $progresso; ?>%" 
                                             aria-valuenow="<?php echo $progresso; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $progresso; ?>% concluído</small>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid mt-3">
                                        <a href="curso.php?id=<?php echo $curso['id']; ?>" 
                                           class="btn btn-primary">
                                            <i class="fas fa-play me-2"></i>
                                            <?php echo isset($_SESSION['usuario_id']) ? 'Continuar Curso' : 'Ver Curso'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Sobre -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h2>Sobre a Plataforma</h2>
                <p class="lead">A Plataforma de Treinamento da Prefeitura de Santa Rosa foi desenvolvida 
                para capacitar e desenvolver competências dos servidores municipais.</p>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check text-success me-2"></i> Cursos online com videoaulas</li>
                    <li><i class="fas fa-check text-success me-2"></i> Materiais de apoio e documentos</li>
                    <li><i class="fas fa-check text-success me-2"></i> Questionários de avaliação</li>
                    <li><i class="fas fa-check text-success me-2"></i> Acompanhamento de progresso</li>
                    <li><i class="fas fa-check text-success me-2"></i> Certificados de conclusão</li>
                </ul>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-university fa-5x text-primary"></i>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
