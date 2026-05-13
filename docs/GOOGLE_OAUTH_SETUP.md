# 🔐 Configuração Google OAuth - Dayflow

Este guia detalha como configurar Google OAuth para o Dayflow.

## 📋 Pré-requisitos

- Conta Google Workspace (@uello.com.br)
- Acesso ao Google Cloud Console
- Credenciais de Admin do Google Cloud

## 🔑 Passo 1: Criar um projeto no Google Cloud Console

1. Acesse [Google Cloud Console](https://console.cloud.google.com)
2. Clique no seletor de projeto (canto superior esquerdo)
3. Clique em **NEW PROJECT**
4. Nome: `Dayflow`
5. Clique em **CREATE**

## 🔗 Passo 2: Habilitar APIs necessárias

1. No console, vá para **APIs & Services** > **Enabled APIs & services**
2. Clique em **ENABLE APIS AND SERVICES**
3. Procure por "Google+ API"
4. Clique em **ENABLE**

## 🎫 Passo 3: Criar credenciais OAuth

1. Vá para **APIs & Services** > **Credentials**
2. Clique em **+ CREATE CREDENTIALS**
3. Selecione **OAuth client ID**
4. Se solicitado, configure a tela de consentimento:
   - User Type: Internal
   - Nome do app: Dayflow
   - Email do suporte: seu_email@uello.com.br
   - Escopos: `email`, `profile`
5. De volta a credenciais, selecione **Web application**
6. Configure:

### URIs autorizados

**JavaScript authorized origins:**
```
http://localhost:8000
http://localhost:5173
http://localhost
```

**Authorized redirect URIs:**
```
http://localhost:8000/auth/callback
http://localhost:8000/api/auth/callback
```

7. Clique em **CREATE**
8. Copie o **Client ID** e **Client Secret**

## 🔧 Passo 4: Configurar no backend

1. Abra `backend/.env`:

```env
GOOGLE_CLIENT_ID=SEU_CLIENT_ID_AQUI
GOOGLE_CLIENT_SECRET=SEU_CLIENT_SECRET_AQUI
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/callback
ALLOWED_EMAIL_DOMAINS=@uello.com.br
```

2. Salve o arquivo

## ✅ Passo 5: Testar

1. Reinicie o backend:

```bash
docker-compose restart backend
```

2. Acesse http://localhost:5173
3. Clique em "Entrar com Google"
4. Faça login com sua conta @uello.com.br
5. Você deve ser redirecionado para o dashboard

## 🚀 Passo 6: Deploy (Produção)

Para deploy em produção:

1. Crie um novo projeto no Google Cloud para produção
2. Adicione seus domínios de produção:
   ```
   https://seu-dominio.com
   https://api.seu-dominio.com
   ```

3. Atualize as variáveis de ambiente:
   ```env
   GOOGLE_CLIENT_ID=seu_client_id_producao
   GOOGLE_CLIENT_SECRET=seu_client_secret_producao
   GOOGLE_REDIRECT_URL=https://api.seu-dominio.com/auth/callback
   ALLOWED_EMAIL_DOMAINS=@uello.com.br
   ```

## 🔒 Boas Práticas

- ✅ Nunca commite credenciais no repositório
- ✅ Use variáveis de ambiente
- ✅ Rotação de credenciais a cada 90 dias
- ✅ Monitore uso de OAuth no Google Cloud Console
- ✅ Restrinja domínios por ambiente

## ❓ Troubleshooting

### "Unauthorized domain" error

- Verifique se o domínio está adicionado em **Authorized JavaScript origins**
- Limpe cookies do navegador

### "Invalid redirect_uri"

- Certifique-se que a URL de callback é exatamente igual em ambos os lugares
- Verifique protocolo (http vs https)

### Erro 401 no callback

- Verifique se GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET estão corretos
- Restart o backend
- Limpe o cache: `php artisan config:cache`

## 📚 Documentação Oficial

- [Google OAuth Docs](https://developers.google.com/identity/protocols/oauth2)
- [Laravel Socialite](https://laravel.com/docs/socialite)
