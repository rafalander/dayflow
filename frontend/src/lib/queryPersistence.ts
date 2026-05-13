import { createSyncStoragePersister } from '@tanstack/query-sync-storage-persister'
import type { Query } from '@tanstack/react-query'
import type { PersistQueryClientOptions } from '@tanstack/react-query-persist-client'

export const QUERY_PERSIST_KEY = 'dayflow-react-query-v2'

export function shouldDehydrateQuery(query: Query): boolean {
  if (query.state.status !== 'success') return false
  const k = query.queryKey
  if (!Array.isArray(k) || k.length === 0) return false
  const root = k[0]
  if (typeof root !== 'string') return false
  if (root === 'auth') return false
  if (root === 'approvals') return false
  if (root === 'users') {
    return k[1] === 'directory'
  }
  if (root === 'vacation-requests') {
    const sub = k[1]
    return sub === 'team-stats' || sub === 'upcoming-absences'
  }
  if (root === 'dashboard') {
    return k[1] === 'monthly-overview'
  }
  if (root === 'teams') {
    return k.length === 1 || typeof k[1] === 'number'
  }
  return ['absence-types', 'cargos', 'roles'].includes(root)
}

const persister = createSyncStoragePersister({
  storage: window.localStorage,
  key: QUERY_PERSIST_KEY,
  throttleTime: 1000,
})

export const persistOptions: Pick<
  PersistQueryClientOptions,
  'persister' | 'maxAge' | 'dehydrateOptions'
> = {
  persister,
  maxAge: 1000 * 60 * 60 * 24,
  dehydrateOptions: {
    shouldDehydrateQuery,
  },
}

export function clearPersistedQueryCache(): void {
  try {
    localStorage.removeItem(QUERY_PERSIST_KEY)
  } catch {
    /* ignore */
  }
}
