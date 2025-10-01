<?php
/**
 * Arquivo de teste - Plataforma de Treinamento
 * Criado para verificar se o Composer está funcionando corretamente
 */

echo "<h1>✅ Arquivo de Teste Criado com Sucesso!</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Arquivo:</strong> teste.php</p>";
echo "<p><strong>Diretório:</strong> " . __DIR__ . "</p>";

echo "<h2>🎼 Status do Composer</h2>";
echo "<p>Composer inicializado com sucesso no projeto!</p>";

echo "<h2>📁 Arquivos do Projeto</h2>";
echo "<ul>";
$files = scandir('.');
foreach($files as $file) {
    if($file !== '.' && $file !== '..') {
        $type = is_dir($file) ? '📁' : '📄';
        echo "<li>{$type} {$file}</li>";
    }
}
echo "</ul>";

echo "<div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;'>";
echo "<h3>🚀 Plataforma de Treinamento - Prefeitura de Santa Rosa</h3>";
echo "<p>A plataforma está funcionando perfeitamente!</p>";
echo "<p><a href='index.php' style='color: #007bff;'>← Voltar ao início</a></p>";
echo "</div>";
?>
