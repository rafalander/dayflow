import axios from 'axios'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  authService,
  dashboardService,
  userService,
  vacationService,
  reportService,
  roleService,
  cargoService,
  teamService,
  settingService,
  absenceTypeService,
} from '@/services'
import type { Cargo, User } from '@/types'
import toast from 'react-hot-toast'

function normalizeCargoList(payload: unknown): Cargo[] {
  if (Array.isArray(payload)) return payload as Cargo[]
  if (payload && typeof payload === 'object') {
    const nested = (payload as { data?: unknown }).data
    if (Array.isArray(nested)) return nested as Cargo[]
  }
  return []
}

export const useAuth = () => {
  const queryClient = useQueryClient()

  const meQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: authService.getMe,
    retry: false,
    staleTime: 10 * 60 * 1000,
  })

  const logoutMutation = useMutation({
    mutationFn: authService.logout,
    onSuccess: () => {
      queryClient.clear()
      toast.success('Sessão encerrada')
    },
  })

  return { meQuery, logoutMutation }
}

export const useDashboardMonthlyOverview = () => {
  return useQuery({
    queryKey: ['dashboard', 'monthly-overview'],
    queryFn: dashboardService.getMonthlyOverview,
    staleTime: 2 * 60 * 1000,
  })
}

export const useUsers = (page = 1, search = '', enabled = true, perPage = 15) => {
  return useQuery({
    queryKey: ['users', page, search, perPage],
    queryFn: () => userService.getAll(page, search, perPage),
    enabled,
  })
}

export const useUserDirectory = (enabled: boolean) => {
  return useQuery({
    queryKey: ['users', 'directory'],
    queryFn: () => userService.getAll(1, '', 500),
    enabled,
    staleTime: 5 * 60 * 1000,
  })
}

export const useRoles = (enabled = true) => {
  return useQuery({
    queryKey: ['roles'],
    queryFn: roleService.getAll,
    enabled,
  })
}

export const useCargos = (enabled = true) => {
  return useQuery({
    queryKey: ['cargos'],
    queryFn: cargoService.getAll,
    enabled,
    staleTime: 5 * 60 * 1000,
    select: normalizeCargoList,
  })
}

export const useCreateCargo = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: cargoService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cargos'] })
      toast.success('Cargo criado')
    },
    onError: () => toast.error('Não foi possível criar o cargo'),
  })
}

export const useUpdateCargo = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Parameters<typeof cargoService.update>[1] }) =>
      cargoService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cargos'] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
      toast.success('Cargo atualizado')
    },
    onError: () => toast.error('Não foi possível atualizar'),
  })
}

export const useDeleteCargo = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: cargoService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cargos'] })
      toast.success('Cargo removido')
    },
    onError: () => toast.error('Não foi possível remover'),
  })
}

export const useUser = (id: number) => {
  return useQuery({
    queryKey: ['users', id],
    queryFn: () => userService.getById(id),
  })
}

export const useUpdateUser = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Parameters<typeof userService.update>[1] }) =>
      userService.update(id, data),
    onSuccess: (_data, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      queryClient.invalidateQueries({ queryKey: ['users', id] })
      queryClient.invalidateQueries({ queryKey: ['users', 'directory'] })
      queryClient.invalidateQueries({ queryKey: ['dashboard', 'monthly-overview'] })
      const me = queryClient.getQueryData<User>(['auth', 'me'])
      if (me?.id === id) {
        queryClient.invalidateQueries({ queryKey: ['auth', 'me'] })
      }
      toast.success('Usuário atualizado')
    },
    onError: () => {
      toast.error('Não foi possível atualizar o usuário')
    },
  })
}

export const useCreateUser = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: userService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      queryClient.invalidateQueries({ queryKey: ['users', 'directory'] })
      toast.success('Usuário criado')
    },
    onError: () => {
      toast.error('Não foi possível criar o usuário')
    },
  })
}

export const useOrganizationTree = () => {
  return useQuery({
    queryKey: ['organization', 'tree'],
    queryFn: userService.getOrganizationTree,
  })
}

export const useTeams = (enabled = true) => {
  return useQuery({
    queryKey: ['teams'],
    queryFn: teamService.getAll,
    enabled,
    staleTime: 60 * 1000,
  })
}

export const useTeam = (id: number | null) => {
  return useQuery({
    queryKey: ['teams', id],
    queryFn: () => teamService.getById(id!),
    enabled: id != null && id > 0,
    staleTime: 60 * 1000,
  })
}

export const useCreateTeam = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: teamService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
      queryClient.invalidateQueries({ queryKey: ['users', 'directory'] })
      toast.success('Time criado')
    },
    onError: () => toast.error('Não foi possível criar o time'),
  })
}

export const useUpdateTeam = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: Parameters<typeof teamService.update>[1] }) =>
      teamService.update(id, data),
    onSuccess: (_d, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['teams'] })
      queryClient.invalidateQueries({ queryKey: ['teams', id] })
      toast.success('Time atualizado')
    },
    onError: () => toast.error('Não foi possível atualizar'),
  })
}

export const useDeleteTeam = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: teamService.delete,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['teams'] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
      toast.success('Time removido')
    },
    onError: () => toast.error('Não foi possível remover o time'),
  })
}

export const useSyncTeamMembers = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, user_ids }: { id: number; user_ids: number[] }) =>
      teamService.syncMembers(id, user_ids),
    onSuccess: (_d, { id }) => {
      queryClient.invalidateQueries({ queryKey: ['teams', id] })
      queryClient.invalidateQueries({ queryKey: ['teams'] })
      queryClient.invalidateQueries({ queryKey: ['users'] })
      toast.success('Membros atualizados')
    },
    onError: () => toast.error('Não foi possível atualizar os membros'),
  })
}

export const useAbsenceTypes = () => {
  return useQuery({
    queryKey: ['absence-types'],
    queryFn: () => absenceTypeService.getAll(),
    staleTime: 60 * 60 * 1000,
  })
}

export const useVacationRequests = (page = 1, filters: Record<string, unknown> = {}) => {
  return useQuery({
    queryKey: ['vacation-requests', page, filters],
    queryFn: () => vacationService.getAll(page, filters),
  })
}

export const useTeamVacationStats = (enabled: boolean) => {
  return useQuery({
    queryKey: ['vacation-requests', 'team-stats'],
    queryFn: () => vacationService.getTeamStats(),
    enabled,
    staleTime: 2 * 60 * 1000,
  })
}

export const useUpcomingAbsences = () => {
  return useQuery({
    queryKey: ['vacation-requests', 'upcoming-absences'],
    queryFn: () => vacationService.getUpcomingAbsences(),
    staleTime: 2 * 60 * 1000,
  })
}

export const useUpdateUpcomingAbsencesHorizon = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (value: string) =>
      settingService.update('dashboard_upcoming_absences_days', value),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vacation-requests', 'upcoming-absences'] })
      toast.success('Horizonte de dias atualizado')
    },
    onError: (err: unknown) => {
      const msg =
        axios.isAxiosError(err) && typeof err.response?.data?.message === 'string'
          ? err.response.data.message
          : null
      toast.error(msg ?? 'Não foi possível salvar a configuração')
    },
  })
}

export const usePendingApprovals = (page = 1, perPage = 15) => {
  return useQuery({
    queryKey: ['approvals', 'pending', page, perPage],
    queryFn: () => vacationService.getPending(page, perPage),
  })
}

export const useVacationRequest = (id: number) => {
  return useQuery({
    queryKey: ['vacation-requests', id],
    queryFn: () => vacationService.getById(id),
  })
}

export const useCreateVacation = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: vacationService.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vacation-requests'] })
      queryClient.invalidateQueries({ queryKey: ['vacation-calendar'] })
      toast.success('Solicitação de ausência criada')
    },
    onError: () => {
      toast.error('Não foi possível criar a solicitação')
    },
  })
}

export const useApproveVacation = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, comments }: { id: number; comments?: string }) =>
      vacationService.approve(id, comments),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vacation-requests'] })
      queryClient.invalidateQueries({ queryKey: ['vacation-calendar'] })
      queryClient.invalidateQueries({ queryKey: ['approvals'] })
      toast.success('Solicitação aprovada')
    },
    onError: () => {
      toast.error('Não foi possível aprovar')
    },
  })
}

export const useRejectVacation = () => {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      vacationService.reject(id, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['vacation-requests'] })
      queryClient.invalidateQueries({ queryKey: ['vacation-calendar'] })
      queryClient.invalidateQueries({ queryKey: ['approvals'] })
      toast.success('Solicitação recusada')
    },
    onError: () => {
      toast.error('Não foi possível recusar')
    },
  })
}

export const useVacationReport = (startDate: string, endDate: string, filters = {}) => {
  return useQuery({
    queryKey: ['reports', 'vacations', startDate, endDate, filters],
    queryFn: () => reportService.getVacationReport(startDate, endDate, filters),
  })
}
