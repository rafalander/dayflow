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

export function toIsoDateKey(value: string): string {
  return value.trim().slice(0, 10)
}
