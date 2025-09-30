<?php
session_start();
require_once 'config/database.php';

$page_title = 'Cursos - Plataforma de Treinamento';
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Buscar cursos ativos
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
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Nossos Cursos</h1>
            
            <?php if(empty($cursos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
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

<?php include 'includes/footer.php'; ?>
