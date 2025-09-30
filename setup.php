<?php
/**
 * Script de configuração da Plataforma de Treinamento
 * Execute este arquivo uma vez para configurar o banco de dados
 */

$host = 'localhost';
$username = 'root';
$password = '';
$database_name = 'plataforma_treinamento';

echo "<h2>Configuração da Plataforma de Treinamento</h2>";
echo "<p>Configurando banco de dados...</p>";

try {
    // Conectar ao MySQL sem especificar banco
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Conectado ao MySQL</p>";
    
    // Ler arquivo SQL
    $sql = file_get_contents('sql/database.sql');
    
    if($sql === false) {
        throw new Exception("Erro ao ler arquivo sql/database.sql");
    }
    
    echo "<p>✓ Arquivo SQL carregado</p>";
    
    // Executar comandos SQL
    $statements = explode(';', $sql);
    
    foreach($statements as $statement) {
        $statement = trim($statement);
        if(!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Banco de dados criado com sucesso!</p>";
    echo "<p>✓ Tabelas criadas</p>";
    echo "<p>✓ Dados de exemplo inseridos</p>";
    
    // Testar conexão com o banco configurado
    $database = new PDO("mysql:host=$host;dbname=$database_name", $username, $password);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Conexão com banco de dados testada</p>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✓ Configuração Concluída!</h4>";
    echo "<p><strong>Usuário administrador criado:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@prefeitura.santarosa.rs.gov.br</li>";
    echo "<li><strong>Senha:</strong> password</li>";
    echo "<li><strong>Tipo:</strong> Gestor</li>";
    echo "</ul>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Faça login com as credenciais acima</li>";
    echo "<li>Altere a senha padrão</li>";
    echo "<li>Comece a criar seus cursos</li>";
    echo "</ol>";
    echo "<p><a href='auth/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fazer Login</a></p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>⚠️ Importante</h4>";
    echo "<p>Por segurança, <strong>delete este arquivo (setup.php)</strong> após a configuração.</p>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✗ Erro de Configuração</h4>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>Se o MySQL está rodando</li>";
    echo "<li>Se as credenciais estão corretas (usuário: root, sem senha)</li>";
    echo "<li>Se o arquivo sql/database.sql existe</li>";
    echo "</ul>";
    echo "</div>";
} catch(Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✗ Erro</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f9fa;
}
h2 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
p {
    margin: 10px 0;
}
</style>
