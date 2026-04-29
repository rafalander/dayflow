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
    | Cargos de sistema (positions.slug) — hierarquia só via cargo
    |--------------------------------------------------------------------------
    */
    'system_cargo_slugs' => [
        'superadmin' => env('DAYFLOW_CARGO_SUPERADMIN_SLUG', 'dayflow-sys-superadmin'),
        'default' => env('DAYFLOW_CARGO_DEFAULT_SLUG', 'dayflow-sys-colaborador'),
        'dev_admin' => env('DAYFLOW_CARGO_DEV_ADMIN_SLUG', 'dayflow-sys-dev-admin'),
    ],

];
