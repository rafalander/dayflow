# 🧪 Guia de Teste Rápido - Dayflow

Como testar o Dayflow localmente após a instalação.

## ✅ Pré-requisitos

- Docker e Docker Compose instalados
- Credenciais Google OAuth configuradas em `.env`
- Aplicação rodando em http://localhost:3000

## 🚀 1. Iniciar a Aplicação

### Windows
```bash
setup.bat
```

### Mac/Linux
```bash
chmod +x setup.sh
./setup.sh
```

### Manual
```bash
docker-compose up -d
docker-compose exec backend php artisan migrate:fresh --seed
```

## 📍 2. Verificar Serviços

Abra seu navegador e verifique:

```
✓ Frontend:  http://localhost:3000
✓ Backend:   http://localhost:8000
✓ Mailpit:   http://localhost:8025
✓ MySQL:     localhost:3306
```

## 🔐 3. Testar Google OAuth

1. Vá para http://localhost:3000
2. Clique em "Entrar com Google"
3. Use sua conta @uello.com.br
4. Você deve ser redirecionado para o dashboard

## 📊 4. Testar API Endpoints

### Obter token (via UI)
1. Faça login no frontend
2. Verifique Network tab no browser DevTools
3. Procure por header `Authorization: Bearer ...`
4. Copie o token

### Testar com cURL ou Postman

```bash
# Obter usuário atual
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/me

# Listar usuários
curl -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/users

# Criar solicitação de férias
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "start_date": "2026-06-01",
    "end_date": "2026-06-10",
    "reason": "Férias planejadas"
  }' \
  http://localhost:8000/api/vacation-requests
```

## 💾 5. Testar Banco de Dados

### Acessar MySQL

```bash
docker-compose exec mysql mysql -u dayflow -pdayflow_password dayflow

# Dentro do MySQL:
SHOW TABLES;
SELECT * FROM users;
SELECT * FROM roles;
```

## 📧 6. Testar Emails

1. Vá para http://localhost:8025
2. Crie uma solicitação de férias
3. Verifique Mailpit para emails enviados

## 🔍 7. Testar Logs

### Backend
```bash
docker-compose logs -f backend
```

### Frontend
```bash
docker-compose logs -f frontend
```

### Abrir Console do Browser
F12 -> Console/Network tabs

## 🧩 8. Testar Componentes

### Dashboard
```
http://localhost:3000/dashboard
```

### Minhas Férias
```
http://localhost:3000/vacations
```

### Aprovações
```
http://localhost:3000/approvals
```

### Relatórios
```
http://localhost:3000/reports
```

### Perfil
```
http://localhost:3000/profile
```

## 🐛 9. Troubleshooting

### "Connection refused"
```bash
docker-compose ps  # Verificar containers rodando
docker-compose logs backend  # Ver logs
```

### "Google OAuth failed"
- Verifique GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET em .env
- Limpe cookies do navegador
- Restart backend: `docker-compose restart backend`

### "Database error"
```bash
docker-compose exec backend php artisan migrate:fresh --seed
```

### "Cache error"
```bash
docker-compose exec backend php artisan cache:clear
```

## ✨ 10. Testes Recomendados

### Fluxo Completo
1. ✅ Fazer login com Google
2. ✅ Acessar dashboard
3. ✅ Criar solicitação de férias
4. ✅ Verificar conflito de equipe
5. ✅ Aprovar/rejeitar como gerente
6. ✅ Visualizar relatório
7. ✅ Fazer logout

### Testes de Segurança
- [ ] Tentar acessar sem token
- [ ] Tentar usar token inválido
- [ ] Tentar acessar recurso de outro usuário
- [ ] Injetar SQL na busca
- [ ] XSS em comentários

## 📈 11. Performance Testing

### Carga básica
```bash
# Com Apache Bench
ab -n 100 -c 10 http://localhost:8000/api/users

# Com wrk
wrk -t12 -c400 -d30s http://localhost:8000/api/users
```

## 📊 12. Checklist Final

- [ ] Frontend carrega sem erros
- [ ] Login com Google funciona
- [ ] Dashboard mostra dados
- [ ] Criar férias funciona
- [ ] Aprovações funcionam
- [ ] Relatórios geram
- [ ] Emails são enviados
- [ ] Logs registram ações
- [ ] API responde corretamente
- [ ] Sem erros 500
- [ ] Performance aceitável

## 🎉 Sucesso!

Se todos os testes passaram, você tem um Dayflow funcionando completamente! 

Próximo passo: Veja [NEXT_STEPS.md](./NEXT_STEPS.md) para melhorias futuras.
