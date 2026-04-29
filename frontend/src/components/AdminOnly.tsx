import type { ReactNode } from 'react'
import { Navigate } from 'react-router-dom'
import { Loader } from 'lucide-react'
import { useAuth } from '@/hooks'

/** Conteúdo só para utilizadores com role `admin` (nível hierárquico separado). */
export default function AdminOnly({ children }: { children: ReactNode }) {
  const { meQuery } = useAuth()

  if (meQuery.isPending) {
    return (
      <div className="flex min-h-[240px] items-center justify-center">
        <Loader className="h-9 w-9 animate-spin text-primary" />
      </div>
    )
  }

  if (meQuery.data?.role !== 'admin') {
    return <Navigate to="/dashboard" replace />
  }

  return <>{children}</>
}
