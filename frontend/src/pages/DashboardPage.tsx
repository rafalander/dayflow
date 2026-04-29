import { useMemo } from 'react'
import { useVacationRequests } from '@/hooks'
import TeamVacationCalendar from '@/components/TeamVacationCalendar'
import { formatDateBR, toIsoDateKey } from '@/utils/date'
import type { VacationRequest } from '@/types'

export default function DashboardPage() {
  const { data: paginator, isPending, isError } = useVacationRequests(1, { per_page: 100 })

  const stats = useMemo(() => {
    const rows: VacationRequest[] = paginator?.data ?? []
    const approved = rows.filter((r) => r.status === 'approved').length
    const pending = rows.filter((r) => r.status === 'pending').length
    const rejected = rows.filter((r) => r.status === 'rejected').length
    return {
      approved,
      pending,
      rejected,
      total: paginator?.total ?? rows.length,
    }
  }, [paginator])

  const upcoming = useMemo(() => {
    const rows: VacationRequest[] = paginator?.data ?? []
    const today = new Date().toISOString().slice(0, 10)
    return rows
      .filter((r) => r.status === 'approved' && toIsoDateKey(r.start_date) >= today)
      .sort((a, b) => toIsoDateKey(a.start_date).localeCompare(toIsoDateKey(b.start_date)))
      .slice(0, 8)
  }, [paginator])

  if (isPending) {
    return <p className="text-gray-600">Carregando…</p>
  }

  if (isError) {
    return <p className="text-red-600">Não foi possível carregar os dados.</p>
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p className="mt-2 text-gray-600">
          Visão geral das suas solicitações e calendário da equipe — férias aprovadas visíveis para todos.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-4">
        {[
          { label: 'Aprovadas', value: stats.approved, color: 'bg-green-100' },
          { label: 'Pendentes', value: stats.pending, color: 'bg-yellow-100' },
          { label: 'Recusadas', value: stats.rejected, color: 'bg-red-50' },
          { label: 'Total (listagem)', value: stats.total, color: 'bg-purple-100' },
        ].map((stat) => (
          <div key={stat.label} className={`rounded-lg p-6 shadow ${stat.color}`}>
            <p className="text-sm font-medium text-gray-600">{stat.label}</p>
            <p className="mt-2 text-3xl font-bold text-gray-900">{stat.value}</p>
          </div>
        ))}
      </div>

      <TeamVacationCalendar />

      <div className="rounded-lg bg-white p-6 shadow">
        <h2 className="mb-4 text-xl font-bold text-gray-900">Suas próximas férias (aprovadas)</h2>
        {upcoming.length === 0 ? (
          <p className="text-gray-500">Nenhuma férias aprovada futura nesta listagem.</p>
        ) : (
          <ul className="divide-y divide-gray-100">
            {upcoming.map((v) => (
              <li key={v.id} className="flex justify-between py-3 text-sm">
                <span className="text-gray-800">
                  {formatDateBR(v.start_date)} → {formatDateBR(v.end_date)}
                </span>
                <span className="text-gray-500">#{v.id}</span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}
