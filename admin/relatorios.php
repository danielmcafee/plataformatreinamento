<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado e é gestor
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'gestor') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Relatórios - Painel Administrativo';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Estatísticas gerais
$stats = [
    'total_usuarios' => 0,
    'total_cursos' => 0,
    'total_aulas' => 0,
    'usuarios_ativos' => 0,
    'cursos_ativos' => 0,
    'aulas_concluidas' => 0
];

if($db) {
    // Total de usuários
    $query = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_usuarios'] = $result['total'];
    
    // Usuários ativos
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['usuarios_ativos'] = $result['total'];
    
    // Total de cursos
    $query = "SELECT COUNT(*) as total FROM cursos";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_cursos'] = $result['total'];
    
    // Cursos ativos
    $query = "SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['cursos_ativos'] = $result['total'];
    
    // Total de aulas
    $query = "SELECT COUNT(*) as total FROM aulas WHERE ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_aulas'] = $result['total'];
    
    // Aulas concluídas
    $query = "SELECT COUNT(*) as total FROM progresso_usuarios WHERE concluida = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['aulas_concluidas'] = $result['total'];
}

// Relatório de cursos mais populares
$cursos_populares = [];
if($db) {
    $query = "SELECT c.titulo, c.id, 
              COUNT(pu.usuario_id) as total_inscritos,
              COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) as total_concluidos,
              ROUND(COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) * 100.0 / COUNT(pu.usuario_id), 2) as taxa_conclusao
              FROM cursos c
              LEFT JOIN progresso_usuarios pu ON c.id = pu.curso_id
              WHERE c.status = 'ativo'
              GROUP BY c.id, c.titulo
              ORDER BY total_inscritos DESC
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $cursos_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de usuários mais ativos
$usuarios_ativos = [];
if($db) {
    $query = "SELECT u.nome, u.email, u.tipo,
              COUNT(pu.aula_id) as total_aulas_assistidas,
              COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) as aulas_concluidas,
              COUNT(DISTINCT pu.curso_id) as cursos_inscritos
              FROM usuarios u
              LEFT JOIN progresso_usuarios pu ON u.id = pu.usuario_id
              WHERE u.ativo = 1
              GROUP BY u.id, u.nome, u.email, u.tipo
              ORDER BY aulas_concluidas DESC
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $usuarios_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Relatório de progresso por tipo de aula
$progresso_por_tipo = [];
if($db) {
    $query = "SELECT a.tipo,
              COUNT(pu.aula_id) as total_aulas,
              COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) as aulas_concluidas,
              ROUND(COUNT(CASE WHEN pu.concluida = 1 THEN 1 END) * 100.0 / COUNT(pu.aula_id), 2) as taxa_conclusao
              FROM aulas a
              LEFT JOIN progresso_usuarios pu ON a.id = pu.aula_id
              WHERE a.ativo = 1
              GROUP BY a.tipo
              ORDER BY aulas_concluidas DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $progresso_por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <a class="nav-link" href="usuarios.php">
                            <i class="fas fa-users me-2"></i>
                            Usuários
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="relatorios.php">
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
                <h1 class="h2">Relatórios e Estatísticas</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estatísticas Gerais -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Usuários
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_usuarios']; ?>
                                    </div>
                                    <div class="text-xs text-muted">
                                        <?php echo $stats['usuarios_ativos']; ?> ativos
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Cursos Ativos
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['cursos_ativos']; ?>
                                    </div>
                                    <div class="text-xs text-muted">
                                        de <?php echo $stats['total_cursos']; ?> total
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total de Aulas
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_aulas']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Aulas Concluídas
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['aulas_concluidas']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Cursos Mais Populares -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Cursos Mais Populares</h6>
                        </div>
                        <div class="card-body">
                            <?php if(empty($cursos_populares)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-bar fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Nenhum dado disponível</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Curso</th>
                                            <th>Inscritos</th>
                                            <th>Concluídos</th>
                                            <th>Taxa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($cursos_populares as $curso): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo htmlspecialchars($curso['titulo']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $curso['total_inscritos']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $curso['total_concluidos']; ?></span>
                                            </td>
                                            <td>
                                                <small><?php echo $curso['taxa_conclusao']; ?>%</small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Usuários Mais Ativos -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Usuários Mais Ativos</h6>
                        </div>
                        <div class="card-body">
                            <?php if(empty($usuarios_ativos)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Nenhum dado disponível</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Usuário</th>
                                            <th>Tipo</th>
                                            <th>Aulas</th>
                                            <th>Cursos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($usuarios_ativos as $usuario): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo htmlspecialchars($usuario['nome']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $usuario['tipo'] == 'gestor' ? 'primary' : 'info'; ?>">
                                                    <?php echo ucfirst($usuario['tipo']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $usuario['aulas_concluidas']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $usuario['cursos_inscritos']; ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Progresso por Tipo de Aula -->
                <div class="col-lg-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">Progresso por Tipo de Aula</h6>
                        </div>
                        <div class="card-body">
                            <?php if(empty($progresso_por_tipo)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-chart-pie fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Nenhum dado disponível</p>
                            </div>
                            <?php else: ?>
                            <div class="row">
                                <?php foreach($progresso_por_tipo as $tipo): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card border-left-<?php echo $tipo['tipo'] == 'video' ? 'danger' : ($tipo['tipo'] == 'documento' ? 'info' : 'warning'); ?>">
                                        <div class="card-body">
                                            <div class="text-center">
                                                <h6 class="text-uppercase">
                                                    <i class="fas fa-<?php echo $tipo['tipo'] == 'video' ? 'play' : ($tipo['tipo'] == 'documento' ? 'file' : 'question-circle'); ?> me-2"></i>
                                                    <?php echo ucfirst($tipo['tipo']); ?>
                                                </h6>
                                                <div class="h4 mb-0"><?php echo $tipo['aulas_concluidas']; ?></div>
                                                <small class="text-muted">de <?php echo $tipo['total_aulas']; ?> aulas</small>
                                                <div class="progress mt-2" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $tipo['taxa_conclusao']; ?>%" 
                                                         aria-valuenow="<?php echo $tipo['taxa_conclusao']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted"><?php echo $tipo['taxa_conclusao']; ?>% concluído</small>
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
            </div>
        </main>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.text-xs {
    font-size: 0.7rem;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}

@media print {
    .admin-sidebar, .btn-toolbar {
        display: none !important;
    }
    .col-md-9 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
