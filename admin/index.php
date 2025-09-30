<?php
session_start();
require_once '../config/database.php';

// Verificar se está logado e é gestor
if(!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] != 'gestor') {
    header('Location: ../auth/login.php');
    exit;
}

$page_title = 'Painel Administrativo - Plataforma de Treinamento';
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Estatísticas do painel
$stats = [
    'total_cursos' => 0,
    'total_aulas' => 0,
    'total_usuarios' => 0,
    'cursos_ativos' => 0
];

if($db) {
    // Total de cursos
    $query = "SELECT COUNT(*) as total FROM cursos";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['total_cursos'] = $result['total'];
    
    // Total de aulas
    $query = "SELECT COUNT(*) as total FROM aulas WHERE ativo = 1";
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
    
    // Cursos ativos
    $query = "SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['cursos_ativos'] = $result['total'];
}

// Cursos recentes
$cursos_recentes = [];
if($db) {
    $query = "SELECT c.*, u.nome as gestor_nome, 
              COUNT(a.id) as total_aulas
              FROM cursos c 
              LEFT JOIN aulas a ON c.id = a.curso_id AND a.ativo = 1
              LEFT JOIN usuarios u ON c.gestor_id = u.id
              GROUP BY c.id
              ORDER BY c.data_criacao DESC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $cursos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block admin-sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
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
                        <a class="nav-link" href="relatorios.php">
                            <i class="fas fa-chart-bar me-2"></i>
                            Relatórios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-2"></i>
                            Voltar ao Site
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Conteúdo Principal -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="cursos.php?action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Novo Curso
                        </a>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Cursos
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_cursos']; ?>
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
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        Total de Usuários
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $stats['total_usuarios']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cursos Recentes -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Cursos Recentes</h6>
                            <a href="cursos.php" class="btn btn-sm btn-primary">Ver Todos</a>
                        </div>
                        <div class="card-body">
                            <?php if(empty($cursos_recentes)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhum curso cadastrado ainda.</p>
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
                                        <?php foreach($cursos_recentes as $curso): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($curso['titulo']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $curso['status'] == 'ativo' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($curso['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $curso['total_aulas']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($curso['data_criacao'])); ?></td>
                                            <td>
                                                <a href="cursos.php?action=edit&id=<?php echo $curso['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="../curso.php?id=<?php echo $curso['id']; ?>" 
                                                   class="btn btn-sm btn-outline-info" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
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

                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Ações Rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="cursos.php?action=create" class="list-group-item list-group-item-action">
                                    <i class="fas fa-plus me-2"></i>Criar Novo Curso
                                </a>
                                <a href="aulas.php?action=create" class="list-group-item list-group-item-action">
                                    <i class="fas fa-play-circle me-2"></i>Adicionar Aula
                                </a>
                                <a href="usuarios.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-users me-2"></i>Gerenciar Usuários
                                </a>
                                <a href="relatorios.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-bar me-2"></i>Ver Relatórios
                                </a>
                            </div>
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
.text-xs {
    font-size: 0.7rem;
}
.text-gray-300 {
    color: #dddfeb !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
</style>

<?php include '../includes/footer.php'; ?>
