import { useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, Pencil, Users, Network } from 'lucide-react'
import HierarchyTree from '@/components/HierarchyTree'
import UserAvatar from '@/components/UserAvatar'
import {
  useTeam,
  useAuth,
  useUserDirectory,
  useUpdateTeam,
  useSyncTeamMembers,
  useDeleteTeam,
} from '@/hooks'
import { isAdminUser } from '@/lib/auth'
import type { User } from '@/types'

const DEFAULT_COLOR = '#6366f1'

export default function TeamDetailPage() {
  const navigate = useNavigate()
  const { id: idParam } = useParams()
  const teamId = idParam ? Number(idParam) : null

  const { meQuery } = useAuth()
  const isAdmin = isAdminUser(meQuery.data)

  const { data, isPending, isError, refetch } = useTeam(teamId)
  const updateMut = useUpdateTeam()
  const syncMut = useSyncTeamMembers()
  const deleteMut = useDeleteTeam()

  const [editOpen, setEditOpen] = useState(false)
  const [membersOpen, setMembersOpen] = useState(false)
  const [editName, setEditName] = useState('')
  const [editDescription, setEditDescription] = useState('')
  const [editColor, setEditColor] = useState(DEFAULT_COLOR)
  const [memberSearch, setMemberSearch] = useState('')
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set())

  const { data: directoryPaginator, isPending: directoryPending } = useUserDirectory(
    isAdmin && Boolean(data) && membersOpen,
  )
  const directoryUsers: User[] = directoryPaginator?.data ?? []

  const team = data?.team
  const hierarchy = data?.hierarchy ?? null
  const memberIds = data?.member_ids ?? []
  const accent = team?.color || DEFAULT_COLOR

  const openEdit = () => {
    if (!team) return
    setEditName(team.name)
    setEditDescription(team.description ?? '')
    setEditColor(team.color || DEFAULT_COLOR)
    setEditOpen(true)
  }

  const openMembers = () => {
    const next = new Set(memberIds)
    if (team?.lead_id) next.add(team.lead_id)
    setSelectedIds(next)
    setMemberSearch('')
    setMembersOpen(true)
  }

  const filteredDirectory = useMemo(() => {
    const q = memberSearch.trim().toLowerCase()
    let list = [...directoryUsers].sort((a, b) => a.name.localeCompare(b.name, 'pt'))
    if (q) {
      list = list.filter(
        (u) =>
          u.name.toLowerCase().includes(q) ||
          u.email.toLowerCase().includes(q) ||
          (u.cargo?.name ?? '').toLowerCase().includes(q),
      )
    }
    return list
  }, [directoryUsers, memberSearch])

  const toggleMember = (uid: number) => {
    setSelectedIds((prev) => {
      const next = new Set(prev)
      if (team && uid === team.lead_id) {
        return next
      }
      if (next.has(uid)) {
        next.delete(uid)
      } else {
        next.add(uid)
      }
      return next
    })
  }

  const handleSaveMembers = async () => {
    if (!teamId || !team) return
    const ids = Array.from(selectedIds)
    if (!ids.includes(team.lead_id)) {
      ids.push(team.lead_id)
    }
    try {
      await syncMut.mutateAsync({ id: teamId, user_ids: ids })
      setMembersOpen(false)
      refetch()
    } catch {}
  }

  const handleSaveEdit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!teamId || !editName.trim()) return
    try {
      await updateMut.mutateAsync({
        id: teamId,
        data: {
          name: editName.trim(),
          description: editDescription.trim() || null,
          color: editColor,
        },
      })
      setEditOpen(false)
      refetch()
    } catch {}
  }

  const handleDelete = async () => {
    if (!teamId || !team) return
    if (!confirm(`Remover o time "${team.name}"? Os utilizadores deixam de estar agrupados neste time.`)) return
    try {
      await deleteMut.mutateAsync(teamId)
      navigate('/teams')
    } catch {}
  }

  if (!isAdmin) {
    return (
      <div className="rounded-lg border border-amber-100 bg-amber-50 p-6 text-amber-900">
        Apenas administradores podem ver esta página.
      </div>
    )
  }

  if (teamId == null || Number.isNaN(teamId)) {
    return <p className="text-red-600">Time inválido.</p>
  }

  if (isPending) {
    return <p className="text-gray-500">A carregar…</p>
  }

  if (isError || !data || !team) {
    return (
      <div className="rounded-lg border border-red-100 bg-red-50 p-6 text-red-800">
        Não foi possível carregar este time.
        <button type="button" className="ml-4 underline" onClick={() => navigate('/teams')}>
          Voltar
        </button>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-4xl">
      <button
        type="button"
        onClick={() => navigate('/teams')}
        className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary"
      >
        <ArrowLeft size={18} />
        Todos os times
      </button>

      <header className="mb-8 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm ring-1 ring-gray-100">
        <div className="h-2 w-full" style={{ background: `linear-gradient(90deg, ${accent}, ${accent}aa)` }} />
        <div className="flex flex-col gap-4 p-6 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0 flex-1">
            <div className="flex flex-wrap items-center gap-3">
              <h1 className="text-2xl font-bold text-gray-900">{team.name}</h1>
              <span
                className="rounded-full px-3 py-1 text-xs font-semibold text-white"
                style={{ backgroundColor: accent }}
              >
                {team.members_count ?? memberIds.length} membros
              </span>
            </div>
            {team.description ? (
              <p className="mt-2 text-gray-600">{team.description}</p>
            ) : (
              <p className="mt-2 text-sm italic text-gray-400">Sem descrição</p>
            )}
            {team.lead ? (
              <div className="mt-4 flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2">
                <UserAvatar name={team.lead.name} src={team.lead.display_avatar} size="sm" />
                <div>
                  <p className="text-sm font-semibold text-gray-800">{team.lead.name}</p>
                  <p className="text-xs text-gray-500">{team.lead.cargo?.name ?? 'Gestor do time'}</p>
                </div>
              </div>
            ) : null}
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={openEdit}
              className="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50"
            >
              <Pencil size={18} />
              Editar
            </button>
            <button
              type="button"
              onClick={openMembers}
              className="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-95"
            >
              <Users size={18} />
              Membros
            </button>
            <button
              type="button"
              onClick={handleDelete}
              className="rounded-xl px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
            >
              Excluir time
            </button>
          </div>
        </div>
      </header>

      <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-100">
        <div className="mb-6 flex items-center gap-2">
          <Network className="text-primary" size={22} />
          <h2 className="text-lg font-bold text-gray-900">Hierarquia</h2>
        </div>
        <p className="mb-6 text-sm text-gray-500">
          A árvore usa o <strong className="text-gray-700">gestor direto</strong> de cada pessoa (edição de usuários).
          Subordinados aparecem encadeados quando o gestor também está no time. Membros só adicionados ao time sem
          gestor definido aparecem abaixo do gestor com o aviso{' '}
          <span className="font-medium text-amber-900">Gestor não ligado na hierarquia</span> — edite o utilizador e
          defina o gestor para montar a pirâmide corretamente.
        </p>

        {hierarchy ? (
          <HierarchyTree node={hierarchy} accentColor={accent} />
        ) : (
          <p className="text-gray-500">Não foi possível montar a árvore (gestor em falta?).</p>
        )}
      </section>

      {editOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-[2px]">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <h3 className="text-lg font-bold text-gray-900">Editar time</h3>
            <form onSubmit={handleSaveEdit} className="mt-4 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700">Nome</label>
                <input
                  value={editName}
                  onChange={(e) => setEditName(e.target.value)}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Descrição</label>
                <textarea
                  value={editDescription}
                  onChange={(e) => setEditDescription(e.target.value)}
                  rows={3}
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700">Cor</label>
                <div className="mt-1 flex items-center gap-2">
                  <input
                    type="color"
                    value={editColor.length === 7 ? editColor : DEFAULT_COLOR}
                    onChange={(e) => setEditColor(e.target.value)}
                    className="h-10 w-14 cursor-pointer rounded border"
                  />
                  <span className="font-mono text-sm">{editColor}</span>
                </div>
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button type="button" onClick={() => setEditOpen(false)} className="rounded-lg px-4 py-2 text-gray-700">
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={updateMut.isPending}
                  className="rounded-lg bg-primary px-5 py-2 font-semibold text-white disabled:opacity-60"
                >
                  Guardar
                </button>
              </div>
            </form>
          </div>
        </div>
      ) : null}

      {membersOpen ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-[2px]">
          <div className="flex max-h-[85vh] w-full max-w-lg flex-col rounded-2xl bg-white shadow-xl">
            <div className="border-b border-gray-100 p-6">
              <h3 className="text-lg font-bold text-gray-900">Membros do time</h3>
              <p className="mt-1 text-sm text-gray-500">
                O gestor <strong>{team.lead?.name}</strong> permanece no time. Marque analistas e outros membros.
              </p>
              <input
                type="search"
                placeholder="Filtrar por nome, e-mail ou cargo…"
                value={memberSearch}
                onChange={(e) => setMemberSearch(e.target.value)}
                className="mt-4 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
              />
            </div>
            <div className="min-h-0 flex-1 overflow-y-auto px-6 py-2">
              <ul className="divide-y divide-gray-100">
                {directoryPending ? (
                  <li className="py-8 text-center text-sm text-gray-500">A carregar utilizadores…</li>
                ) : null}
                {!directoryPending &&
                  filteredDirectory.map((u) => {
                  const isLead = u.id === team.lead_id
                  const checked = selectedIds.has(u.id)
                  return (
                    <li key={u.id} className="flex items-center gap-3 py-3">
                      <input
                        type="checkbox"
                        id={`m-${u.id}`}
                        checked={checked}
                        disabled={isLead}
                        onChange={() => toggleMember(u.id)}
                        className="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                      />
                      <label htmlFor={`m-${u.id}`} className="flex min-w-0 flex-1 cursor-pointer items-center gap-3">
                        <UserAvatar name={u.name} src={u.display_avatar} size="sm" />
                        <span className="min-w-0">
                          <span className="block truncate font-medium text-gray-900">
                            {u.name}
                            {isLead ? (
                              <span className="ml-2 text-xs font-semibold text-primary">(gestor)</span>
                            ) : null}
                          </span>
                          <span className="block truncate text-xs text-gray-500">{u.email}</span>
                          <span className="mt-0.5 block truncate text-xs font-medium text-gray-600">
                            {u.cargo?.name ?? 'Sem cargo'}
                          </span>
                        </span>
                      </label>
                    </li>
                  )
                })}
              </ul>
            </div>
            <div className="flex justify-end gap-2 border-t border-gray-100 p-6">
              <button
                type="button"
                onClick={() => setMembersOpen(false)}
                className="rounded-lg px-4 py-2 text-gray-700 hover:bg-gray-100"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={() => void handleSaveMembers()}
                disabled={syncMut.isPending || directoryPending}
                className="rounded-lg bg-primary px-5 py-2 font-semibold text-white disabled:opacity-60"
              >
                {syncMut.isPending ? 'A guardar…' : 'Guardar membros'}
              </button>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  )
}
