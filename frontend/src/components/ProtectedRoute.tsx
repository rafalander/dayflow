import { Navigate, Outlet } from 'react-router-dom'
import { useAuthStore } from '@/store/authStore'
import { useAuth } from '@/hooks'
import { Loader } from 'lucide-react'

export default function ProtectedRoute() {
  const token = useAuthStore((state) => state.token)
  const { meQuery } = useAuth()

  if (!token) {
    return <Navigate to="/auth/login" replace />
  }

  if (meQuery.isPending) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <Loader className="w-8 h-8 animate-spin text-primary" />
      </div>
    )
  }

  if (meQuery.isError) {
    return <Navigate to="/auth/login" replace />
  }

  return <Outlet />
}
