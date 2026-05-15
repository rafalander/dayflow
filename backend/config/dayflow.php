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

        'default' => env('DAYFLOW_CARGO_DEFAULT_SLUG', 'dayflow-sys-colaborador')

    ],



    /*

    |--------------------------------------------------------------------------

    | Próximas ausências — horizonte (dias)

    |--------------------------------------------------------------------------

    |

    | Padrão quando não existir registro em `settings` (mesma chave que

    | UpcomingAbsencesService::SETTING_KEY). Admins alteram na UI Configurações ou via API de settings.

    |

    */

    'dashboard_upcoming_absences_days' => (int) env('DAYFLOW_DASHBOARD_UPCOMING_ABSENCES_DAYS', 30),



    /*

    |--------------------------------------------------------------------------

    | Tipos de ausência (slug => rótulo)

    |--------------------------------------------------------------------------

    |

    | Estenda esta lista conforme a política da empresa; slugs estáveis para API e relatórios.

    |

    */

    'absence_types' => [

        'vacation' => 'Férias',

        'day_off' => 'Day off',

        'bank_hours' => 'Folga (banco de horas)',

        'medical' => 'Ausência médica',

        'personal' => 'Ausência pessoal',

        'other' => 'Outro',

    ],

    'api_read_cache' => [

        'vacation_calendar' => (int) env('DAYFLOW_CACHE_VACATION_CALENDAR', 300),

        'team_stats' => (int) env('DAYFLOW_CACHE_TEAM_STATS', 120),

        'dashboard_overview' => (int) env('DAYFLOW_CACHE_DASHBOARD_OVERVIEW', 180),

        'upcoming_absences' => (int) env('DAYFLOW_CACHE_UPCOMING_ABSENCES', 180),

        'users_directory' => (int) env('DAYFLOW_CACHE_USERS_DIRECTORY', 240),

        'teams_index' => (int) env('DAYFLOW_CACHE_TEAMS_INDEX', 300),

        'cargos_index' => (int) env('DAYFLOW_CACHE_CARGOS_INDEX', 600),

        'absence_types' => (int) env('DAYFLOW_CACHE_ABSENCE_TYPES', 86400),

        'roles' => (int) env('DAYFLOW_CACHE_ROLES', 86400),

    ],

];

