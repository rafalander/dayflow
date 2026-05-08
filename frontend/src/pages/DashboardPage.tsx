import { useMemo } from 'react'
import { useAuth, useTeamVacationStats } from '@/hooks'
import TeamVacationCalendar from '@/components/TeamVacationCalendar'
import { canViewTeamVacationStats } from '@/lib/auth'

export default function DashboardPage() {
  const { meQuery } = useAuth()
  const user = meQuery.data
  const showTeamStats = canViewTeamVacationStats(user)

  const teamStatsQuery = useTeamVacationStats(showTeamStats && !!user)

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
          gestores veem só subordinados diretos). Próximas ausências e horizonte ficam em Configurações.
        </p>
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
