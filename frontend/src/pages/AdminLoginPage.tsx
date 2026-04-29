import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { isAxiosError } from 'axios'
import { useAuthStore } from '@/store/authStore'
import { authService } from '@/services'
import { Loader } from 'lucide-react'

export default function AdminLoginPage() {
  const navigate = useNavigate()
  const setToken = useAuthStore((state) => state.setToken)
  const setUser = useAuthStore((state) => state.setUser)
  const [email, setEmail] = useState('superadmin@uello.com.br')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const { token, user } = await authService.superadminLogin(email.trim(), password)
      setToken(token)
      setUser(user)
      navigate('/dashboard', { replace: true })
    } catch (err: unknown) {
      if (isAxiosError(err) && err.response?.data && typeof err.response.data === 'object') {
        const d = err.response.data as { message?: string; errors?: Record<string, string[]> }
        const first =
          d.errors &&
          Object.values(d.errors)
            .flat()
            .find(Boolean)
        setError(first ?? d.message ?? 'Credenciais inválidas.')
      } else {
        setError('Não foi possível entrar.')
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="relative flex min-h-screen flex-col bg-gray-100">
      <Link
        to="/auth/login"
        className="absolute left-4 top-4 text-sm text-gray-500 underline-offset-4 hover:text-gray-700 hover:underline"
      >
        ← Voltar ao login
      </Link>

      <div className="flex flex-1 items-center justify-center p-4">
        <div className="w-full max-w-sm rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
          <h1 className="text-center text-lg font-semibold text-gray-800">Acesso administrativo</h1>
          <p className="mt-1 text-center text-xs text-gray-500">Uso restrito para configuração inicial do sistema</p>

          <form onSubmit={handleSubmit} className="mt-6 space-y-4">
            <div>
              <label htmlFor="admin-email" className="block text-xs font-medium text-gray-600">
                E-mail
              </label>
              <input
                id="admin-email"
                type="email"
                autoComplete="username"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                required
              />
            </div>
            <div>
              <label htmlFor="admin-password" className="block text-xs font-medium text-gray-600">
                Senha
              </label>
              <input
                id="admin-password"
                type="password"
                autoComplete="current-password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                required
              />
            </div>
            {error && (
              <p className="text-xs text-red-600" role="alert">
                {error}
              </p>
            )}
            <button
              type="submit"
              disabled={loading}
              className="flex w-full items-center justify-center rounded-lg bg-gray-800 py-2.5 text-sm font-semibold text-white hover:bg-gray-900 disabled:opacity-60"
            >
              {loading ? <Loader className="h-4 w-4 animate-spin" /> : 'Entrar'}
            </button>
          </form>
        </div>
      </div>
    </div>
  )
}
