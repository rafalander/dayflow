import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { authService, userService, vacationService, reportService } from '@/services'
import toast from 'react-hot-toast'

export const useAuth = () => {
  const queryClient = useQueryClient()

  const meQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: authService.getMe,
    retry: false,
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

export const useUsers = (page = 1, search = '') => {
  return useQuery({
    queryKey: ['users', page, search],
    queryFn: () => userService.getAll(page, search),
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
    mutationFn: ({ id, data }: { id: number; data: any }) =>
      userService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
      toast.success('User updated successfully')
    },
    onError: () => {
      toast.error('Failed to update user')
    },
  })
}

export const useOrganizationTree = () => {
  return useQuery({
    queryKey: ['organization', 'tree'],
    queryFn: userService.getOrganizationTree,
  })
}

export const useVacationRequests = (page = 1, filters: Record<string, unknown> = {}) => {
  return useQuery({
    queryKey: ['vacation-requests', page, filters],
    queryFn: () => vacationService.getAll(page, filters),
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
      toast.success('Solicitação de férias criada')
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
