import { useEffect, useState } from 'react'
import toast from 'react-hot-toast'
import { useAbsenceTypes, useAuth, useUpcomingAbsences, useUpdateUpcomingAbsencesHorizon } from '@/hooks'
import { absenceTypeLabel } from '@/lib/absenceTypes'
import { formatDateBR } from '@/utils/date'
import { isAdminUser } from '@/lib/auth'

export default function SettingsPage() {
  const { data: absenceTypes = [] } = useAbsenceTypes()
  const { meQuery } = useAuth()
  const user = meQuery.data

  const upcomingQuery = useUpcomingAbsences()
  const updateHorizon = useUpdateUpcomingAbsencesHorizon()

  const [horizonDraft, setHorizonDraft] = useState('')

  useEffect(() => {
    if (upcomingQuery.data?.days != null) {
      setHorizonDraft(String(upcomingQuery.data.days))
    }
  }, [upcomingQuery.data?.days])

  const handleHorizonSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const n = parseInt(horizonDraft, 10)
    if (!Number.isFinite(n) || n < 1 || n > 366) {
      toast.error('Informe um número de dias entre 1 e 366.')
      return
    }
    updateHorizon.mutate(String(n))
  }

  if (meQuery.isPending) {
    return <p className="text-gray-600">Carregando…</p>
  }

  if (meQuery.isError) {
    return <p className="text-red-600">Não foi possível carregar os dados.</p>
  }

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold text-gray-900">Configurações</h1>
        <p className="mt-2 text-gray-600">
          Preferências da organização. O horizonte de próximas ausências também alimenta integrações futuras (ex.:
          alertas automáticos).
        </p>
      </div>

      <div className="rounded-lg bg-white p-6 shadow">
        <div className="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h2 className="text-xl font-bold text-gray-900">Próximas ausências</h2>
            <p className="mt-1 text-sm text-gray-500">
              Ausências aprovadas com início no período configurado (visível para todos).
              {upcomingQuery.data != null && (
                <span className="block text-gray-600">
                  Horizonte atual: <strong>{upcomingQuery.data.days}</strong> dias.
                </span>
              )}
            </p>
          </div>
          {isAdminUser(user) && (
            <form
              onSubmit={handleHorizonSubmit}
              className="flex flex-wrap items-end gap-2 rounded-md border border-gray-200 bg-gray-50 p-3"
            >
              <label className="flex flex-col text-sm">
                <span className="text-gray-600">Dias à frente</span>
                <input
                  type="number"
                  min={1}
                  max={366}
                  value={horizonDraft}
                  onChange={(e) => setHorizonDraft(e.target.value)}
                  className="mt-1 w-28 rounded border border-gray-300 px-2 py-1.5 text-gray-900 shadow-sm"
                />
              </label>
              <button
                type="submit"
                disabled={updateHorizon.isPending}
                className="rounded-md bg-gray-900 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50"
              >
                Salvar
              </button>
            </form>
          )}
        </div>

        {upcomingQuery.isPending && <p className="text-gray-600">Carregando ausências…</p>}
        {upcomingQuery.isError && (
          <p className="text-red-600">Não foi possível carregar as próximas ausências.</p>
        )}
        {upcomingQuery.data && upcomingQuery.data.list.length === 0 && (
          <p className="text-gray-500">Nenhuma ausência aprovada neste período.</p>
        )}
        {upcomingQuery.data && upcomingQuery.data.list.length > 0 && (
          <ul className="divide-y divide-gray-100">
            {upcomingQuery.data.list.map((v) => (
              <li
                key={v.id}
                className="flex flex-col gap-0.5 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"
              >
                <span className="font-medium text-gray-900">{v.user?.name ?? `Usuário #${v.user_id}`}</span>
                <span className="text-gray-600">
                  {absenceTypeLabel(v, absenceTypes)} · {formatDateBR(v.start_date)} → {formatDateBR(v.end_date)}
                </span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}
