import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import toast from 'react-hot-toast'
import { reportService } from '@/services'
import type { VacationReportGroup } from '@/types'

function defaultRange(): { start: string; end: string } {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 90)
  return {
    start: start.toISOString().slice(0, 10),
    end: end.toISOString().slice(0, 10),
  }
}

export default function ReportsPage() {
  const { start: ds, end: de } = useMemo(() => defaultRange(), [])
  const [startDate, setStartDate] = useState(ds)
  const [endDate, setEndDate] = useState(de)

  const reportQuery = useQuery({
    queryKey: ['reports', 'vacations', startDate, endDate],
    queryFn: () => reportService.getVacationReport(startDate, endDate),
    enabled: false,
  })

  const groups = useMemo((): VacationReportGroup[] => {
    const raw = reportQuery.data
    if (!raw || typeof raw !== 'object') return []
    return Object.values(raw as Record<string, VacationReportGroup>)
  }, [reportQuery.data])

  const handleGenerate = () => {
    reportQuery.refetch()
  }

  const downloadBlob = (blob: Blob, filename: string) => {
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    a.click()
    URL.revokeObjectURL(url)
  }

  const handleExcel = async () => {
    try {
      const blob = await reportService.exportExcel(startDate, endDate)
      downloadBlob(blob, `relatorio-ausencias-${startDate}-${endDate}.csv`)
      toast.success('Download iniciado')
    } catch (e: unknown) {
      toast.error(e instanceof Error ? e.message : 'Falha ao exportar')
    }
  }

  const handlePdf = async () => {
    try {
      const blob = await reportService.exportPdf(startDate, endDate)
      downloadBlob(blob, `relatorio-ausencias-${startDate}-${endDate}.pdf`)
      toast.success('Download iniciado')
    } catch (e: unknown) {
      toast.error(e instanceof Error ? e.message : 'PDF ainda não implementado no servidor')
    }
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold text-gray-900">Relatórios</h1>

      <div className="rounded-lg bg-white p-6 shadow">
        <h2 className="mb-4 text-lg font-semibold text-gray-900">Período</h2>
        <div className="flex flex-wrap items-end gap-4">
          <div>
            <label htmlFor="rs" className="block text-xs font-medium text-gray-600">
              Início
            </label>
            <input
              id="rs"
              type="date"
              value={startDate}
              onChange={(e) => setStartDate(e.target.value)}
              className="mt-1 rounded border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label htmlFor="re" className="block text-xs font-medium text-gray-600">
              Fim
            </label>
            <input
              id="re"
              type="date"
              value={endDate}
              min={startDate}
              onChange={(e) => setEndDate(e.target.value)}
              className="mt-1 rounded border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
          <button
            type="button"
            onClick={handleGenerate}
            disabled={reportQuery.isFetching}
            className="rounded-lg bg-primary px-4 py-2 font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
          >
            {reportQuery.isFetching ? 'Gerando…' : 'Gerar relatório'}
          </button>
          <button
            type="button"
            onClick={handleExcel}
            className="rounded-lg bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700"
          >
            CSV / Excel
          </button>
          <button
            type="button"
            onClick={handlePdf}
            className="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700"
          >
            PDF
          </button>
        </div>

        {reportQuery.isError && <p className="mt-4 text-sm text-red-600">Erro ao gerar relatório.</p>}

        {reportQuery.data && (
          <div className="mt-8 overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="border-b bg-gray-50 text-left">
                  <th className="px-4 py-2">Colaborador</th>
                  <th className="px-4 py-2">E-mail</th>
                  <th className="px-4 py-2">Total dias</th>
                  <th className="px-4 py-2">Períodos</th>
                </tr>
              </thead>
              <tbody>
                {groups.map((g) => (
                  <tr key={g.user?.id ?? Math.random()} className="border-b">
                    <td className="px-4 py-2">{g.user?.name}</td>
                    <td className="px-4 py-2">{g.user?.email}</td>
                    <td className="px-4 py-2">{g.total_days}</td>
                    <td className="px-4 py-2 text-gray-600">
                      {g.vacations?.length ?? 0} registro(s)
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            {groups.length === 0 && !reportQuery.isFetching && (
              <p className="mt-4 text-gray-500">Nenhum dado aprovado neste período.</p>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
