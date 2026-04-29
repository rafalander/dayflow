<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dev password login (email + senha)
    |--------------------------------------------------------------------------
    |
    | Quando true, o endpoint POST /api/auth/dev-login fica disponível para
    | testes automatizados ou fluxos locais sem UI. A UI de login usa Google +
    | área superadmin separada.
    |
    */
    'dev_password_login' => env('DEV_PASSWORD_LOGIN', false),

    /*
    |--------------------------------------------------------------------------
    | Superadmin (primeiro acesso / ajustes de sistema)
    |--------------------------------------------------------------------------
    |
    | Credenciais padrão para o usuário criado em SuperadminSeeder.
    | Sobrescreva via SUPERADMIN_EMAIL e SUPERADMIN_PASSWORD no .env.
    |
    */
    'superadmin' => [
        'email' => env('SUPERADMIN_EMAIL', 'superadmin@uello.com.br'),
        'password' => env('SUPERADMIN_PASSWORD', 'admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hierarquia (nível numérico em users.level)
    |--------------------------------------------------------------------------
    */
    'superadmin_level' => (int) env('SUPERADMIN_LEVEL', 1000),

    'default_user_level' => (int) env('DEFAULT_USER_LEVEL', 20),

];
