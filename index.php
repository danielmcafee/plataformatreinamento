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

// Estatísticas gerais
$stats = [
    'total_cursos' => count($cursos),
    'total_aulas' => 0,
    'cursos_concluidos' => 0,
    'total_usuarios' => 0,
    'aulas_concluidas' => 0
];

// Dados para gráficos
$chart_data = [
    'progresso_por_mes' => [],
    'cursos_populares' => [],
    'tipos_aulas' => []
];

if($db) {
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
    
    // Aulas concluídas
    $query = "SELECT COUNT(*) as total FROM progresso_usuarios WHERE concluida = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['aulas_concluidas'] = $result['total'];
    
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
    
    // Dados para gráfico de progresso por mês (últimos 6 meses)
    $query = "SELECT 
                DATE_FORMAT(pu.data_conclusao, '%Y-%m') as mes,
                COUNT(*) as total_conclusoes
              FROM progresso_usuarios pu
              WHERE pu.concluida = 1 
                AND pu.data_conclusao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(pu.data_conclusao, '%Y-%m')
              ORDER BY mes";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $chart_data['progresso_por_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dados para gráfico de cursos populares
    $query = "SELECT 
                c.titulo,
                COUNT(pu.usuario_id) as total_inscritos
              FROM cursos c
              LEFT JOIN progresso_usuarios pu ON c.id = pu.curso_id
              WHERE c.status = 'ativo'
              GROUP BY c.id, c.titulo
              ORDER BY total_inscritos DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $chart_data['cursos_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dados para gráfico de tipos de aulas
    $query = "SELECT 
                a.tipo,
                COUNT(*) as total
              FROM aulas a
              INNER JOIN cursos c ON a.curso_id = c.id
              WHERE a.ativo = 1 AND c.status = 'ativo'
              GROUP BY a.tipo";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $chart_data['tipos_aulas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- Hero Section -->
<section class="hero-section-subtle">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="hero-title">Plataforma de Treinamento</h1>
                <p class="hero-subtitle">Desenvolva suas competências através dos nossos cursos online. 
                Uma iniciativa da Prefeitura de Santa Rosa para capacitar seus servidores.</p>
                <?php if(!isset($_SESSION['usuario_id'])): ?>
                <div class="hero-actions">
                    <a href="auth/login.php" class="btn btn-primary me-3">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrar
                    </a>
                    <a href="auth/cadastro.php" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i>Cadastrar
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-4 text-center">
                <div class="hero-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cursos - Seção Principal -->
<section class="py-5">
    <div class="container-fluid">
        <!-- Header da Seção de Cursos -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center">
                    <h2 class="display-5 fw-bold mb-3">Nossos Cursos</h2>
                    <p class="lead text-muted">Explore nossa coleção de cursos e desenvolva suas competências profissionais</p>
                </div>
            </div>
        </div>

        <!-- Layout de 2 Colunas para Cursos -->
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
                            <?php if(isset($_SESSION['usuario_id'])): ?>
                            <a href="meus-cursos.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-graduation-cap me-2"></i>
                                Meus Cursos
                            </a>
                            <a href="perfil.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user me-2"></i>
                                Meu Perfil
                            </a>
                            <a href="cursos.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-list me-2"></i>
                                Ver Todos os Cursos
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
                            <a href="cursos.php" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-list me-2"></i>
                                Ver Cursos
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Widget de Tipos de Aulas -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Tipos de Aulas
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="tiposAulasChart"></canvas>
                    </div>
                </div>

                <!-- Widget de Últimas Atividades -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Últimas Atividades
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-plus-circle text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">Novo curso adicionado</h6>
                                    <small class="text-muted">Há 2 horas</small>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-check-circle text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">Aula concluída</h6>
                                    <small class="text-muted">Há 4 horas</small>
                                </div>
                            </div>
                            <div class="list-group-item d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-user-plus text-info"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 small">Novo usuário cadastrado</h6>
                                    <small class="text-muted">Há 1 dia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard de Análises -->
<section class="py-5 bg-light">
    <div class="container-fluid">
        <!-- Cards de Estatísticas -->
        <div class="row mb-5">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_cursos']; ?></h3>
                            <p class="mb-0">Cursos Disponíveis</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-play-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_aulas']; ?></h3>
                            <p class="mb-0">Total de Aulas</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats['total_usuarios']; ?></h3>
                            <p class="mb-0">Usuários Ativos</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon me-3">
                            <i class="fas fa-check-circle fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h3 class="mb-0"><?php echo $stats['aulas_concluidas']; ?></h3>
                            <p class="mb-0">Aulas Concluídas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Desempenho -->
        <div class="row">
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Progresso por Mês
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="progressoChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy me-2"></i>
                            Cursos Mais Populares
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="cursosPopularesChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Sobre -->
<section class="py-5 bg-light">
    <div class="container-fluid">
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

<script>
// Dados dos gráficos
const chartData = {
    progressoPorMes: <?php echo json_encode($chart_data['progresso_por_mes']); ?>,
    cursosPopulares: <?php echo json_encode($chart_data['cursos_populares']); ?>,
    tiposAulas: <?php echo json_encode($chart_data['tipos_aulas']); ?>
};

// Configuração dos gráficos
Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.color = '#64748b';

// Gráfico de Progresso por Mês
const progressoCtx = document.getElementById('progressoChart').getContext('2d');
new Chart(progressoCtx, {
    type: 'line',
    data: {
        labels: chartData.progressoPorMes.map(item => {
            const [year, month] = item.mes.split('-');
            return new Date(year, month - 1).toLocaleDateString('pt-BR', { month: 'short', year: 'numeric' });
        }),
        datasets: [{
            label: 'Aulas Concluídas',
            data: chartData.progressoPorMes.map(item => item.total_conclusoes),
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                },
                ticks: {
                    color: '#64748b'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b'
                }
            }
        }
    }
});

// Gráfico de Tipos de Aulas
const tiposAulasCtx = document.getElementById('tiposAulasChart').getContext('2d');
new Chart(tiposAulasCtx, {
    type: 'doughnut',
    data: {
        labels: chartData.tiposAulas.map(item => item.tipo.charAt(0).toUpperCase() + item.tipo.slice(1)),
        datasets: [{
            data: chartData.tiposAulas.map(item => item.total),
            backgroundColor: [
                '#2563eb',
                '#059669',
                '#d97706',
                '#dc2626',
                '#0891b2'
            ],
            borderWidth: 0,
            cutout: '60%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        }
    }
});

// Gráfico de Cursos Populares
const cursosPopularesCtx = document.getElementById('cursosPopularesChart').getContext('2d');
new Chart(cursosPopularesCtx, {
    type: 'bar',
    data: {
        labels: chartData.cursosPopulares.map(item => item.titulo.length > 20 ? item.titulo.substring(0, 20) + '...' : item.titulo),
        datasets: [{
            label: 'Inscrições',
            data: chartData.cursosPopulares.map(item => item.total_inscritos),
            backgroundColor: '#2563eb',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 6,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9'
                },
                ticks: {
                    color: '#64748b'
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    maxRotation: 45
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
