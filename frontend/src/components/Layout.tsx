import { useMemo, useState } from 'react'
import { Outlet, useNavigate, useLocation } from 'react-router-dom'
import { Menu, X, LogOut, Calendar, CheckCircle, BarChart3, Users, Briefcase, Network, Settings } from 'lucide-react'
import UserAvatar from '@/components/UserAvatar'
import { useAuthStore } from '@/store/authStore'
import { useAuth } from '@/hooks'
import { isAdminUser } from '@/lib/auth'

function BrandWordmark({ collapsed }: { collapsed: boolean }) {
  if (collapsed) {
    return (
      <div className="box-border flex aspect-square h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-[11px] font-bold leading-none text-primary">
        DF
      </div>
    )
  }
  return (
    <div className="min-w-0">
      <p className="font-bold leading-tight text-primary text-lg">Dayflow</p>
    </div>
  )
}

export default function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(true)
  const navigate = useNavigate()
  const location = useLocation()
  const { meQuery, logoutMutation } = useAuth()
  const logout = useAuthStore((state) => state.logout)

  const user = meQuery.data

  const menuItems = useMemo(() => {
    const base: { label: string; path: string; icon: typeof BarChart3; matchPrefix?: string }[] = [
      { label: 'Dashboard', path: '/dashboard', icon: BarChart3 },
      { label: 'Ausências', path: '/ausencias', icon: Calendar },
      { label: 'Aprovações', path: '/approvals', icon: CheckCircle },
      { label: 'Relatórios', path: '/reports', icon: BarChart3 },
    ]
    if (isAdminUser(user)) {
      base.push(
        { label: 'Usuários', path: '/users', icon: Users },
        { label: 'Cargos', path: '/cargos', icon: Briefcase },
        { label: 'Times', path: '/teams', icon: Network, matchPrefix: '/teams' },
      )
      base.push({ label: 'Configurações', path: '/settings', icon: Settings })
    }
    return base
  }, [user])

  const handleLogout = async () => {
    try {
      await logoutMutation.mutateAsync()
      logout()
      navigate('/auth/login')
    } catch (error) {
      console.error('Logout failed:', error)
    }
  }

  const isActive = (path: string, matchPrefix?: string) => {
    if (matchPrefix) {
      return location.pathname === path || location.pathname.startsWith(`${matchPrefix}/`)
    }
    return location.pathname === path
  }

  return (
    <div className="flex h-screen bg-gray-50">
      <aside
        className={`flex shrink-0 flex-col border-r border-gray-200 bg-white transition-[width] duration-300 ${
          sidebarOpen ? 'w-64' : 'w-24'
        }`}
      >
        <div
          className={`flex shrink-0 border-b border-gray-200 bg-white ${
            sidebarOpen
              ? 'h-16 flex-row items-center justify-between gap-2 px-3'
              : 'flex-col items-center gap-2 px-2 py-3'
          }`}
        >
          <div
            className={
              sidebarOpen ? 'flex min-w-0 flex-1 items-center overflow-hidden' : 'flex justify-center'
            }
          >
            <BrandWordmark collapsed={!sidebarOpen} />
          </div>
          <button
            type="button"
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg hover:bg-gray-100"
            aria-label={sidebarOpen ? 'Recolher menu' : 'Expandir menu'}
          >
            {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
          </button>
        </div>

        <nav className="space-y-2 p-4">
          {menuItems.map((item) => {
            const Icon = item.icon
            return (
              <button
                key={item.path}
                type="button"
                onClick={() => navigate(item.path)}
                className={`flex w-full items-center space-x-3 rounded px-4 py-2 transition ${
                  isActive(item.path, item.matchPrefix)
                    ? 'bg-primary text-white'
                    : 'text-gray-600 hover:bg-gray-100'
                }`}
              >
                <Icon size={20} />
                <span className={!sidebarOpen ? 'hidden' : ''}>{item.label}</span>
              </button>
            )
          })}
        </nav>
      </aside>

      <main className="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden bg-gray-50">
        <header className="flex h-16 shrink-0 items-center border-b border-gray-200 bg-white px-6">
          <div className="flex w-full items-center justify-between">
            <div className="min-w-0">
              <p className="font-bold text-primary text-lg">Dayflow</p>
              <p className="text-xs text-gray-500">Gestão de Férias</p>
            </div>

            <div className="flex items-center gap-3">
              {user && (
                <button
                  type="button"
                  onClick={() => navigate('/profile')}
                  className="flex min-w-0 max-w-[min(100%,280px)] items-center gap-3 rounded-lg px-2 py-1.5 text-left transition hover:bg-gray-100"
                  title="Meu perfil"
                >
                  <UserAvatar name={user.name} src={user.display_avatar} size="sm" />
                  <div className="min-w-0">
                    <p className="truncate font-medium text-gray-800">{user.name}</p>
                    <p className="truncate text-sm text-gray-500">{user.cargo?.name ?? '—'}</p>
                  </div>
                </button>
              )}

              <button
                type="button"
                onClick={handleLogout}
                className="shrink-0 rounded p-2 text-red-600 hover:bg-gray-100"
                title="Sair"
                disabled={logoutMutation.isPending}
              >
                <LogOut size={20} />
              </button>
            </div>
          </div>
        </header>

        <div className="min-h-0 flex-1 overflow-auto p-6">
          <Outlet />
        </div>
      </main>
    </div>
  )
}
