import api from './api'
import { User, UserRole, ApiResponse, LaravelPaginator, VacationRequest, Role, Cargo } from '@/types'

export const authService = {
  getMe: async (): Promise<User> => {
    const response = await api.get<ApiResponse<User>>('/me')
    return response.data.data
  },

  logout: async (): Promise<void> => {
    await api.post('/logout')
  },

  redirectToGoogle: (): void => {
    window.location.href = `${import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'}/auth/redirect`
  },

  /** Login local (email/senha) — só funciona com DEV_PASSWORD_LOGIN no backend. */
  devLogin: async (email: string, password: string): Promise<{ token: string; user: User }> => {
    const response = await api.post<ApiResponse<{ token: string; user: User }>>('/auth/dev-login', {
      email,
      password,
    })
    return response.data.data
  },

  superadminLogin: async (email: string, password: string): Promise<{ token: string; user: User }> => {
    const response = await api.post<ApiResponse<{ token: string; user: User }>>('/auth/superadmin-login', {
      email,
      password,
    })
    return response.data.data
  },
}

export const userService = {
  getAll: async (page = 1, search = '', perPage = 15) => {
    const response = await api.get<ApiResponse<any>>('/users', {
      params: { page, search, per_page: perPage },
    })
    return response.data.data
  },

  getById: async (id: number) => {
    const response = await api.get<ApiResponse<User>>(`/users/${id}`)
    return response.data.data
  },

  create: async (data: {
    name: string
    email: string
    password: string
    role?: UserRole
    level?: number
    cargo_id?: number | null
    manager_id?: number | null
    is_active?: boolean
  }) => {
    const response = await api.post<ApiResponse<User>>('/users', data)
    return response.data.data as User
  },

  update: async (
    id: number,
    data: Partial<
      Pick<User, 'name' | 'email' | 'role' | 'level' | 'cargo_id' | 'manager_id' | 'is_active'> & { password?: string }
    >,
  ) => {
    const response = await api.put<ApiResponse<User>>(`/users/${id}`, data)
    return response.data.data
  },

  getOrganizationTree: async () => {
    const response = await api.get<ApiResponse<any>>('/organization/tree')
    return response.data.data
  },

  getSubordinates: async (userId: number) => {
    const response = await api.get<ApiResponse<any>>(`/users/${userId}/subordinates`)
    return response.data.data
  },
}

export const roleService = {
  getAll: async (): Promise<Role[]> => {
    const response = await api.get<{ data: Role[]; status: string }>('/roles')
    return response.data.data
  },
}

export const cargoService = {
  getAll: async (): Promise<Cargo[]> => {
    const response = await api.get<{ data: Cargo[]; status: string }>('/cargos')
    return response.data.data
  },

  create: async (data: { name: string; description?: string | null; role: UserRole; level: number }) => {
    const response = await api.post<ApiResponse<Cargo>>('/cargos', data)
    return response.data.data as Cargo
  },

  update: async (
    id: number,
    data: Partial<{ name: string; description: string | null; role: UserRole; level: number }>,
  ) => {
    const response = await api.put<ApiResponse<Cargo>>(`/cargos/${id}`, data)
    return response.data.data as Cargo
  },

  delete: async (id: number) => {
    await api.delete(`/cargos/${id}`)
  },
}

export const vacationService = {
  getAll: async (page = 1, filters: Record<string, unknown> = {}) => {
    const response = await api.get<{ data: LaravelPaginator<VacationRequest>; status: string }>('/vacation-requests', {
      params: { page, per_page: 50, ...filters },
    })
    return response.data.data
  },

  getById: async (id: number) => {
    const response = await api.get<ApiResponse<any>>(`/vacation-requests/${id}`)
    return response.data.data
  },

  create: async (data: any) => {
    const response = await api.post<ApiResponse<any>>('/vacation-requests', data)
    return response.data.data
  },

  update: async (id: number, data: any) => {
    const response = await api.put<ApiResponse<any>>(`/vacation-requests/${id}`, data)
    return response.data.data
  },

  delete: async (id: number) => {
    await api.delete(`/vacation-requests/${id}`)
  },

  getCalendar: async (startDate: string, endDate: string): Promise<VacationRequest[]> => {
    const response = await api.get<{ data: VacationRequest[]; status: string }>('/vacation-requests/calendar', {
      params: { start_date: startDate, end_date: endDate },
    })
    return response.data.data
  },

  getPending: async (page = 1, perPage = 15) => {
    const response = await api.get<{ data: LaravelPaginator<VacationRequest>; status: string }>('/approvals/pending', {
      params: { page, per_page: perPage },
    })
    return response.data.data
  },

  approve: async (id: number, comments?: string) => {
    const response = await api.post<ApiResponse<any>>(`/vacation-requests/${id}/approve`, { comments })
    return response.data.data
  },

  reject: async (id: number, reason: string) => {
    const response = await api.post<ApiResponse<any>>(`/vacation-requests/${id}/reject`, { reason })
    return response.data.data
  },
}

export const reportService = {
  getVacationReport: async (startDate: string, endDate: string, filters: Record<string, unknown> = {}) => {
    const response = await api.get<ApiResponse<Record<string, unknown>>>('/reports/vacations', {
      params: { start_date: startDate, end_date: endDate, ...filters },
    })
    return response.data.data
  },

  exportPdf: async (startDate: string, endDate: string, filters: Record<string, unknown> = {}) => {
    const response = await api.get('/reports/export-pdf', {
      params: { start_date: startDate, end_date: endDate, ...filters },
      responseType: 'blob',
    })
    const ct = String(response.headers['content-type'] ?? '')
    if (ct.includes('application/json')) {
      const text = await (response.data as Blob).text()
      const j = JSON.parse(text) as { message?: string }
      throw new Error(j.message || 'PDF indisponível')
    }
    return response.data as Blob
  },

  exportExcel: async (startDate: string, endDate: string, filters: Record<string, unknown> = {}) => {
    const response = await api.get('/reports/export-excel', {
      params: { start_date: startDate, end_date: endDate, ...filters },
      responseType: 'blob',
    })
    const ct = String(response.headers['content-type'] ?? '')
    if (ct.includes('application/json')) {
      const text = await (response.data as Blob).text()
      const j = JSON.parse(text) as { message?: string }
      throw new Error(j.message || 'Exportação indisponível')
    }
    return response.data as Blob
  },
}
