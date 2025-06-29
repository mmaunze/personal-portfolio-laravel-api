# API REST Laravel - Portfolio Pessoal

## Visão Geral

Esta é uma API REST completa desenvolvida em Laravel para gestão de um portfolio pessoal. A API fornece funcionalidades abrangentes para gestão de utilizadores, posts de blog, projetos, downloads e mensagens de contacto, com sistema robusto de autenticação e autorização baseado em roles e permissões.

## Características Principais

### 🔐 **Sistema de Autenticação e Autorização**
- Autenticação JWT usando Laravel Sanctum
- Sistema de roles e permissões com Spatie Permission
- 4 níveis de acesso: Admin, Editor, Author, Viewer
- Controlo granular de permissões por funcionalidade
- Gestão de tokens com refresh e revogação

### 👥 **Gestão de Utilizadores**
- CRUD completo de utilizadores
- Upload de avatar com validação
- Perfis detalhados com informações pessoais
- Estatísticas de atividade por utilizador
- Controlo de status (ativo/inativo)
- Gestão de roles e permissões

### 📝 **Gestão de Posts**
- CRUD completo de posts de blog
- Sistema de categorias e tags
- Controlo de publicação (rascunho/publicado)
- Upload de imagens para posts
- Sistema de visualizações
- Pesquisa e filtros avançados
- Operações em lote

### 🗂️ **Gestão de Downloads**
- CRUD completo de ficheiros para download
- Upload seguro com validação de tipo e tamanho
- Sistema de categorias e tags
- Controlo de acesso (público/requer registo)
- Sistema de destaque
- Estatísticas de downloads
- Operações em lote

### 📧 **Sistema de Contactos**
- Formulário público de contacto
- Gestão de mensagens recebidas
- Sistema de status (novo, lido, respondido, arquivado, spam)
- Proteção anti-spam com rate limiting
- Estatísticas e métricas
- Exportação de dados

## Instalação e Configuração

### Requisitos do Sistema
- PHP 8.1 ou superior
- Composer
- Base de dados (PostgreSQL, MySQL ou SQLite)
- Extensões PHP: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

### Instalação

1. **Clonar/Extrair o projeto**
```bash
git clone https://github.com/mmaunze/personal-portfolio-laravel-api.git
```

```bash
cd personal-portfolio-laravel-api
```

2. **Instalar dependências**
```bash
composer install
```

3. **Configurar ambiente**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de dados no .env**
```env
# Para PostgreSQL (Produção)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=portfolio_api
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

# Para SQLite (Desenvolvimento)
DB_CONNECTION=sqlite
DB_DATABASE=/caminho/absoluto/para/database.sqlite
```

5. **Executar migrações e seeders**
```bash
php artisan migrate --seed
```

6. **Configurar storage**
```bash
php artisan storage:link
```

7. **Iniciar servidor**
```bash
php artisan serve
```

### Utilizadores Padrão

Após executar os seeders, estarão disponíveis:

**Administrador:**
- Email: admin@dominio.com
- Password: admin123

**Editor:**
- Email: editor@dominio.com
- Password: editor123

## Estrutura da API

### Base URL
```
http://localhost:8000/api
```

### Headers Obrigatórios
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token} (para rotas protegidas)
```

## Endpoints da API

### 🔐 Autenticação

#### POST /auth/register
Registo de novo utilizador.

**Body:**
```json
{
  "name": "Nome Completo",
  "email": "email@exemplo.com",
  "password": "senha123",
  "password_confirmation": "senha123"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Utilizador registado com sucesso",
  "data": {
    "user": { ... },
    "token": "token_jwt",
    "token_type": "Bearer"
  }
}
```

#### POST /auth/login
Login de utilizador.

**Body:**
```json
{
  "email": "admin@dominio.com",
  "password": "admin123"
}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Login realizado com sucesso",
  "data": {
    "user": {
      "id": 1,
      "name": "Cesário Machava",
      "email": "admin@dominio.com",
      "roles": [{"name": "admin"}]
    },
    "token": "token_jwt",
    "token_type": "Bearer",
    "stats": { ... }
  }
}
```

#### GET /auth/me
Obter informações do utilizador autenticado.

#### PUT /auth/profile
Atualizar perfil do utilizador autenticado.

#### POST /auth/logout
Logout (revoga o token atual).

#### POST /auth/logout-all
Logout de todos os dispositivos (revoga todos os tokens).

### 👥 Gestão de Utilizadores (Admin apenas)

#### GET /users
Listar utilizadores com filtros e paginação.

**Query Parameters:**
- `search`: Pesquisa por nome, email ou bio
- `role`: Filtrar por role (admin, editor, author, viewer)
- `status`: Filtrar por status (active, inactive)
- `sort_by`: Campo para ordenação (default: created_at)
- `sort_order`: Ordem (asc, desc)
- `per_page`: Itens por página (default: 15)

#### POST /users
Criar novo utilizador.

**Body:**
```json
{
  "name": "Nome do Utilizador",
  "email": "email@exemplo.com",
  "password": "senha123",
  "password_confirmation": "senha123",
  "bio": "Biografia opcional",
  "phone": "123456789",
  "website": "https://exemplo.com",
  "location": "Lisboa, Portugal",
  "role": "editor",
  "is_active": true
}
```

#### GET /users/{id}
Obter detalhes de um utilizador específico.

#### PUT /users/{id}
Atualizar utilizador.

#### DELETE /users/{id}
Eliminar utilizador.

#### PATCH /users/{id}/toggle-status
Alternar status ativo/inativo do utilizador.

#### GET /users-roles
Obter lista de roles disponíveis.

#### GET /users-stats
Obter estatísticas de utilizadores.

### 📝 Gestão de Posts

#### GET /posts
Listar posts com filtros e paginação.

**Query Parameters:**
- `search`: Pesquisa por título, conteúdo ou autor
- `category`: Filtrar por categoria
- `author`: Filtrar por autor
- `status`: Filtrar por status (published, draft)
- `tags`: Filtrar por tags (separadas por vírgula)
- `date_from`: Data inicial (YYYY-MM-DD)
- `date_to`: Data final (YYYY-MM-DD)
- `sort_by`: Campo para ordenação
- `sort_order`: Ordem (asc, desc)
- `per_page`: Itens por página

#### POST /posts
Criar novo post.

**Body:**
```json
{
  "title": "Título do Post",
  "slug": "titulo-do-post",
  "excerpt": "Resumo do post",
  "full_content": "Conteúdo completo em HTML",
  "author": "Nome do Autor",
  "publish_date": "2024-01-01",
  "category": "Tecnologia",
  "tags": ["laravel", "php", "api"],
  "image_url": "https://exemplo.com/imagem.jpg",
  "is_published": true
}
```

#### GET /posts/{id}
Obter detalhes de um post (por ID ou slug).

#### PUT /posts/{id}
Atualizar post.

#### DELETE /posts/{id}
Eliminar post.

#### PATCH /posts/{id}/toggle-published
Alternar status de publicação do post.

#### POST /posts/bulk-action
Operações em lote nos posts.

**Body:**
```json
{
  "action": "publish", // publish, unpublish, delete
  "post_ids": [1, 2, 3]
}
```

#### GET /posts-stats
Obter estatísticas de posts.

#### GET /posts-categories
Obter lista de categorias.

#### GET /posts-tags
Obter lista de tags.

### 🗂️ Gestão de Downloads

#### GET /downloads
Listar downloads com filtros.

**Query Parameters:**
- `search`: Pesquisa por título ou descrição
- `category`: Filtrar por categoria
- `file_type`: Filtrar por tipo de ficheiro
- `author`: Filtrar por autor
- `status`: Filtrar por status (published, draft)
- `featured`: Filtrar por destaque (true, false)
- `requires_registration`: Filtrar por requisito de registo

#### POST /downloads
Criar novo download.

**Body (multipart/form-data):**
```
title: Título do Download
description: Descrição opcional
file: [arquivo]
category: Categoria
tags: ["tag1", "tag2"]
author: Nome do Autor
version: 1.0
is_featured: true
is_published: true
requires_registration: false
```

#### GET /downloads/{id}
Obter detalhes de um download.

#### PUT /downloads/{id}
Atualizar download.

#### DELETE /downloads/{id}
Eliminar download.

#### GET /downloads/{id}/download
Download público do ficheiro.

#### PATCH /downloads/{id}/toggle-published
Alternar status de publicação.

#### PATCH /downloads/{id}/toggle-featured
Alternar status de destaque.

#### POST /downloads/bulk-action
Operações em lote.

#### GET /downloads-stats
Obter estatísticas de downloads.

#### GET /downloads-categories
Obter lista de categorias.

### 📧 Gestão de Contactos

#### POST /contact (Público)
Enviar mensagem de contacto.

**Body:**
```json
{
  "name": "Nome Completo",
  "email": "email@exemplo.com",
  "phone": "123456789",
  "company": "Empresa (opcional)",
  "subject": "Assunto da mensagem",
  "message": "Conteúdo da mensagem"
}
```

#### GET /contacts
Listar mensagens de contacto.

**Query Parameters:**
- `search`: Pesquisa por nome, email ou assunto
- `status`: Filtrar por status (new, read, replied, archived, spam)
- `date_from`: Data inicial
- `date_to`: Data final
- `recent_days`: Filtrar por dias recentes

#### GET /contacts/{id}
Obter detalhes de uma mensagem (marca como lida automaticamente).

#### PUT /contacts/{id}
Atualizar status da mensagem.

**Body:**
```json
{
  "status": "replied",
  "notes": "Notas internas (opcional)"
}
```

#### DELETE /contacts/{id}
Eliminar mensagem.

#### PATCH /contacts/{id}/mark-read
Marcar como lida.

#### PATCH /contacts/{id}/mark-replied
Marcar como respondida.

#### PATCH /contacts/{id}/mark-spam
Marcar como spam.

#### PATCH /contacts/{id}/archive
Arquivar mensagem.

#### POST /contacts/bulk-action
Operações em lote.

#### GET /contacts-stats
Obter estatísticas de contactos.

#### GET /contacts-export
Exportar contactos em formato CSV.

## Sistema de Roles e Permissões

### Roles Disponíveis

#### Admin
- Acesso total ao sistema
- Gestão de utilizadores, roles e permissões
- Todas as operações CRUD
- Acesso a estatísticas e relatórios

#### Editor
- Gestão completa de conteúdo (posts, projetos, downloads)
- Visualização e resposta a contactos
- Acesso a estatísticas de conteúdo
- Não pode gerir utilizadores

#### Author
- Criação e edição do próprio conteúdo
- Visualização de conteúdo público
- Acesso limitado a estatísticas

#### Viewer
- Apenas visualização de conteúdo
- Acesso ao dashboard básico

### Permissões Detalhadas

**Posts:**
- view-posts, create-posts, edit-posts, delete-posts, publish-posts

**Projetos:**
- view-projects, create-projects, edit-projects, delete-projects, publish-projects

**Downloads:**
- view-downloads, create-downloads, edit-downloads, delete-downloads, publish-downloads

**Contactos:**
- view-contacts, reply-contacts, delete-contacts, manage-contacts

**Utilizadores:**
- view-users, create-users, edit-users, delete-users, manage-roles

**Sistema:**
- view-dashboard, manage-settings, view-analytics

## Tratamento de Erros

### Códigos de Status HTTP

- `200`: Sucesso
- `201`: Criado com sucesso
- `400`: Pedido inválido
- `401`: Não autorizado
- `403`: Proibido
- `404`: Não encontrado
- `422`: Dados de validação inválidos
- `429`: Muitos pedidos (rate limiting)
- `500`: Erro interno do servidor

### Formato de Resposta de Erro

```json
{
  "success": false,
  "message": "Descrição do erro",
  "errors": {
    "campo": ["Mensagem de validação"]
  }
}
```

## Segurança

### Medidas Implementadas

1. **Autenticação JWT** com tokens seguros
2. **Rate Limiting** para prevenir spam
3. **Validação rigorosa** de todos os inputs
4. **Sanitização** de dados de entrada
5. **Upload seguro** de ficheiros com validação
6. **Controlo de acesso** baseado em permissões
7. **Logs de auditoria** para ações críticas
8. **Proteção CSRF** habilitada
9. **Headers de segurança** configurados

### Rate Limiting

- **Contactos**: 3 por hora por IP, 5 por dia por email
- **Login**: 5 tentativas por minuto
- **API Geral**: 60 pedidos por minuto por utilizador

## Performance e Otimização

### Características de Performance

1. **Paginação** em todas as listagens
2. **Eager Loading** para relacionamentos
3. **Cache** de consultas frequentes
4. **Índices** de base de dados otimizados
5. **Compressão** de respostas
6. **Lazy Loading** de recursos pesados

### Recomendações para Produção

1. **Cache Redis** para sessões e cache
2. **CDN** para ficheiros estáticos
3. **Load Balancer** para múltiplas instâncias
4. **Monitoring** com logs estruturados
5. **Backup** automatizado da base de dados

## Deployment

### Configuração de Produção

1. **Ambiente de Produção**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=

DB_CONNECTION=pgsql
DB_HOST=seu_host_postgresql
DB_DATABASE=portfolio_api_prod
DB_USERNAME=usuario_prod
DB_PASSWORD=senha_segura

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

2. **Comandos de Deploy**
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan storage:link
```

3. **Configuração do Servidor Web**
- Nginx ou Apache configurado para Laravel
- SSL/TLS habilitado
- Gzip compression ativada
- Headers de segurança configurados

### Docker (Opcional)

Incluído Dockerfile para containerização:

```bash
docker build -t portfolio-api .
docker run -p 8000:8000 portfolio-api
```

## Testes

### Executar Testes

```bash
php artisan test
```

### Cobertura de Testes

- Testes unitários para modelos
- Testes de funcionalidade para controladores
- Testes de integração para API
- Testes de autenticação e autorização

## Monitorização e Logs

### Logs Disponíveis

- **Autenticação**: Login, logout, tentativas falhadas
- **Operações CRUD**: Criação, edição, eliminação
- **Uploads**: Ficheiros enviados e processados
- **Erros**: Exceções e erros do sistema
- **Performance**: Consultas lentas e métricas

### Métricas Importantes

- Número de utilizadores ativos
- Posts mais visualizados
- Downloads mais populares
- Taxa de resposta a contactos
- Tempo médio de resposta da API

## Suporte e Manutenção

### Atualizações Regulares

- Atualizações de segurança do Laravel
- Patches de dependências
- Melhorias de performance
- Novas funcionalidades

### Backup e Recuperação

- Backup diário da base de dados
- Backup semanal de ficheiros
- Testes regulares de recuperação
- Documentação de procedimentos

## Conclusão

Esta API Laravel fornece uma base sólida e escalável para um portfolio pessoal, com todas as funcionalidades necessárias para gestão de conteúdo, utilizadores e interações. A arquitetura modular permite fácil extensão e manutenção, enquanto as medidas de segurança garantem proteção adequada dos dados.

Para suporte técnico ou questões sobre implementação, consulte a documentação oficial do Laravel ou contacte a equipa de desenvolvimento.

