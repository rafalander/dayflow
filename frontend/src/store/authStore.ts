import { create } from 'zustand'
import { User } from '@/types'

interface AuthStore {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  setUser: (user: User | null) => void
  setToken: (token: string | null) => void
  setIsLoading: (isLoading: boolean) => void
  setError: (error: string | null) => void
  logout: () => void
}

export const useAuthStore = create<AuthStore>((set) => ({
  user: null,
  token: localStorage.getItem('auth_token') || null,
  isAuthenticated: !!localStorage.getItem('auth_token'),
  isLoading: false,
  error: null,
  setUser: (user) => set({ user }),
  setToken: (token) => {
    if (token) {
      localStorage.setItem('auth_token', token)
    } else {
      localStorage.removeItem('auth_token')
    }
    set({ token, isAuthenticated: !!token })
  },
  setIsLoading: (isLoading) => set({ isLoading }),
  setError: (error) => set({ error }),
  logout: () => {
    localStorage.removeItem('auth_token')
    set({ 
      user: null, 
      token: null, 
      isAuthenticated: false, 
      error: null 
    })
  },
}))
