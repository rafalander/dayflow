import { format, isValid, parseISO } from 'date-fns'

/** Formata ISO ou data `YYYY-MM-DD` como DD/MM/YYYY (pt-BR). */
export function formatDateBR(value: string): string {
  const trimmed = value.trim()
  if (!trimmed) return ''
  try {
    const d = trimmed.includes('T') ? parseISO(trimmed) : parseISO(`${trimmed}T12:00:00`)
    if (!isValid(d)) return value
    return format(d, 'dd/MM/yyyy')
  } catch {
    return value
  }
}

/** Data e hora curtas (pt-BR), ex.: 29/04/2026 14:30 */
export function formatDateTimeBR(value: string): string {
  const trimmed = value.trim()
  if (!trimmed) return ''
  try {
    const d = parseISO(trimmed)
    if (!isValid(d)) return value
    return format(d, 'dd/MM/yyyy HH:mm')
  } catch {
    return value
  }
}

/** Normaliza ISO datetime ou YYYY-MM-DD para prefixo YYYY-MM-DD (comparações / filtros). */
export function toIsoDateKey(value: string): string {
  return value.trim().slice(0, 10)
}
