import type { ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { useAuth } from '@/hooks'
import { formatDateBR, formatDateTimeBR } from '@/utils/date'
import { Loader, Mail, Briefcase, CalendarClock, CalendarDays } from 'lucide-react'
import UserAvatar from '@/components/UserAvatar'

function InfoRow({
  icon: Icon,
  label,
  value,
}: {
  icon: LucideIcon
  label: string
  value: ReactNode
}) {
  return (
    <div className="flex gap-4 py-5 first:pt-0 last:pb-0">
      <span className="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
        <Icon size={18} strokeWidth={2} />
      </span>
      <div className="min-w-0 flex-1 border-b border-gray-100 pb-5 last:border-0 last:pb-0">
        <p className="text-xs font-medium uppercase tracking-wide text-gray-400">{label}</p>
        <p className="mt-1 text-sm font-semibold text-gray-900 sm:text-base">{value}</p>
      </div>
    </div>
  )
}

export default function ProfilePage() {
  const { meQuery } = useAuth()

  if (meQuery.isPending) {
    return (
      <div className="flex min-h-[320px] items-center justify-center">
        <Loader className="h-9 w-9 animate-spin text-primary" />
      </div>
    )
  }

  const user = meQuery.data
  if (!user) {
    return (
      <p className="text-center text-gray-500">Não foi possível carregar o perfil.</p>
    )
  }

  const cargoName = user.cargo?.name ?? '—'

  const memberSince = user.created_at ? formatDateBR(user.created_at) : '—'

  const lastLogin = user.last_login_at ? formatDateTimeBR(user.last_login_at) : 'Nunca registrado'

  return (
    <div className="mx-auto max-w-3xl space-y-8">
      <header>
        <h1 className="text-2xl font-bold tracking-tight text-gray-900">Meu perfil</h1>
        <p className="mt-1 text-sm text-gray-500">Dados da sua conta corporativa no Dayflow.</p>
      </header>

      <section className="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
        <div className="shrink-0 rounded-full p-0.5 ring-2 ring-gray-100">
          <UserAvatar name={user.name} src={user.display_avatar} size="lg" />
        </div>

        <div className="relative w-full min-w-0 flex-1 rounded-2xl border border-gray-200/90 bg-white p-5 pt-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6 sm:pt-6">
          <span
            className={`absolute right-4 top-4 z-10 inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-xs font-semibold ring-1 sm:right-6 sm:top-6 ${
              user.is_active
                ? 'bg-emerald-50 text-emerald-800 ring-emerald-200/90'
                : 'bg-gray-100 text-gray-600 ring-gray-200'
            }`}
          >
            <span className={`h-2 w-2 rounded-full ${user.is_active ? 'bg-emerald-500' : 'bg-gray-400'}`} />
            {user.is_active ? 'Conta ativa' : 'Conta inativa'}
          </span>

          <div className="min-w-0 max-w-[calc(100%-9rem)] pr-2 sm:max-w-none">
            <h2 className="text-xl font-bold tracking-tight text-gray-900 sm:text-2xl">{user.name}</h2>
            <p className="mt-2 flex items-center gap-2 text-sm">
              <Mail size={16} className="shrink-0 text-gray-500" aria-hidden />
              <span className="truncate text-gray-800">{user.email}</span>
            </p>
            <div className="mt-3">
              <span className="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                {cargoName}
              </span>
            </div>
          </div>
        </div>
      </section>

      <section className="rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm ring-1 ring-black/[0.03] sm:p-8">
        <div className="mb-2 flex items-center gap-2.5">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
            <Briefcase className="h-5 w-5 text-primary" aria-hidden />
          </div>
          <div>
            <h3 className="text-base font-semibold text-gray-900">Informações da conta</h3>
            <p className="text-xs text-gray-500">Hierarquia e permissões vêm apenas do cargo atribuído.</p>
          </div>
        </div>

        <div className="mt-4 border-t border-gray-100 pt-2">
          <InfoRow icon={Briefcase} label="Cargo" value={cargoName} />
          <InfoRow icon={CalendarDays} label="Membro desde" value={memberSince} />
          <InfoRow icon={CalendarClock} label="Último acesso" value={lastLogin} />
        </div>
      </section>
    </div>
  )
}
