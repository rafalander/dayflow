import { useState } from 'react'
import { useCargos, useRoles, useCreateCargo, useUpdateCargo, useDeleteCargo } from '@/hooks'
import { useAuth } from '@/hooks'
import type { Cargo, UserRole } from '@/types'

export default function CargosPage() {
  const { meQuery } = useAuth()
  const authLevel = meQuery.data?.cargo?.level ?? 0
  const maxLevel = Math.max(1, authLevel - 1)

  const { data: cargos = [], isPending, isError } = useCargos(true)
  const { data: roles = [] } = useRoles(true)
  const createMut = useCreateCargo()
  const updateMut = useUpdateCargo()
  const deleteMut = useDeleteCargo()

  const [modal, setModal] = useState<'create' | 'edit' | null>(null)
  const [editing, setEditing] = useState<Cargo | null>(null)
  const [name, setName] = useState('')
  const [description, setDescription] = useState('')
  const [role, setRole] = useState<UserRole>('user')
  const [level, setLevel] = useState(40)

  const resetForm = () => {
    setName('')
    setDescription('')
    setRole('user')
    setLevel(Math.min(40, maxLevel))
    setEditing(null)
  }

  const openCreate = () => {
    resetForm()
    setModal('create')
  }

  const openEdit = (c: Cargo) => {
    setEditing(c)
    setName(c.name)
    setDescription(c.description ?? '')
    setRole(c.role)
    setLevel(c.level)
    setModal('edit')
  }

  const closeModal = () => {
    setModal(null)
    resetForm()
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      if (modal === 'create') {
        await createMut.mutateAsync({
          name: name.trim(),
          description: description.trim() || null,
          role,
          level,
        })
      } else if (modal === 'edit' && editing) {
        await updateMut.mutateAsync({
          id: editing.id,
          data: {
            name: name.trim(),
            description: description.trim() || null,
            role,
            level,
          },
        })
      }
      closeModal()
    } catch {
      /* toast no hook */
    }
  }

  const handleDelete = async (c: Cargo) => {
    if (!confirm(`Remover o cargo "${c.name}"?`)) return
    try {
      await deleteMut.mutateAsync(c.id)
    } catch {
      /* toast */
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Cargos</h1>
          <p className="mt-1 text-sm text-gray-600">
            Cada cargo define uma role (admin ou usuário) e um nível hierárquico. Ao atribuir um cargo a alguém,
            o utilizador passa a ter esse nível (desde que seja inferior ao seu).
          </p>
        </div>
        <button
          type="button"
          onClick={openCreate}
          className="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-white hover:bg-primary/90"
        >
          Novo cargo
        </button>
      </div>

      {isPending && <p className="text-gray-600">Carregando…</p>}
      {isError && <p className="text-red-600">Erro ao carregar cargos.</p>}

      {!isPending && !isError && (
        <div className="overflow-x-auto rounded-lg bg-white shadow">
          <table className="w-full min-w-[640px] text-sm">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Nome</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Nível</th>
                <th className="px-4 py-3 text-left font-semibold text-gray-700">Descrição</th>
                <th className="px-4 py-3 text-right font-semibold text-gray-700">Ações</th>
              </tr>
            </thead>
            <tbody>
              {cargos.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                    Nenhum cargo cadastrado
                  </td>
                </tr>
              )}
              {cargos.map((c) => (
                <tr key={c.id} className="border-t border-gray-100">
                  <td className="px-4 py-3 font-medium text-gray-900">{c.name}</td>
                  <td className="px-4 py-3 text-gray-700">{c.role === 'admin' ? 'Administrador' : 'Usuário'}</td>
                  <td className="px-4 py-3 font-mono text-gray-800">{c.level}</td>
                  <td className="max-w-md truncate px-4 py-3 text-gray-600">{c.description ?? '—'}</td>
                  <td className="space-x-3 px-4 py-3 text-right">
                    <button
                      type="button"
                      className="font-medium text-primary hover:underline"
                      onClick={() => openEdit(c)}
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      className="font-medium text-red-600 hover:underline disabled:opacity-50"
                      disabled={deleteMut.isPending}
                      onClick={() => handleDelete(c)}
                    >
                      Excluir
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <h2 className="text-lg font-semibold text-gray-900">
              {modal === 'create' ? 'Novo cargo' : 'Editar cargo'}
            </h2>
            <form onSubmit={handleSubmit} className="mt-4 space-y-4">
              <div>
                <label htmlFor="cn" className="block text-xs font-medium text-gray-600">
                  Nome
                </label>
                <input
                  id="cn"
                  required
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label htmlFor="cr" className="block text-xs font-medium text-gray-600">
                  Role
                </label>
                <select
                  id="cr"
                  required
                  value={role}
                  onChange={(e) => setRole(e.target.value as UserRole)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                >
                  {roles.map((r) => (
                    <option key={r.slug} value={r.slug}>
                      {r.name}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label htmlFor="cl" className="block text-xs font-medium text-gray-600">
                  Nível (máx. {maxLevel})
                </label>
                <input
                  id="cl"
                  type="number"
                  required
                  min={1}
                  max={maxLevel}
                  value={level}
                  onChange={(e) => setLevel(Number(e.target.value))}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm font-mono"
                />
              </div>
              <div>
                <label htmlFor="cd" className="block text-xs font-medium text-gray-600">
                  Descrição (opcional)
                </label>
                <textarea
                  id="cd"
                  rows={3}
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  className="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
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
                  Salvar
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}
