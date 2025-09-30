<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Plataforma de Treinamento - Prefeitura de Santa Rosa'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="assets/css/style.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <div class="d-flex align-items-center">
                    <div class="brand-icon me-2">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span class="brand-text">Plataforma de Treinamento</span>
                </div>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="index.php">
                            <i class="fas fa-home me-1"></i>
                            Início
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="cursos.php">
                            <i class="fas fa-book me-1"></i>
                            Cursos
                        </a>
                    </li>
                    <?php if(isset($_SESSION['usuario_id']) && $_SESSION['tipo'] == 'gestor'): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="admin/">
                            <i class="fas fa-cog me-1"></i>
                            Admin
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['usuario_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar me-2">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="fw-500"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="perfil.php">
                                    <i class="fas fa-user me-2"></i>
                                    Meu Perfil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="meus-cursos.php">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    Meus Cursos
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Sair
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link fw-500" href="auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Entrar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-2" href="auth/cadastro.php">
                            <i class="fas fa-user-plus me-1"></i>
                            Cadastrar
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
