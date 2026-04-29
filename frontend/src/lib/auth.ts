import type { User } from '@/types'

/** Administrador = cargo com `role === 'admin'` na tabela de cargos. */
export function isAdminUser(user?: User | null): boolean {
  return user?.cargo?.role === 'admin'
}
