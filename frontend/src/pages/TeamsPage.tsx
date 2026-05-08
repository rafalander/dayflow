import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Plus, Users, ChevronRight } from 'lucide-react'
import UserAvatar from '@/components/UserAvatar'
import {
  useTeams,
  useCreateTeam,
  useAuth,
  useUserDirectory,
} from '@/hooks'
import { isAdminUser } from '@/lib/auth'
import type { User } from '@/types'

const DEFAULT_COLOR = '#6366f1'

export default function TeamsPage() {
  const navigate = useNavigate()
  const { meQuery } = useAuth()
  const isAdmin = isAdminUser(meQuery.data)

  const { data: teams = [], isPending, isError } = useTeams(isAdmin)
  const createMut = useCreateTeam()
  const { data: directoryPaginator } = useUserDirectory(isAdmin)
  const directoryUsers: User[] = directoryPaginator?.data ?? []

  const [modalOpen, setModalOpen] = useState(false)
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [color, setColor] = useState(DEFAULT_COLOR)
  const [leadId, setLeadId] = useState<number | ''>('')

  const leadOptions = useMemo(() => {
    return [...directoryUsers].sort((a, b) => a.name.localeCompare(b.name, 'pt'))
  }, [directoryUsers])

  const openModal = () => {
    setName('')
    setDescription('')
    setColor(DEFAULT_COLOR)
    setLeadId('')
    setModalOpen(true)
  }

  const closeModal = () => {
    setModalOpen(false)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!name.trim() || leadId === '') return
    try {
      await createMut.mutateAsync({
        name: name.trim(),
        description: description.trim() || null,
        color,
        lead_id: Number(leadId),
      })
      closeModal()
    } catch {}
  }

  if (!isAdmin) {
    return (
      <div className="rounded-lg border border-amber-100 bg-amber-50 p-6 text-amber-900">
        Apenas administradores podem gerir times.
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-6xl">
      <div className="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Times</h1>
          <p className="mt-1 text-gray-600">
            Agrupe analistas sob cada gestor. Dentro de cada card, abra o detalhe para ver a árvore hierárquica.
          </p>
        </div>
        <button
          type="button"
          onClick={openModal}
          className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 font-semibold text-white shadow-sm transition hover:opacity-95"
        >
          <Plus size={20} />
          Novo time
        </button>
      </div>

      {isPending ? (
        <p className="text-gray-500">A carregar…</p>
      ) : isError ? (
        <p className="text-red-600">Não foi possível carregar os times.</p>
      ) : teams.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-gray-200 bg-white/80 px-8 py-16 text-center">
          <Users className="mx-auto mb-4 text-gray-300" size={48} />
          <p className="text-lg font-medium text-gray-700">Nenhum time ainda</p>
          <p className="mt-2 text-gray-500">Crie um time e escolha o gestor para começar.</p>
          <button
            type="button"
            onClick={openModal}
            className="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 font-semibold text-white"
          >
            <Plus size={18} />
            Criar primeiro time
          </button>
        </div>
      ) : (
        <div className="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
          {teams.map((team) => {
            const accent = team.color || DEFAULT_COLOR
            const lead = team.lead
            return (
              <button
                key={team.id}
                type="button"
                onClick={() => navigate(`/teams/${team.id}`)}
                className="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white text-left shadow-sm ring-1 ring-gray-100 transition hover:-translate-y-0.5 hover:shadow-md"
              >
                <div
                  className="h-2 w-full"
                  style={{ background: `linear-gradient(90deg, ${accent}, ${accent}cc)` }}
                />
                <div className="flex flex-col gap-4 p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                      <h2 className="truncate text-lg font-bold text-gray-900">{team.name}</h2>
                      {team.description ? (
                        <p className="mt-1 line-clamp-2 text-sm text-gray-500">{team.description}</p>
                      ) : null}
                    </div>
                    <ChevronRight
                      className="shrink-0 text-gray-300 transition group-hover:text-primary"
                      size={22}
                    />
                  </div>

                  <div className="flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2">
                    {lead ? (
                      <>
                        <UserAvatar name={lead.name} src={lead.display_avatar} size="sm" />
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-sm font-semibold text-gray-800">{lead.name}</p>
                          <p className="text-xs text-gray-500">{lead.cargo?.name ?? 'Gestor'}</p>
                        </div>
                      </>
                    ) : (
                      <span className="text-sm text-gray-400">Sem gestor</span>
                    )}
                  </div>

                  <div className="flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                    <span className="flex items-center gap-1.5 text-gray-600">
                      <Users size={18} className="text-gray-400" />
                      <span>
                        <strong className="text-gray-900">{team.members_count ?? 0}</strong> membros
                      </span>
                    </span>
                    <span className="font-medium text-primary">Ver hierarquia</span>
                  </div>
                </div>
              </button>
            )
          })}
        </div>
      )}

      {modalOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-[2px]">
          <div
            className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
            role="dialog"
            aria-labelledby="team-modal-title"
          >
            <h2 id="team-modal-title" className="text-xl font-bold text-gray-900">
              Novo time
            </h2>
            <p className="mt-1 text-sm text-gray-500">
              O gestor recebe o vínculo ao time; depois pode adicionar analistas no detalhe.
            </p>

            <form onSubmit={handleSubmit} className="mt-6 space-y-4">
              <div>
                <label htmlFor="team-name" className="block text-sm font-medium text-gray-700">
                  Nome
                </label>
                <input
                  id="team-name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  required
                  maxLength={255}
                />
              </div>

              <div>
                <label htmlFor="team-desc" className="block text-sm font-medium text-gray-700">
                  Descrição (opcional)
                </label>
                <textarea
                  id="team-desc"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                />
              </div>

              <div className="flex flex-wrap items-end gap-4">
                <div>
                  <label htmlFor="team-color" className="block text-sm font-medium text-gray-700">
                    Cor do card
                  </label>
                  <div className="mt-1 flex items-center gap-2">
                    <input
                      id="team-color"
                      type="color"
                      value={color.length === 7 ? color : DEFAULT_COLOR}
                      onChange={(e) => setColor(e.target.value)}
                      className="h-10 w-14 cursor-pointer rounded border border-gray-200 bg-white"
                    />
                    <span className="font-mono text-sm text-gray-600">{color}</span>
                  </div>
                </div>
              </div>

              <div>
                <label htmlFor="team-lead" className="block text-sm font-medium text-gray-700">
                  Gestor do time
                </label>
                <select
                  id="team-lead"
                  value={leadId === '' ? '' : String(leadId)}
                  onChange={(e) => setLeadId(e.target.value === '' ? '' : Number(e.target.value))}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 shadow-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                  required
                >
                  <option value="">Selecione…</option>
                  {leadOptions.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name} · {u.cargo?.name ?? '—'}
                    </option>
                  ))}
                </select>
              </div>

              <div className="flex justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeModal}
                  className="rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-100"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={createMut.isPending}
                  className="rounded-lg bg-primary px-5 py-2 font-semibold text-white hover:opacity-95 disabled:opacity-60"
                >
                  {createMut.isPending ? 'A criar…' : 'Criar time'}
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}
    </div>
  )
}
