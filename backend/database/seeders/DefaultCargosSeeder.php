<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Seeder;

/**
 * Cargos de negócio padrão (tabela positions).
 * Níveis dimensionados para ficarem abaixo do cargo de sistema superadmin (1000).
 */
class DefaultCargosSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['name' => 'Diretor(a)', 'slug' => 'diretor', 'role' => 'admin', 'level' => 10, 'description' => 'Cargo padrão — direção'],
            ['name' => 'Gerente', 'slug' => 'gerente', 'role' => 'admin', 'level' => 8, 'description' => 'Cargo padrão — gerência'],
            ['name' => 'Coordenador(a)', 'slug' => 'coordenador', 'role' => 'admin', 'level' => 5, 'description' => 'Cargo padrão — coordenação'],
            ['name' => 'Tech Lead', 'slug' => 'tech-lead', 'role' => 'admin', 'level' => 4, 'description' => 'Cargo padrão — tech lead'],
            ['name' => 'Desenvolvedor(a)', 'slug' => 'desenvolvedor', 'role' => 'user', 'level' => 2, 'description' => 'Cargo padrão — desenvolvimento'],
            ['name' => 'Analista', 'slug' => 'analista', 'role' => 'user', 'level' => 1, 'description' => 'Cargo padrão — análise'],
        ];

        foreach ($rows as $row) {
            Cargo::query()->updateOrInsert(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'role' => $row['role'],
                    'level' => $row['level'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
