import type { ReactNode } from 'react'
import { Loader } from 'lucide-react'
import { useAuth } from '@/hooks'
import { isAdminUser } from '@/lib/auth'

/** Conteúdo só para utilizadores cujo cargo tem `role === 'admin'`. */
export default function AdminOnly({ children }: { children: ReactNode }) {
  const { meQuery } = useAuth()

  if (meQuery.isPending) {
    return (
      <div className="flex min-h-[240px] items-center justify-center">
        <Loader className="h-8 w-8 animate-spin text-primary" />
      </div>
    )
  }

  if (!isAdminUser(meQuery.data)) {
    return (
      <div className="rounded-lg border border-amber-100 bg-amber-50 p-6 text-amber-900">
        <p className="font-medium">Acesso restrito</p>
        <p className="mt-1 text-sm">Esta área é apenas para administradores (cargo administrativo).</p>
      </div>
    )
  }

  return <>{children}</>
}
