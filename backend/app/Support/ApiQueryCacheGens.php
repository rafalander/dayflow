<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class ApiQueryCacheGens
{
    private const VACATION = 'gen_vacation_read';

    private const USER_DIRECTORY = 'gen_user_directory';

    private const TEAMS_INDEX = 'gen_teams_index';

    private const CARGOS_INDEX = 'gen_cargos_index';

    public static function vacation(): int
    {
        return (int) Cache::get(self::VACATION, 1);
    }

    public static function bumpVacation(): void
    {
        self::bump(self::VACATION);
    }

    public static function userDirectory(): int
    {
        return (int) Cache::get(self::USER_DIRECTORY, 1);
    }

    public static function bumpUserDirectory(): void
    {
        self::bump(self::USER_DIRECTORY);
    }

    public static function teams(): int
    {
        return (int) Cache::get(self::TEAMS_INDEX, 1);
    }

    public static function bumpTeams(): void
    {
        self::bump(self::TEAMS_INDEX);
    }

    public static function cargos(): int
    {
        return (int) Cache::get(self::CARGOS_INDEX, 1);
    }

    public static function bumpCargos(): void
    {
        self::bump(self::CARGOS_INDEX);
    }

    private static function bump(string $key): void
    {
        $v = (int) Cache::get($key, 1);
        Cache::forever($key, $v + 1);
    }
}
