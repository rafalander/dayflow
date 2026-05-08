import { format, isValid, parseISO } from 'date-fns'

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

export function toIsoDateKey(value: string | null | undefined): string {
  if (value == null || typeof value !== 'string') return ''
  return value.trim().slice(0, 10)
}

/** Normalizes API date payloads (string, Laravel date object JSON, timestamp). */
export function coerceIsoDateKey(value: unknown): string {
  if (value == null) return ''
  if (typeof value === 'string') return toIsoDateKey(value)
  if (typeof value === 'number' && Number.isFinite(value)) {
    const d = new Date(value)
    return Number.isNaN(d.getTime()) ? '' : format(d, 'yyyy-MM-dd')
  }
  if (typeof value === 'object' && value !== null && 'date' in value) {
    const inner = (value as { date?: unknown }).date
    if (typeof inner === 'string') return toIsoDateKey(inner)
  }
  return ''
}
