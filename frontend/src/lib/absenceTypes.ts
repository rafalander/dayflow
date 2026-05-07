import type { AbsenceTypeOption, VacationRequest } from '@/types'

export function absenceTypeLabel(row: VacationRequest, catalog?: AbsenceTypeOption[]): string {
  if (row.absence_type_label) return row.absence_type_label
  const hit = catalog?.find((t) => t.slug === row.absence_type)
  return hit?.label ?? row.absence_type ?? '—'
}
