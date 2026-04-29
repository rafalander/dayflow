import { useState } from 'react'
import { usePendingApprovals, useApproveVacation, useRejectVacation } from '@/hooks'
import type { VacationRequest } from '@/types'

export default function ApprovalsPage() {
  const { data: paginator, isPending, isError, refetch } = usePendingApprovals(1, 50)
  const approveMutation = useApproveVacation()
  const rejectMutation = useRejectVacation()

  const [rejectFor, setRejectFor] = useState<VacationRequest | null>(null)
  const [rejectReason, setRejectReason] = useState('')

  const rows: VacationRequest[] = paginator?.data ?? []

  const handleApprove = async (id: number) => {
    await approveMutation.mutateAsync({ id })
    refetch()
  }

  const handleRejectSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!rejectFor || !rejectReason.trim()) return
    await rejectMutation.mutateAsync({ id: rejectFor.id, reason: rejectReason.trim() })
    setRejectFor(null)
    setRejectReason('')
    refetch()
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold text-gray-900">Aprovações pendentes</h1>

      {isPending && <p className="text-gray-600">Carregando…</p>}
      {isError && <p className="text-red-600">Erro ao carregar pendências.</p>}

      <div className="overflow-hidden rounded-lg bg-white shadow">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Solicitante</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Início</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fim</th>
              <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">Ações</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && !isPending && (
              <tr>
                <td colSpan={4} className="px-6 py-8 text-center text-gray-500">
                  Nenhuma aprovação pendente
                </td>
              </tr>
            )}
            {rows.map((v) => (
              <tr key={v.id} className="border-t">
                <td className="px-6 py-3 text-sm">{v.user?.name ?? `Usuário #${v.user_id}`}</td>
                <td className="px-6 py-3 text-sm">{v.start_date}</td>
                <td className="px-6 py-3 text-sm">{v.end_date}</td>
                <td className="space-x-2 px-6 py-3 text-sm">
                  <button
                    type="button"
                    className="font-medium text-green-700 hover:underline disabled:opacity-50"
                    disabled={approveMutation.isPending}
                    onClick={() => handleApprove(v.id)}
                  >
                    Aprovar
                  </button>
                  <button
                    type="button"
                    className="font-medium text-red-700 hover:underline disabled:opacity-50"
                    disabled={rejectMutation.isPending}
                    onClick={() => {
                      setRejectFor(v)
                      setRejectReason('')
                    }}
                  >
                    Recusar
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {rejectFor && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2 className="text-lg font-semibold text-gray-900">Recusar solicitação #{rejectFor.id}</h2>
            <form onSubmit={handleRejectSubmit} className="mt-4 space-y-4">
              <div>
                <label htmlFor="rej" className="block text-xs font-medium text-gray-600">
                  Motivo (obrigatório)
                </label>
                <textarea
                  id="rej"
                  required
                  rows={4}
                  value={rejectReason}
                  onChange={(e) => setRejectReason(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  className="rounded px-4 py-2 text-sm text-gray-600 hover:bg-gray-100"
                  onClick={() => setRejectFor(null)}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={rejectMutation.isPending}
                  className="rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60"
                >
                  Confirmar recusa
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
