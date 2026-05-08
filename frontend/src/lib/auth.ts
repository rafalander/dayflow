import type { User } from '@/types'

export function isAdminUser(user?: User | null): boolean {
  return user?.cargo?.role === 'admin'
}

export function canViewTeamVacationStats(user?: User | null): boolean {
  if (!user) return false
  if (isAdminUser(user)) return true
  const n = user.subordinates_count ?? user.subordinates?.length ?? 0
  return n > 0
}
