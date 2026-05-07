import type { User } from '@/types'

/** Administrador = cargo com `role === 'admin'` na tabela de cargos. */
export function isAdminUser(user?: User | null): boolean {
  return user?.cargo?.role === 'admin'
}

/** Cartões de resumo no dashboard: admin ou gestor com subordinados diretos (via `/me`). */
export function canViewTeamVacationStats(user?: User | null): boolean {
  if (!user) return false
  if (isAdminUser(user)) return true
  return (user.subordinates?.length ?? 0) > 0
}
