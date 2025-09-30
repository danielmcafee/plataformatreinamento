<?php
session_start();
require_once 'config/database.php';

$page_title = 'Cursos - Plataforma de Treinamento';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Buscar cursos ativos
$cursos = [];
$stats = [
    'total_cursos' => 0,
    'total_aulas' => 0,
    'cursos_concluidos' => 0,
    'total_usuarios' => 0
];

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
    
    // Estatísticas
    $stats['total_cursos'] = count($cursos);
    
    // Total de aulas
    $query = "SELECT COUNT(*) as total FROM aulas a 
              INNER JOIN cursos c ON a.curso_id = c.id 
              WHERE c.status = 'ativo' AND a.ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_aulas'] = $result['total'];
    
    // Total de usuários
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_usuarios'] = $result['total'];
    
    if(isset($_SESSION['usuario_id'])) {
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
}
?>

<!-- Header Section -->
<section class="py-4 bg-light border-bottom">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="mb-2">Nossos Cursos</h1>
                <p class="text-muted mb-0">Explore nossa coleção de cursos e desenvolva suas competências</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex align-items-center justify-content-lg-end">
                    <span class="badge bg-primary me-2"><?php echo $stats['total_cursos']; ?> cursos</span>
                    <span class="badge bg-success"><?php echo $stats['total_aulas']; ?> aulas</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container-fluid py-4">
    <!-- Layout de 2 Colunas -->
    <div class="row">
        <!-- Coluna Principal - Lista de Cursos -->
        <div class="col-lg-8">
            <?php if(empty($cursos)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nenhum curso disponível no momento</h4>
                    <p class="text-muted">Novos cursos serão adicionados em breve.</p>
                </div>
            </div>
            <?php else: ?>
            
            <!-- Filtro de busca -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchInput" placeholder="Buscar cursos...">
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                Mostrando <?php echo count($cursos); ?> curso(s)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grid de Cursos -->
            <div class="row" id="cursosContainer">
                <?php foreach($cursos as $curso): ?>
                <div class="col-lg-6 col-md-6 course-card mb-4">
                    <div class="card h-100 course-card-modern">
                        <?php if($curso['imagem']): ?>
                        <img src="<?php echo htmlspecialchars($curso['imagem']); ?>" 
                             class="card-img-top course-image" alt="<?php echo htmlspecialchars($curso['titulo']); ?>">
                        <?php else: ?>
                        <div class="card-img-top course-image-placeholder">
                            <i class="fas fa-graduation-cap fa-3x"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                                <span class="badge bg-primary"><?php echo $curso['total_aulas']; ?> aulas</span>
                            </div>
                            
                            <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($curso['descricao']); ?></p>
                            
                            <div class="course-meta mb-3">
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-user me-2"></i>
                                    <span>Instrutor: <?php echo htmlspecialchars($curso['gestor_nome']); ?></span>
                                </div>
                            </div>
                            
                            <?php if(isset($_SESSION['usuario_id'])): ?>
                            <?php 
                            $progresso = $curso['total_aulas'] > 0 ? 
                                round(($curso['aulas_concluidas'] / $curso['total_aulas']) * 100) : 0;
                            ?>
                            <div class="progress-section mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Progresso</small>
                                    <small class="fw-bold text-primary"><?php echo $progresso; ?>%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progresso; ?>%" 
                                         aria-valuenow="<?php echo $progresso; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-auto">
                                <a href="curso.php?id=<?php echo $curso['id']; ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-play me-2"></i>
                                    <?php echo isset($_SESSION['usuario_id']) ? 'Continuar Curso' : 'Ver Curso'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Lateral - Widgets -->
        <div class="col-lg-4">
            <!-- Widget de Estatísticas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estatísticas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-graduation-cap fa-2x text-primary mb-2"></i>
                                <h5 class="mb-0"><?php echo $stats['total_cursos']; ?></h5>
                                <small class="text-muted">Cursos</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="stat-item">
                                <i class="fas fa-play-circle fa-2x text-success mb-2"></i>
                                <h5 class="mb-0"><?php echo $stats['total_aulas']; ?></h5>
                                <small class="text-muted">Aulas</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item">
                                <i class="fas fa-users fa-2x text-info mb-2"></i>
                                <h5 class="mb-0"><?php echo $stats['total_usuarios']; ?></h5>
                                <small class="text-muted">Usuários</small>
                            </div>
                        </div>
                        <?php if(isset($_SESSION['usuario_id'])): ?>
                        <div class="col-6">
                            <div class="stat-item">
                                <i class="fas fa-check-circle fa-2x text-warning mb-2"></i>
                                <h5 class="mb-0"><?php echo $stats['cursos_concluidos']; ?></h5>
                                <small class="text-muted">Concluídos</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Widget de Categorias -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-tags me-2"></i>
                        Categorias
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-primary">Tecnologia</span>
                        <span class="badge bg-success">Gestão</span>
                        <span class="badge bg-info">Administração</span>
                        <span class="badge bg-warning">Recursos Humanos</span>
                        <span class="badge bg-danger">Finanças</span>
                    </div>
                </div>
            </div>

            <!-- Widget de Ações Rápidas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-home me-2"></i>
                            Voltar ao Início
                        </a>
                        <?php if(isset($_SESSION['usuario_id'])): ?>
                        <a href="meus-cursos.php" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Meus Cursos
                        </a>
                        <a href="perfil.php" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-user me-2"></i>
                            Meu Perfil
                        </a>
                        <?php else: ?>
                        <a href="auth/login.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Fazer Login
                        </a>
                        <a href="auth/cadastro.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-2"></i>
                            Criar Conta
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Widget de Últimos Cursos -->
            <?php if(!empty($cursos)): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Últimos Cursos
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $ultimos_cursos = array_slice($cursos, 0, 3);
                    foreach($ultimos_cursos as $curso): 
                    ?>
                    <div class="d-flex align-items-center p-3 border-bottom">
                        <div class="me-3">
                            <div class="course-mini-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 small"><?php echo htmlspecialchars(substr($curso['titulo'], 0, 25)) . (strlen($curso['titulo']) > 25 ? '...' : ''); ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-play-circle me-1"></i>
                                <?php echo $curso['total_aulas']; ?> aulas
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
