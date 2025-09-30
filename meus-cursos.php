<?php
session_start();
require_once 'config/database.php';

// Verificar se está logado
if(!isset($_SESSION['usuario_id'])) {
    header('Location: auth/login.php');
    exit;
}

$page_title = 'Meus Cursos - Plataforma de Treinamento';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Buscar cursos do usuário com progresso
$cursos = [];
if($db) {
    $query = "SELECT c.*, u.nome as gestor_nome,
              COUNT(a.id) as total_aulas,
              COUNT(pu.aula_id) as aulas_iniciadas,
              COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) as aulas_concluidas,
              MAX(pu.data_conclusao) as ultima_atividade
              FROM cursos c
              LEFT JOIN aulas a ON c.id = a.curso_id AND a.ativo = 1
              LEFT JOIN progresso_usuarios pu ON c.id = pu.curso_id AND pu.usuario_id = ?
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              WHERE c.status = 'ativo'
              GROUP BY c.id
              HAVING aulas_iniciadas > 0 OR ? = 0
              ORDER BY ultima_atividade DESC, c.data_criacao DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id'], $_SESSION['usuario_id']]);
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar cursos disponíveis (não iniciados)
$cursos_disponiveis = [];
if($db) {
    $query = "SELECT c.*, u.nome as gestor_nome,
              COUNT(a.id) as total_aulas
              FROM cursos c
              LEFT JOIN aulas a ON c.id = a.curso_id AND a.ativo = 1
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              WHERE c.status = 'ativo'
              AND c.id NOT IN (
                  SELECT DISTINCT pu.curso_id 
                  FROM progresso_usuarios pu 
                  WHERE pu.usuario_id = ?
              )
              GROUP BY c.id
              ORDER BY c.data_criacao DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['usuario_id']]);
    $cursos_disponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Estatísticas do usuário
$stats = [
    'cursos_inscritos' => count($cursos),
    'cursos_disponiveis' => count($cursos_disponiveis),
    'total_aulas' => 0,
    'aulas_concluidas' => 0
];

foreach($cursos as $curso) {
    $stats['total_aulas'] += $curso['total_aulas'];
    $stats['aulas_concluidas'] += $curso['aulas_concluidas'];
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-graduation-cap me-2"></i>
                Meus Cursos
            </h1>
            
            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['cursos_inscritos']; ?></h3>
                        <p>Cursos Inscritos</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['cursos_disponiveis']; ?></h3>
                        <p>Cursos Disponíveis</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['total_aulas']; ?></h3>
                        <p>Total de Aulas</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <h3><?php echo $stats['aulas_concluidas']; ?></h3>
                        <p>Aulas Concluídas</p>
                    </div>
                </div>
            </div>

            <!-- Cursos em Andamento -->
            <?php if(!empty($cursos)): ?>
            <div class="card mb-5">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle me-2"></i>
                        Cursos em Andamento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($cursos as $curso): ?>
                        <?php 
                        $progresso = $curso['total_aulas'] > 0 ? 
                            round(($curso['aulas_concluidas'] / $curso['total_aulas']) * 100) : 0;
                        $status_class = $progresso == 100 ? 'success' : ($progresso > 0 ? 'primary' : 'secondary');
                        ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars($curso['descricao']); ?>
                                    </p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">
                                                <i class="fas fa-play-circle me-1"></i>
                                                <?php echo $curso['aulas_concluidas']; ?> de <?php echo $curso['total_aulas']; ?> aulas
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($curso['gestor_nome']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-<?php echo $status_class; ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $progresso; ?>%" 
                                                 aria-valuenow="<?php echo $progresso; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $progresso; ?>% concluído</small>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="curso.php?id=<?php echo $curso['id']; ?>" 
                                           class="btn btn-<?php echo $status_class; ?> btn-sm">
                                            <i class="fas fa-<?php echo $progresso == 100 ? 'check' : 'play'; ?> me-1"></i>
                                            <?php echo $progresso == 100 ? 'Revisar' : 'Continuar'; ?>
                                        </a>
                                        
                                        <?php if($curso['ultima_atividade']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            Última atividade: <?php echo date('d/m/Y', strtotime($curso['ultima_atividade'])); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Cursos Disponíveis -->
            <?php if(!empty($cursos_disponiveis)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Cursos Disponíveis
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($cursos_disponiveis as $curso): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                <?php if($curso['imagem']): ?>
                                <img src="<?php echo htmlspecialchars($curso['imagem']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($curso['titulo']); ?>" 
                                     style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                <div class="card-img-top bg-primary d-flex align-items-center justify-content-center text-white" 
                                     style="height: 150px;">
                                    <i class="fas fa-graduation-cap fa-3x"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title"><?php echo htmlspecialchars($curso['titulo']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <?php echo htmlspecialchars($curso['descricao']); ?>
                                    </p>
                                    
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
                                        
                                        <div class="d-grid">
                                            <a href="curso.php?id=<?php echo $curso['id']; ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-play me-1"></i>
                                                Iniciar Curso
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mensagem quando não há cursos -->
            <?php if(empty($cursos) && empty($cursos_disponiveis)): ?>
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Nenhum curso disponível</h4>
                <p class="text-muted">Aguarde novos cursos serem adicionados pelos gestores.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Voltar ao Início
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
