-- Banco de dados para Plataforma de Treinamento - Prefeitura de Santa Rosa
CREATE DATABASE IF NOT EXISTS plataforma_treinamento;
USE plataforma_treinamento;

-- Tabela de usuários
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('gestor', 'colaborador') NOT NULL DEFAULT 'colaborador',
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de cursos
CREATE TABLE cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    gestor_id INT,
    FOREIGN KEY (gestor_id) REFERENCES usuarios(id)
);

-- Tabela de aulas
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    ordem INT NOT NULL,
    tipo ENUM('video', 'documento', 'questionario') NOT NULL,
    conteudo TEXT, -- URL do YouTube ou conteúdo do documento/questionário
    duracao INT DEFAULT 0, -- em minutos
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
);

-- Tabela de progresso dos usuários
CREATE TABLE progresso_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    curso_id INT NOT NULL,
    aula_id INT NOT NULL,
    concluida BOOLEAN DEFAULT FALSE,
    data_conclusao TIMESTAMP NULL,
    pontuacao INT DEFAULT 0, -- para questionários
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progresso (usuario_id, aula_id)
);

-- Tabela de documentos
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aula_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho INT DEFAULT 0,
    tipo_arquivo VARCHAR(50),
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
);

-- Tabela de questionários
CREATE TABLE questionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aula_id INT NOT NULL,
    pergunta TEXT NOT NULL,
    tipo ENUM('multipla_escolha', 'verdadeiro_falso', 'texto') NOT NULL,
    opcoes JSON, -- para múltipla escolha
    resposta_correta TEXT,
    pontos INT DEFAULT 1,
    ordem INT NOT NULL,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
);

-- Tabela de respostas dos questionários
CREATE TABLE respostas_questionarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    questionario_id INT NOT NULL,
    resposta TEXT NOT NULL,
    correta BOOLEAN DEFAULT FALSE,
    data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (questionario_id) REFERENCES questionarios(id) ON DELETE CASCADE
);

-- Inserir usuário gestor padrão
INSERT INTO usuarios (nome, email, senha, tipo) VALUES 
('Administrador', 'admin@prefeitura.santarosa.rs.gov.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestor');

-- Inserir alguns cursos de exemplo
INSERT INTO cursos (titulo, descricao, gestor_id) VALUES 
('Capacitação em Serviço Público', 'Curso introdutório sobre ética e responsabilidade no serviço público', 1),
('Gestão de Documentos', 'Aprenda a organizar e gerenciar documentos oficiais', 1),
('Atendimento ao Cidadão', 'Técnicas de atendimento e comunicação com o público', 1);
