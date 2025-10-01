<?php
/**
 * Arquivo de Teste - Plataforma de Treinamento
 * Prefeitura de Santa Rosa
 */

// Configurações de teste
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🎓 Plataforma de Treinamento - Teste</h1>\n";
echo "<hr>\n";

// Teste 1: Verificar PHP
echo "<h2>✅ Teste 1: Versão do PHP</h2>\n";
echo "<p>Versão PHP: <strong>" . phpversion() . "</strong></p>\n";
echo "<p>Server: <strong>" . $_SERVER['SERVER_SOFTWARE'] . "</strong></p>\n";
echo "<hr>\n";

// Teste 2: Verificar extensões necessárias
echo "<h2>✅ Teste 2: Extensões PHP</h2>\n";
$extensoes = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
echo "<ul>\n";
foreach ($extensoes as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? '✅' : '❌';
    echo "<li>$status <strong>$ext</strong>: " . ($loaded ? 'Instalada' : 'NÃO instalada') . "</li>\n";
}
echo "</ul>\n";
echo "<hr>\n";

// Teste 3: Verificar conexão com banco de dados
echo "<h2>✅ Teste 3: Conexão com Banco de Dados</h2>\n";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✅ <strong>Conexão com o banco de dados: SUCESSO!</strong></p>\n";
        
        // Testar query simples
        $query = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total de usuários cadastrados: <strong>" . $result['total'] . "</strong></p>\n";
        
        $query = "SELECT COUNT(*) as total FROM cursos WHERE status = 'ativo'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Total de cursos ativos: <strong>" . $result['total'] . "</strong></p>\n";
        
    } else {
        echo "<p style='color: red;'>❌ <strong>Erro ao conectar com o banco de dados!</strong></p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Erro:</strong> " . $e->getMessage() . "</p>\n";
}
echo "<hr>\n";

// Teste 4: Verificar Composer
echo "<h2>✅ Teste 4: Composer</h2>\n";
if (file_exists('composer.json')) {
    echo "<p style='color: green;'>✅ <strong>composer.json encontrado!</strong></p>\n";
    $composer = json_decode(file_get_contents('composer.json'), true);
    echo "<p>Nome do projeto: <strong>" . $composer['name'] . "</strong></p>\n";
    echo "<p>Descrição: <strong>" . $composer['description'] . "</strong></p>\n";
} else {
    echo "<p style='color: red;'>❌ <strong>composer.json não encontrado!</strong></p>\n";
}
echo "<hr>\n";

// Teste 5: Verificar estrutura de diretórios
echo "<h2>✅ Teste 5: Estrutura de Diretórios</h2>\n";
$diretorios = [
    'admin' => 'Painel Administrativo',
    'assets' => 'Assets (CSS, JS, Imagens)',
    'auth' => 'Sistema de Autenticação',
    'config' => 'Configurações',
    'includes' => 'Includes (Header, Footer)',
    'sql' => 'Scripts SQL'
];
echo "<ul>\n";
foreach ($diretorios as $dir => $desc) {
    $exists = is_dir($dir);
    $status = $exists ? '✅' : '❌';
    echo "<li>$status <strong>/$dir</strong>: $desc - " . ($exists ? 'OK' : 'NÃO ENCONTRADO') . "</li>\n";
}
echo "</ul>\n";
echo "<hr>\n";

// Teste 6: Verificar arquivos principais
echo "<h2>✅ Teste 6: Arquivos Principais</h2>\n";
$arquivos = [
    'index.php' => 'Página inicial',
    'curso.php' => 'Página de curso',
    'cursos.php' => 'Lista de cursos',
    'setup.php' => 'Setup do banco de dados',
    'README.md' => 'Documentação',
    '.gitignore' => 'Git ignore'
];
echo "<ul>\n";
foreach ($arquivos as $arquivo => $desc) {
    $exists = file_exists($arquivo);
    $status = $exists ? '✅' : '❌';
    echo "<li>$status <strong>$arquivo</strong>: $desc - " . ($exists ? 'OK' : 'NÃO ENCONTRADO') . "</li>\n";
}
echo "</ul>\n";
echo "<hr>\n";

// Informações finais
echo "<h2>🎉 Resumo do Teste</h2>\n";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>\n";
echo "<p><strong>Timezone:</strong> " . date_default_timezone_get() . "</p>\n";
echo "<p><strong>Sistema Operacional:</strong> " . PHP_OS . "</p>\n";
echo "<p><strong>Arquitetura:</strong> " . php_uname('m') . "</p>\n";

echo "<hr>\n";
echo "<p style='text-align: center;'><a href='index.php' style='text-decoration: none; background: #2563eb; color: white; padding: 10px 20px; border-radius: 5px; display: inline-block;'>🏠 Voltar para a Página Inicial</a></p>\n";

// Estilos CSS
echo "<style>
    body {
        font-family: 'Inter', sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background: #f8fafc;
    }
    h1 {
        color: #2563eb;
        border-bottom: 3px solid #2563eb;
        padding-bottom: 10px;
    }
    h2 {
        color: #1e293b;
        margin-top: 20px;
    }
    hr {
        border: none;
        border-top: 1px solid #e5e7eb;
        margin: 20px 0;
    }
    ul {
        list-style: none;
        padding: 0;
    }
    li {
        padding: 5px 0;
        font-size: 16px;
    }
    p {
        font-size: 16px;
        line-height: 1.6;
    }
</style>\n";

