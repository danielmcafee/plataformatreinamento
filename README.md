# Plataforma de Treinamento - Prefeitura de Santa Rosa

Sistema completo de plataforma de treinamento desenvolvido para capacitar servidores da Prefeitura de Santa Rosa.

## 🎯 Funcionalidades

### Para Gestores
- ✅ Painel administrativo completo
- ✅ Criação e gerenciamento de cursos
- ✅ Adição de videoaulas do YouTube via iframe
- ✅ Upload e gerenciamento de documentos
- ✅ Criação de questionários interativos
- ✅ Relatórios de progresso dos colaboradores
- ✅ Gerenciamento de usuários

### Para Colaboradores
- ✅ Visualização de cursos disponíveis
- ✅ Acesso a videoaulas do YouTube
- ✅ Download de materiais de apoio
- ✅ Resolução de questionários
- ✅ Acompanhamento de progresso
- ✅ Perfil personalizado

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework CSS**: Bootstrap 5
- **Ícones**: Font Awesome 6
- **Servidor**: Apache/Nginx (WAMP/XAMPP)

## 📋 Pré-requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- WAMP, XAMPP ou similar (para desenvolvimento local)

## 🚀 Instalação

### 1. Clone o repositório
```bash
git clone https://github.com/danielmcafee/plataformatreinamento.git
cd plataformatreinamento
```

### 2. Configure o banco de dados
1. Acesse o phpMyAdmin ou seu gerenciador MySQL
2. Crie um banco de dados chamado `plataforma_treinamento`
3. Execute o arquivo `sql/database.sql` no banco criado

### 3. Execute a configuração automática
1. Acesse `http://localhost/plataformatreinamento/setup.php`
2. Aguarde a configuração automática do banco
3. **IMPORTANTE**: Delete o arquivo `setup.php` após a configuração

### 4. Acesse a plataforma
- URL: `http://localhost/plataformatreinamento`
- **Usuário administrador padrão:**
  - Email: `admin@prefeitura.santarosa.rs.gov.br`
  - Senha: `password`

## 📁 Estrutura do Projeto

```
plataformatreinamento/
├── admin/                 # Painel administrativo
│   ├── index.php         # Dashboard
│   └── cursos.php        # Gerenciar cursos
├── auth/                 # Sistema de autenticação
│   ├── login.php         # Página de login
│   ├── cadastro.php      # Página de cadastro
│   └── logout.php        # Logout
├── assets/               # Recursos estáticos
│   ├── css/              # Estilos CSS
│   └── js/               # Scripts JavaScript
├── config/               # Configurações
│   └── database.php      # Conexão com banco
├── includes/             # Arquivos incluídos
│   ├── header.php        # Cabeçalho
│   └── footer.php        # Rodapé
├── sql/                  # Scripts SQL
│   └── database.sql      # Estrutura do banco
├── index.php             # Página inicial
├── curso.php             # Visualização de curso
├── cursos.php            # Lista de cursos
├── perfil.php            # Perfil do usuário
└── setup.php             # Configuração inicial
```

## 🎓 Como Usar

### Para Gestores

1. **Faça login** com as credenciais de administrador
2. **Acesse o painel administrativo** através do menu
3. **Crie cursos**:
   - Clique em "Novo Curso"
   - Preencha título e descrição
   - Defina o status (ativo/inativo)
4. **Adicione aulas**:
   - Acesse "Aulas" no menu
   - Selecione o curso
   - Adicione videoaulas do YouTube, documentos ou questionários
5. **Monitore progresso** através dos relatórios

### Para Colaboradores

1. **Cadastre-se** na plataforma
2. **Navegue pelos cursos** disponíveis
3. **Inscreva-se** nos cursos de interesse
4. **Assista às aulas** e complete os questionários
5. **Acompanhe seu progresso** no perfil

## 🔧 Configuração Avançada

### Personalização Visual
- Edite `assets/css/style.css` para personalizar cores e estilos
- Modifique `includes/header.php` para alterar o cabeçalho
- Atualize `includes/footer.php` para personalizar o rodapé

### Configuração do Banco
- Edite `config/database.php` para alterar credenciais
- Modifique `sql/database.sql` para adicionar campos personalizados

## 🔒 Segurança

- ✅ Senhas criptografadas com `password_hash()`
- ✅ Validação de entrada em todos os formulários
- ✅ Proteção contra SQL Injection com prepared statements
- ✅ Verificação de sessão em páginas protegidas
- ✅ Sanitização de dados de saída

## 📊 Recursos Implementados

### Sistema de Cursos
- Criação e edição de cursos
- Status ativo/inativo
- Controle de acesso por gestor

### Sistema de Aulas
- Videoaulas do YouTube via iframe
- Upload de documentos
- Questionários interativos
- Ordenação sequencial

### Sistema de Usuários
- Login e cadastro
- Perfis de gestor e colaborador
- Gerenciamento de perfil
- Estatísticas pessoais

### Interface Responsiva
- Design moderno e profissional
- Compatível com dispositivos móveis
- Navegação intuitiva
- Feedback visual para ações

## 🐛 Solução de Problemas

### Erro de Conexão com Banco
- Verifique se o MySQL está rodando
- Confirme as credenciais em `config/database.php`
- Execute o script `sql/database.sql`

### Página em Branco
- Verifique os logs de erro do PHP
- Confirme se todas as extensões necessárias estão instaladas
- Verifique permissões de arquivo

### Problemas de Upload
- Verifique permissões da pasta de uploads
- Confirme configuração `upload_max_filesize` no PHP

## 📞 Suporte

Para suporte técnico ou dúvidas sobre a plataforma, entre em contato com a equipe de TI da Prefeitura de Santa Rosa.

## 📄 Licença

Este projeto foi desenvolvido para uso interno da Prefeitura de Santa Rosa. Todos os direitos reservados.

---

**Desenvolvido com ❤️ para a Prefeitura de Santa Rosa**
