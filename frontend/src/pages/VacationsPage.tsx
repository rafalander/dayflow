import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { useVacationRequests, useCreateVacation } from '@/hooks'
import { vacationService } from '@/services'
import { formatDateBR } from '@/utils/date'
import type { VacationRequest } from '@/types'

function addDays(isoDate: string, days: number): string {
  const d = new Date(isoDate + 'T12:00:00')
  d.setDate(d.getDate() + days)
  return d.toISOString().slice(0, 10)
}

export default function VacationsPage() {
  const queryClient = useQueryClient()
  const [page] = useState(1)
  const { data: paginator, isPending, isError } = useVacationRequests(page, { per_page: 50 })
  const createMutation = useCreateVacation()

  const [modalOpen, setModalOpen] = useState(false)
  const today = new Date().toISOString().slice(0, 10)
  const [startDate, setStartDate] = useState(() => addDays(today, 7))
  const [endDate, setEndDate] = useState(() => addDays(today, 14))
  const [reason, setReason] = useState('')

  const deleteMutation = useMutation({
    mutationFn: (id: number) => vacationService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vacation-requests'] })
      toast.success('Solicitação removida')
    },
    onError: () => toast.error('Não foi possível remover'),
  })

  const rows: VacationRequest[] = paginator?.data ?? []

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      const payload: { start_date: string; end_date: string; reason?: string } = {
        start_date: startDate,
        end_date: endDate,
      }
      const r = reason.trim()
      if (r) payload.reason = r
      await createMutation.mutateAsync(payload)
      setModalOpen(false)
      setReason('')
    } catch {
      /* toast from mutation */
    }
  }

  const statusLabel: Record<string, string> = {
    pending: 'Pendente',
    approved: 'Aprovada',
    rejected: 'Recusada',
    cancelled: 'Cancelada',
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-3xl font-bold text-gray-900">Minhas Férias</h1>
        <button
          type="button"
          onClick={() => setModalOpen(true)}
          className="rounded-lg bg-primary px-6 py-2 font-semibold text-white hover:bg-primary/90"
        >
          Nova solicitação
        </button>
      </div>

      {isPending && <p className="text-gray-600">Carregando…</p>}
      {isError && <p className="text-red-600">Erro ao carregar solicitações.</p>}

      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Início</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fim</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && !isPending && (
              <tr>
                <td colSpan={4} className="px-6 py-8 text-center text-gray-500">
                  Nenhuma solicitação de férias
                </td>
              </tr>
            )}
            {rows.map((v) => (
              <tr key={v.id} className="border-t">
                <td className="px-6 py-3 text-sm">{formatDateBR(v.start_date)}</td>
                <td className="px-6 py-3 text-sm">{formatDateBR(v.end_date)}</td>
                <td className="px-6 py-3 text-sm">{statusLabel[v.status] ?? v.status}</td>
                <td className="px-6 py-3 text-sm">
                  {v.status === 'pending' && (
                    <button
                      type="button"
                      className="text-red-600 hover:underline"
                      disabled={deleteMutation.isPending}
                      onClick={() => {
                        if (confirm('Remover esta solicitação pendente?')) deleteMutation.mutate(v.id)
                      }}
                    >
                      Remover
                    </button>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {paginator && paginator.last_page > 1 && (
        <p className="text-sm text-gray-500">
          Página {paginator.current_page} de {paginator.last_page} ({paginator.total} itens)
        </p>
      )}

      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2 className="text-lg font-semibold text-gray-900">Nova solicitação</h2>
            <form onSubmit={handleCreate} className="mt-4 space-y-4">
              <div>
                <label htmlFor="vs" className="block text-xs font-medium text-gray-600">
                  Data início
                </label>
                <input
                  id="vs"
                  type="date"
                  required
                  min={today}
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label htmlFor="ve" className="block text-xs font-medium text-gray-600">
                  Data fim
                </label>
                <input
                  id="ve"
                  type="date"
                  required
                  min={startDate}
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label htmlFor="vr" className="block text-xs font-medium text-gray-600">
                  Motivo (opcional)
                </label>
                <textarea
                  id="vr"
                  value={reason}
                  onChange={(e) => setReason(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="rounded px-4 py-2 text-sm text-gray-600 hover:bg-gray-100"
                  onClick={() => setModalOpen(false)}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={createMutation.isPending}
                  className="rounded bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                >
                  {createMutation.isPending ? 'Enviando…' : 'Enviar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
