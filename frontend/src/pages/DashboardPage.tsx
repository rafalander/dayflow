import { useMemo } from 'react'
import { useAuth, useDashboardMonthlyOverview, useTeamVacationStats } from '@/hooks'
import TeamVacationCalendar from '@/components/TeamVacationCalendar'
import { canViewTeamVacationStats } from '@/lib/auth'

export default function DashboardPage() {
  const { meQuery } = useAuth()
  const user = meQuery.data
  const showTeamStats = canViewTeamVacationStats(user)

  const monthlyOverviewQuery = useDashboardMonthlyOverview()
  const teamStatsQuery = useTeamVacationStats(showTeamStats && !!user)

  const monthlyOverview = monthlyOverviewQuery.data
  const stats = teamStatsQuery.data

  const statsCards = useMemo(
    () =>
      stats
        ? [
            { label: 'Aprovadas', value: stats.approved, color: 'bg-green-100' },
            { label: 'Pendentes', value: stats.pending, color: 'bg-yellow-100' },
            { label: 'Recusadas', value: stats.rejected, color: 'bg-red-50' },
            { label: 'Total (listagem)', value: stats.total, color: 'bg-purple-100' },
          ]
        : [],
    [stats],
  )

  const monthLabel = useMemo(() => {
    const monthKey = monthlyOverview?.month
    if (!monthKey) return 'mês atual'

    const [year, month] = monthKey.split('-').map(Number)
    const label = new Intl.DateTimeFormat('pt-BR', { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1))
    return label.charAt(0).toUpperCase() + label.slice(1)
  }, [monthlyOverview?.month])

  if (meQuery.isPending) {
    return <p className="text-gray-600">Carregando…</p>
  }

  if (meQuery.isError) {
    return <p className="text-red-600">Não foi possível carregar os dados.</p>
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-2 text-gray-600">
          Calendário geral da equipe; resumo de solicitações apenas para gestão (admin vê a organização, demais
          gestores veem só subordinados diretos). O mês atual também destaca aniversariantes e ausências previstas.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.7fr)_320px]">
        <section className="rounded-lg bg-white p-6 shadow">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Aniversariantes de {monthLabel}</h2>
            <p className="mt-1 text-sm text-gray-500">Lista dos colaboradores ativos com aniversário no mês corrente.</p>
          </div>

          <div className="mt-4">
            {monthlyOverviewQuery.isPending && <p className="text-gray-600">Carregando aniversariantes…</p>}
            {monthlyOverviewQuery.isError && (
              <p className="text-red-600">Não foi possível carregar os aniversariantes deste mês.</p>
            )}
            {monthlyOverview && monthlyOverview.birthdays.length === 0 && (
              <p className="text-gray-500">Nenhum aniversariante cadastrado para este mês.</p>
            )}
            {monthlyOverview && monthlyOverview.birthdays.length > 0 && (
              <ul className="divide-y divide-gray-100">
                {monthlyOverview.birthdays.map((person) => (
                  <li key={person.id} className="flex items-center justify-between gap-4 py-3">
                    <span className="font-medium text-gray-900">{person.name}</span>
                    <span className="inline-flex min-w-12 items-center justify-center rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">
                      Dia {String(person.day).padStart(2, '0')}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </section>

        <section className="rounded-lg bg-amber-50 p-6 shadow ring-1 ring-amber-100">
          <p className="text-sm font-medium text-amber-800">Possíveis ausências no mês</p>
          {monthlyOverviewQuery.isPending && <p className="mt-3 text-sm text-amber-900">Carregando contagem…</p>}
          {monthlyOverviewQuery.isError && (
            <p className="mt-3 text-sm text-red-600">Não foi possível calcular as ausências previstas.</p>
          )}
          {monthlyOverview && (
            <>
              <p className="mt-3 text-4xl font-bold text-amber-950">{monthlyOverview.possible_absences_count}</p>
              <p className="mt-2 text-sm text-amber-900">
                Solicitações aprovadas ou pendentes com período dentro de {monthLabel.toLowerCase()}.
              </p>
            </>
          )}
        </section>
      </div>

      {showTeamStats && (
        <div className="space-y-3">
          {teamStatsQuery.isPending && <p className="text-gray-600">Carregando resumo…</p>}
          {teamStatsQuery.isError && (
            <p className="text-red-600">Não foi possível carregar o resumo para gestão.</p>
          )}
          {statsCards.length > 0 && (
            <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
              {statsCards.map((stat) => (
                <div key={stat.label} className={`rounded-lg p-6 shadow ${stat.color}`}>
                  <p className="text-sm font-medium text-gray-600">{stat.label}</p>
                  <p className="mt-2 text-3xl font-bold text-gray-900">{stat.value}</p>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      <TeamVacationCalendar />
    </div>
  )
}
