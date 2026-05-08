import { useMemo, useRef, useState } from 'react'
import { keepPreviousData, useQuery } from '@tanstack/react-query'
import {
  addMonths,
  eachDayOfInterval,
  endOfMonth,
  endOfWeek,
  format,
  isSameMonth,
  isToday,
  startOfMonth,
  startOfWeek,
} from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { ChevronLeft, ChevronRight } from 'lucide-react'
import { useAbsenceTypes, useAuth } from '@/hooks'
import { absenceTypeLabel } from '@/lib/absenceTypes'
import { vacationService } from '@/services'
import { coerceIsoDateKey, formatDateBR, toIsoDateKey } from '@/utils/date'
import type { VacationRequest } from '@/types'

const WEEKDAYS = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom']

function dayInVacation(dayIso: string, v: VacationRequest): boolean {
  const start = coerceIsoDateKey(v.start_date)
  const end = coerceIsoDateKey(v.end_date)
  if (!start || !end) return false
  return start <= dayIso && end >= dayIso
}

function shortName(fullName: string): string {
  const parts = fullName.trim().split(/\s+/)
  if (parts.length <= 1) return parts[0]?.slice(0, 12) ?? '?'
  return `${parts[0]} ${parts[parts.length - 1][0]}.`
}

function normalizeCalendarRow(x: unknown): VacationRequest | null {
  if (x == null || typeof x !== 'object') return null
  const o = x as Record<string, unknown>
  const start =
    coerceIsoDateKey(o.start_date) || coerceIsoDateKey(o.startDate)
  const end = coerceIsoDateKey(o.end_date) || coerceIsoDateKey(o.endDate)
  if (!start || !end) return null
  return { ...(o as unknown as VacationRequest), start_date: start, end_date: end }
}

function asVacationRequestList(data: unknown): VacationRequest[] {
  if (data == null) return []
  const raw: unknown[] = Array.isArray(data)
    ? data
    : typeof data === 'object'
      ? Object.values(data as Record<string, unknown>)
      : []
  return raw.map(normalizeCalendarRow).filter((v): v is VacationRequest => v !== null)
}

function chipStyles(userId: number): { bg: string; text: string } {
  const palette = [
    { bg: 'bg-indigo-100', text: 'text-indigo-900' },
    { bg: 'bg-violet-100', text: 'text-violet-900' },
    { bg: 'bg-teal-100', text: 'text-teal-900' },
    { bg: 'bg-amber-100', text: 'text-amber-900' },
    { bg: 'bg-rose-100', text: 'text-rose-900' },
    { bg: 'bg-sky-100', text: 'text-sky-900' },
  ]
  return palette[Math.abs(userId) % palette.length]
}

export default function TeamVacationCalendar() {
  const { meQuery } = useAuth()
  const viewerId = meQuery.data?.id
  const { data: absenceTypes = [] } = useAbsenceTypes()
  const monthInputRef = useRef<HTMLInputElement>(null)
  const [cursor, setCursor] = useState(() => startOfMonth(new Date()))

  function openMonthPicker() {
    const el = monthInputRef.current
    if (!el) return
    if (typeof el.showPicker !== 'function') {
      el.click()
      return
    }
    try {
      void Promise.resolve(el.showPicker()).catch(() => el.click())
    } catch {
      el.click()
    }
  }

  const { rangeStart, rangeEnd, gridStart, gridEnd } = useMemo(() => {
    const monthStart = startOfMonth(cursor)
    const monthEnd = endOfMonth(cursor)
    const gStart = startOfWeek(monthStart, { weekStartsOn: 1 })
    const gEnd = endOfWeek(monthEnd, { weekStartsOn: 1 })
    return {
      rangeStart: format(gStart, 'yyyy-MM-dd'),
      rangeEnd: format(gEnd, 'yyyy-MM-dd'),
      gridStart: gStart,
      gridEnd: gEnd,
    }
  }, [cursor])

  const { data: calendarData, isPending, isError, isFetching } = useQuery({
    queryKey: ['vacation-calendar', viewerId, rangeStart, rangeEnd],
    queryFn: () => vacationService.getCalendar(rangeStart, rangeEnd),
    enabled: viewerId != null,
    staleTime: 3 * 60 * 1000,
    placeholderData: keepPreviousData,
  })

  const vacations = useMemo(() => asVacationRequestList(calendarData), [calendarData])

  const days = useMemo(
    () => eachDayOfInterval({ start: gridStart, end: gridEnd }),
    [gridStart, gridEnd],
  )

  const byDay = useMemo(() => {
    const map = new Map<string, VacationRequest[]>()
    for (const day of days) {
      const iso = format(day, 'yyyy-MM-dd')
      const list = vacations.filter((v) => dayInVacation(iso, v))
      map.set(iso, list)
    }
    return map
  }, [days, vacations])

  return (
    <section className="rounded-2xl border border-gray-200/90 bg-white p-5 shadow-sm ring-1 ring-black/[0.03] sm:p-6">
      <div className="flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h2 className="text-lg font-semibold text-gray-900">Calendário da equipe</h2>
          <p className="mt-1 text-sm text-gray-500">
            Ausências aprovadas da empresa no período; as suas solicitações pendentes também aparecem aqui. Passe o mouse
            sobre um nome para ver tipo e intervalo.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2 self-start">
          <div className="flex items-center gap-0 rounded-lg border border-gray-200 bg-gray-50/80 p-0.5">
            <button
              type="button"
              className="rounded-md p-2 text-gray-600 hover:bg-white hover:text-gray-900"
              aria-label="Mês anterior"
              onClick={() => setCursor((d) => addMonths(d, -1))}
            >
              <ChevronLeft size={18} />
            </button>
            <div className="relative flex min-h-[2.25rem] min-w-[11rem] items-center justify-center">
              <input
                ref={monthInputRef}
                type="month"
                lang="pt-BR"
                value={format(cursor, 'yyyy-MM')}
                onChange={(e) => {
                  const v = e.target.value
                  if (!v) return
                  const [y, mo] = v.split('-').map(Number)
                  if (!Number.isFinite(y) || !Number.isFinite(mo)) return
                  setCursor(startOfMonth(new Date(y, mo - 1, 1)))
                }}
                className="sr-only"
                tabIndex={-1}
                aria-hidden
              />
              <button
                type="button"
                title="Clique para escolher o mês"
                className="rounded-md px-2 py-1.5 text-sm font-semibold capitalize text-gray-800 transition-colors hover:bg-white hover:text-primary focus:outline-none focus-visible:bg-white focus-visible:ring-2 focus-visible:ring-primary/30"
                onClick={openMonthPicker}
              >
                {format(cursor, 'MMMM yyyy', { locale: ptBR })}
              </button>
            </div>
            <button
              type="button"
              className="rounded-md p-2 text-gray-600 hover:bg-white hover:text-gray-900"
              aria-label="Próximo mês"
              onClick={() => setCursor((d) => addMonths(d, 1))}
            >
              <ChevronRight size={18} />
            </button>
          </div>
        </div>
      </div>

      {isPending && <p className="mt-6 text-sm text-gray-500">Carregando calendário…</p>}
      {isError && (
        <p className="mt-6 text-sm text-red-600">Não foi possível carregar as ausências do período.</p>
      )}

      {!isPending && !isError && (
        <>
          <div
            className={`mt-4 overflow-hidden rounded-xl border border-gray-200 transition-opacity duration-200 ${
              isFetching ? 'pointer-events-none opacity-60' : 'opacity-100'
            }`}
          >
            <div className="grid grid-cols-7 gap-px bg-gray-200">
              {WEEKDAYS.map((w) => (
                <div
                  key={w}
                  className="bg-gray-50 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500"
                >
                  {w}
                </div>
              ))}
            {days.map((day) => {
              const iso = format(day, 'yyyy-MM-dd')
              const inMonth = isSameMonth(day, cursor)
              const list = byDay.get(iso) ?? []
              const today = isToday(day)

              return (
                <div
                  key={iso}
                  className={`flex min-h-[5.5rem] flex-col bg-white p-1.5 sm:min-h-[6.5rem] sm:p-2 ${
                    !inMonth ? 'opacity-45' : ''
                  } ${today ? 'ring-1 ring-inset ring-primary/40' : ''}`}
                >
                  <span
                    className={`mb-1 text-right text-xs font-medium tabular-nums ${
                      today ? 'text-primary' : inMonth ? 'text-gray-800' : 'text-gray-400'
                    }`}
                  >
                    {format(day, 'd')}
                  </span>
                  <div className="flex min-h-0 flex-1 flex-col gap-0.5 overflow-hidden">
                    {list.slice(0, 3).map((v) => {
                      const u = v.user
                      const label = u?.name ? shortName(u.name) : `#${v.user_id}`
                      const { bg, text } = chipStyles(v.user_id)
                      const typeL = absenceTypeLabel(v, absenceTypes)
                      const pending = v.status === 'pending'
                      const statusNote = pending ? ' — pendente de aprovação' : ''
                      const title = u?.name
                        ? `${u.name} — ${typeL} — ${formatDateBR(v.start_date)} → ${formatDateBR(v.end_date)}${statusNote}`
                        : `${typeL} — ${formatDateBR(v.start_date)} → ${formatDateBR(v.end_date)}${statusNote}`
                      return (
                        <span
                          key={`${v.id}-${iso}`}
                          title={title}
                          className={`truncate rounded px-1 py-0.5 text-[10px] font-medium leading-tight sm:text-[11px] ${bg} ${text} ${
                            pending ? 'ring-1 ring-inset ring-amber-400/70' : ''
                          }`}
                        >
                          {label}
                        </span>
                      )
                    })}
                    {list.length > 3 && (
                      <span className="text-[10px] font-medium text-gray-500">+{list.length - 3}</span>
                    )}
                  </div>
                </div>
              )
            })}
            </div>
          </div>

          {vacations.length === 0 && (
            <p className="mt-4 text-center text-sm text-gray-500">
              Nada neste período: nem ausências aprovadas da empresa, nem suas solicitações pendentes.
            </p>
          )}

          {vacations.length > 0 && (
            <div className="mt-6 rounded-xl border border-gray-100 bg-gray-50/60 p-4">
              <h3 className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                Ausências no período da grade ({format(gridStart, 'dd/MM')} – {format(gridEnd, 'dd/MM/yyyy')})
              </h3>
              <ul className="mt-3 space-y-2 text-sm">
                {[...vacations]
                  .sort((a, b) =>
                    toIsoDateKey(a.start_date).localeCompare(toIsoDateKey(b.start_date)),
                  )
                  .map((v) => (
                    <li key={v.id} className="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-100/80 pb-2 last:border-0 last:pb-0">
                      <span className="font-medium text-gray-900">{v.user?.name ?? `Usuário #${v.user_id}`}</span>
                      <span className="text-gray-600">
                        {absenceTypeLabel(v, absenceTypes)} · {formatDateBR(v.start_date)} →{' '}
                        {formatDateBR(v.end_date)}
                        {v.status === 'pending' ? (
                          <span className="text-amber-700"> · pendente</span>
                        ) : null}
                      </span>
                    </li>
                  ))}
              </ul>
            </div>
          )}
        </>
      )}
    </section>
  )
}
