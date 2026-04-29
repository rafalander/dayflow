# Dayflow - Sistema de Gestão de Férias

Plataforma moderna e escalável para gerenciamento de férias corporativas com integração Google OAuth, aprovações hierárquicas e relatórios em PDF/Excel.

## 🎯 Funcionalidades

- ✅ **Autenticação Google OAuth 2.0** - Login seguro com contas corporativas
- ✅ **Gestão de Férias** - Solicitar, aprovar e rastrear férias
- ✅ **Hierarquia Organizacional** - Estrutura dinâmica de organização
- ✅ **Aprovações Automáticas** - Fluxo de aprovação baseado em hierarquia
- ✅ **Relatórios** - Exportar para PDF e Excel
- ✅ **Notificações** - Email automáticas + Slack (preparado)
- ✅ **Logs de Auditoria** - Rastreamento de todas as ações
- ✅ **Calendário de Férias** - Visualização de equipes ausentes
- ✅ **API RESTful** - Bem documentada e escalável

## 🧩 Stack Tecnológico

### Backend
- **Laravel 13** - Framework PHP moderno
- **MySQL 8.4** - Banco de dados relacional
- **Laravel Sanctum** - Autenticação API com tokens
- **Laravel Socialite** - Google OAuth
- **Redis** - Cache e sessões
- **PHP 8.3** - Versão estável

### Frontend
- **React 18** - UI interativa
- **TypeScript** - Type safety
- **Vite** - Build tool rápido
- **Tailwind CSS** - Styling moderno
- **TanStack Query** - Data fetching
- **Zustand** - State management
- **React Router** - Navigation

### Infraestrutura
- **Docker & Docker Compose** - Containerização completa
- **Nginx** - Reverse proxy
- **Mailpit** - Email testing

## 📋 Pré-requisitos

- Docker e Docker Compose
- Google OAuth credentials (@uello.com.br)
- Node.js 22+ (para desenvolvimento local)
- PHP 8.3+ (para desenvolvimento local)
- Composer (para desenvolvimento local)

## 🚀 Quick Start

### 1. Clone o repositório

```bash
cd dayflow
```

### 2. Configure as variáveis de ambiente

Copie e configure o `.env` do backend:

```bash
cd backend
cp .env.example .env
# Edite .env com suas credenciais Google
cd ..
```

### 3. Inicie os containers

```bash
docker-compose up -d
```

Isso iniciará:
- MySQL (porta 3306)
- Backend Laravel (porta 8000)
- Frontend React (porta 3000)
- Redis (porta 6379)
- Mailpit (porta 8025)

### 4. Configure o banco de dados

```bash
docker-compose exec backend php artisan migrate:fresh --seed
```

Isso criará:
- Tabelas necessárias
- Roles padrão (Admin, Manager, Tech Lead, etc.)
- Configurações iniciais

### 5. Acesse a aplicação

- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **Mailpit (Emails)**: http://localhost:8025
- **Redis**: localhost:6379

## 🔐 Configuração Google OAuth

### Passos para obter credenciais

1. Acesse [Google Cloud Console](https://console.cloud.google.com)
2. Crie um novo projeto
3. Ative a API "Google+ API"
4. Crie credenciais OAuth 2.0 (tipo: Web Application)
5. Adicione URLs autorizadas:
   - `http://localhost:8000`
   - `http://localhost:8000/auth/callback`
   - `http://localhost:3000`

6. Copie o Client ID e Client Secret
7. Configure no `.env`:

```env
GOOGLE_CLIENT_ID=seu_client_id
GOOGLE_CLIENT_SECRET=seu_client_secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/callback
ALLOWED_EMAIL_DOMAINS=@uello.com.br
```

## 📁 Estrutura do Projeto

```
dayflow/
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   ├── Models/
│   │   ├── Services/
│   │   ├── Policies/
│   │   └── Notifications/
│   ├── routes/
│   │   ├── api.php
│   │   └── web.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── .env
├── frontend/                # React App
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── services/
│   │   ├── store/
│   │   ├── hooks/
│   │   ├── types/
│   │   └── App.tsx
│   └── package.json
├── docker/                  # Docker configs
│   ├── php/
│   ├── nginx/
│   └── mysql/
└── docker-compose.yml
```

## 🛠️ Comandos Úteis

### Backend

```bash
# Executar migrations
docker-compose exec backend php artisan migrate

# Seed do banco
docker-compose exec backend php artisan db:seed

# Artisan commands
docker-compose exec backend php artisan tinker
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:cache

# Tests
docker-compose exec backend php artisan test
```

### Frontend

```bash
# Instalar dependências
docker-compose exec frontend npm install

# Build
docker-compose exec frontend npm run build

# Type check
docker-compose exec frontend npm run type-check

# Lint
docker-compose exec frontend npm run lint
```

### Docker

```bash
# Ver logs
docker-compose logs -f backend
docker-compose logs -f frontend

# Parar containers
docker-compose down

# Limpar volumes
docker-compose down -v
```

## 📊 API Endpoints

### Autenticação
- `GET /api/auth/redirect` - Redirecionar para Google OAuth
- `GET /api/auth/callback` - Callback do Google
- `GET /api/me` - Obter usuário atual
- `POST /api/logout` - Logout

### Usuários
- `GET /api/users` - Listar usuários
- `GET /api/users/{id}` - Obter usuário
- `PUT /api/users/{id}` - Atualizar usuário
- `GET /api/organization/tree` - Árvore organizacional
- `GET /api/users/{id}/subordinates` - Subordinados

### Férias
- `GET /api/vacation-requests` - Listar solicitações
- `POST /api/vacation-requests` - Criar solicitação
- `GET /api/vacation-requests/{id}` - Obter solicitação
- `PUT /api/vacation-requests/{id}` - Atualizar solicitação
- `DELETE /api/vacation-requests/{id}` - Deletar solicitação
- `GET /api/vacation-requests/calendar` - Calendário de férias

### Aprovações
- `GET /api/approvals/pending` - Aprovações pendentes
- `POST /api/vacation-requests/{id}/approve` - Aprovar
- `POST /api/vacation-requests/{id}/reject` - Rejeitar

### Relatórios
- `GET /api/reports/vacations` - Relatório de férias
- `GET /api/reports/export-pdf` - Exportar PDF
- `GET /api/reports/export-excel` - Exportar Excel
- `GET /api/reports/audit-logs` - Logs de auditoria

## 🔒 Segurança

- ✅ Validação de domínio email (@uello.com.br)
- ✅ Proteção CSRF
- ✅ Autenticação Sanctum com tokens
- ✅ Autorização baseada em policies
- ✅ SQL Injection protection (Eloquent ORM)
- ✅ XSS protection (React escaping)
- ✅ Logs de auditoria completos
- ✅ Rate limiting preparado

## 📝 Configurações

### Variáveis importantes

```env
# App
APP_NAME=Dayflow
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=dayflow

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

# Email
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

# Slack (opcional)
SLACK_WEBHOOK_URL=...
SLACK_CHANNEL=...

# Configurações
MAX_TEAM_VACATION_PERCENTAGE=30
VACATION_REMINDER_DAYS=7
```

## 🧪 Testes

### Backend

```bash
docker-compose exec backend php artisan test
```

### Frontend

```bash
docker-compose exec frontend npm test
```

## 📚 Documentação

- API Documentation: `/api/docs` (preparado)
- Postman Collection: [Link](./docs/Dayflow.postman_collection.json)
- Architecture: [Link](./docs/ARCHITECTURE.md)

## 🐛 Troubleshooting

### Container não inicia

```bash
docker-compose logs backend
docker-compose logs frontend
```

### Erro de conexão com banco

```bash
# Verificar saúde do MySQL
docker-compose exec mysql mysqladmin ping -u root -p
```

### Cache não funciona

```bash
docker-compose exec backend php artisan cache:clear
```

### Frontend não atualiza

```bash
docker-compose down -v
docker-compose up -d
```

## 📦 Deploy

O projeto está preparado para deploy em:
- Heroku
- AWS
- DigitalOcean
- GCP
- Azure

Veja [DEPLOYMENT.md](./docs/DEPLOYMENT.md) para detalhes.

## 🤝 Contribuindo

Veja [CONTRIBUTING.md](./docs/CONTRIBUTING.md)

## 📄 Licença

MIT License - veja LICENSE para detalhes

## 📞 Suporte

Para suporte, abra uma issue ou entre em contato.

---

Desenvolvido com ❤️ para Uello
