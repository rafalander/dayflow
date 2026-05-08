import { useState } from 'react'
import { useUsers, useAuth, useCargos, useUserDirectory, useUpdateUser, useCreateUser } from '@/hooks'
import { formatDateBR, formatDateTimeBR } from '@/utils/date'
import { isAdminUser } from '@/lib/auth'
import type { User } from '@/types'

type ModalMode = 'create' | 'edit' | null

function roleLabel(slug: string): string {
  if (slug === 'admin') return 'Administrador'
  if (slug === 'user') return 'Usuário'
  return slug
}

export default function UsersPage() {
  const { meQuery } = useAuth()
  const currentUserId = meQuery.data?.id
  const authLevel = meQuery.data?.cargo?.level ?? 0
  const isAdmin = isAdminUser(meQuery.data)

  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const { data: paginator, isPending, isError } = useUsers(page, search, isAdmin)

  const [modalMode, setModalMode] = useState<ModalMode>(null)
  const [editing, setEditing] = useState<User | null>(null)

  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [cargoId, setCargoId] = useState<number | null>(null)
  const [managerId, setManagerId] = useState<number | null>(null)
  const [isActive, setIsActive] = useState(true)

  const { data: cargos = [] } = useCargos(isAdmin)
  const { data: directoryPaginator } = useUserDirectory(isAdmin && modalMode !== null)
  const directoryUsers: User[] = directoryPaginator?.data ?? []

  const updateMut = useUpdateUser()
  const createMut = useCreateUser()

  const rows: User[] = paginator?.data ?? []

  const maxAssignableLevel = Math.max(1, authLevel - 1)

  const resetForm = () => {
    setName('')
    setEmail('')
    setPassword('')
    setCargoId(null)
    setManagerId(null)
    setIsActive(true)
    setEditing(null)
  }

  const openCreate = () => {
    resetForm()
    setModalMode('create')
  }

  const openEdit = (u: User) => {
    setEditing(u)
    setName(u.name)
    setEmail(u.email)
    setPassword('')
    setCargoId(u.cargo_id)
    setManagerId(u.manager_id ?? null)
    setIsActive(u.is_active)
    setModalMode('edit')
  }

  const closeModal = () => {
    setModalMode(null)
    resetForm()
  }

  const handleCargoChange = (cid: string) => {
    const v = cid === '' ? null : Number(cid)
    setCargoId(v)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      if (modalMode === 'create') {
        if (!password.trim() || !cargoId) return
        await createMut.mutateAsync({
          name: name.trim(),
          email: email.trim(),
          password: password.trim(),
          cargo_id: cargoId,
          manager_id: managerId,
          is_active: isActive,
        })
      } else if (modalMode === 'edit' && editing) {
        if (currentUserId === editing.id) {
          await updateMut.mutateAsync({
            id: editing.id,
            data: {
              name: name.trim(),
              email: email.trim(),
              ...(password.trim() ? { password: password.trim() } : {}),
            },
          })
        } else {
          await updateMut.mutateAsync({
            id: editing.id,
            data: {
              name: name.trim(),
              email: email.trim(),
              cargo_id: cargoId ?? undefined,
              manager_id: managerId,
              is_active: isActive,
              ...(password.trim() ? { password: password.trim() } : {}),
            },
          })
        }
      }
      closeModal()
    } catch {}
  }

  const managerOptions = directoryUsers.filter((u) =>
    editing ? u.id !== editing.id : true,
  )

  const cargoAllowedForCreate = (c: { level: number }) => c.level < authLevel && c.level <= maxAssignableLevel

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Usuários</h1>
          <p className="mt-1 text-sm text-gray-600">
            Hierarquia definida pelo <strong>cargo</strong>: só vê e edita utilizadores com cargo estritamente abaixo
            do seu.
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={openCreate}
            className="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"
          >
            Novo usuário
          </button>
          <input
            type="search"
            placeholder="Buscar nome ou e-mail…"
            value={search}
            onChange={(e) => {
              setSearch(e.target.value)
              setPage(1)
            }}
            className="min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm"
          />
        </div>
      </div>

      {isPending && <p className="text-gray-600">Carregando…</p>}
      {isError && <p className="text-red-600">Sem permissão ou erro ao carregar usuários.</p>}

      {!isPending && !isError && (
        <div className="overflow-x-auto rounded-lg bg-white shadow">
          <table className="min-w-[920px] w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Nome</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">E-mail</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Cargo</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Gestor</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Ativo</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Último acesso</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Criado em</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-700">Ações</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={9} className="px-4 py-8 text-center text-gray-500">
                    Nenhum usuário encontrado
                  </td>
                </tr>
              )}
              {rows.map((u) => (
                <tr key={u.id} className="border-t border-gray-100">
                  <td className="whitespace-nowrap px-4 py-3 text-gray-600">{u.id}</td>
                  <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
                  <td className="max-w-[220px] truncate px-4 py-3 text-gray-700">{u.email}</td>
                  <td className="px-4 py-3 text-gray-700">{u.cargo?.name ?? '—'}</td>
                  <td className="max-w-[160px] truncate px-4 py-3 text-gray-700">
                    {u.manager?.name ?? '—'}
                  </td>
                  <td className="whitespace-nowrap px-4 py-3">
                    <span
                      className={
                        u.is_active ? 'font-medium text-emerald-700' : 'font-medium text-gray-500'
                      }
                    >
                      {u.is_active ? 'Sim' : 'Não'}
                    </span>
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-gray-700">
                    {u.last_login_at ? formatDateTimeBR(u.last_login_at) : '—'}
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-gray-700">
                    {formatDateBR(u.created_at)}
                  </td>
                  <td className="whitespace-nowrap px-4 py-3 text-right">
                    <button
                      type="button"
                      className="font-medium text-primary hover:underline"
                      onClick={() => openEdit(u)}
                    >
                      Editar
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {paginator && paginator.last_page > 1 && (
        <div className="flex flex-wrap items-center gap-3 text-sm text-gray-600">
          <span>
            Página {paginator.current_page} de {paginator.last_page} ({paginator.total} utilizadores)
          </span>
          <button
            type="button"
            disabled={paginator.current_page <= 1}
            className="rounded border border-gray-300 px-3 py-1 disabled:opacity-40"
            onClick={() => setPage((p) => Math.max(1, p - 1))}
          >
            Anterior
          </button>
          <button
            type="button"
            disabled={paginator.current_page >= paginator.last_page}
            className="rounded border border-gray-300 px-3 py-1 disabled:opacity-40"
            onClick={() => setPage((p) => p + 1)}
          >
            Próxima
          </button>
        </div>
      )}

      {modalMode && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
            <h2 className="text-lg font-semibold text-gray-900">
              {modalMode === 'create' ? 'Novo usuário' : `Editar usuário #${editing?.id}`}
            </h2>
            <form onSubmit={handleSubmit} className="mt-4 space-y-4">
              <div>
                <label htmlFor="uf-name" className="block text-xs font-medium text-gray-600">
                  Nome
                </label>
                <input
                  id="uf-name"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label htmlFor="uf-email" className="block text-xs font-medium text-gray-600">
                  E-mail
                </label>
                <input
                  id="uf-email"
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label htmlFor="uf-pass" className="block text-xs font-medium text-gray-600">
                  {modalMode === 'create' ? 'Palavra-passe' : 'Nova palavra-passe (opcional)'}
                </label>
                <input
                  id="uf-pass"
                  type="password"
                  autoComplete="new-password"
                  required={modalMode === 'create'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder={modalMode === 'edit' ? 'Deixe em branco para manter' : ''}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>

              {!(modalMode === 'edit' && currentUserId === editing?.id) && (
                <div>
                  <label htmlFor="uf-cargo" className="block text-xs font-medium text-gray-600">
                    Cargo
                  </label>
                  <select
                    id="uf-cargo"
                    value={cargoId ?? ''}
                    onChange={(e) => handleCargoChange(e.target.value)}
                    required={modalMode === 'create'}
                    className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  >
                    <option value="">— Selecione —</option>
                    {cargos
                      .filter(
                        (c) =>
                          cargoAllowedForCreate(c) ||
                          (modalMode === 'edit' && editing && c.id === editing.cargo_id),
                      )
                      .map((c) => (
                        <option key={c.id} value={c.id}>
                          {c.name} ({roleLabel(c.role)})
                        </option>
                      ))}
                  </select>
                </div>
              )}

              {!(modalMode === 'edit' && currentUserId === editing?.id) && (
                <div>
                  <label htmlFor="uf-mgr" className="block text-xs font-medium text-gray-600">
                    Gestor direto
                  </label>
                  <select
                    id="uf-mgr"
                    value={managerId ?? ''}
                    onChange={(e) =>
                      setManagerId(e.target.value === '' ? null : Number(e.target.value))
                    }
                    className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                  >
                    <option value="">— Nenhum —</option>
                    {managerOptions.map((u) => (
                      <option key={u.id} value={u.id}>
                        {u.name} ({u.email}){u.cargo?.name ? ` · ${u.cargo.name}` : ''}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {!(modalMode === 'edit' && currentUserId === editing?.id) && (
                <div className="flex items-center gap-2">
                  <input
                    id="uf-active"
                    type="checkbox"
                    checked={isActive}
                    onChange={(e) => setIsActive(e.target.checked)}
                    className="rounded border-gray-300"
                  />
                  <label htmlFor="uf-active" className="text-sm text-gray-700">
                    Conta ativa
                  </label>
                </div>
              )}

              {currentUserId === editing?.id && (
                <p className="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                  Está a editar a sua própria conta. Aqui só pode alterar nome, e-mail ou palavra-passe.
                </p>
              )}

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  className="rounded px-4 py-2 text-sm text-gray-600 hover:bg-gray-100"
                  onClick={closeModal}
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={createMut.isPending || updateMut.isPending}
                  className="rounded bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90 disabled:opacity-60"
                >
                  {modalMode === 'create' ? 'Criar' : 'Guardar'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
