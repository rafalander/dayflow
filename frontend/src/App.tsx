import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { QueryClientProvider } from '@tanstack/react-query'
import { Toaster } from 'react-hot-toast'
import queryClient from '@/lib/queryClient'
import ProtectedRoute from '@/components/ProtectedRoute'
import Layout from '@/components/Layout'
import LoginPage from '@/pages/LoginPage'
import AdminLoginPage from '@/pages/AdminLoginPage'
import DashboardPage from '@/pages/DashboardPage'
import VacationsPage from '@/pages/VacationsPage'
import ApprovalsPage from '@/pages/ApprovalsPage'
import ReportsPage from '@/pages/ReportsPage'
import ProfilePage from '@/pages/ProfilePage'
import AuthErrorPage from '@/pages/AuthErrorPage'
import AdminOnly from '@/components/AdminOnly'
import UsersPage from '@/pages/UsersPage'
import CargosPage from '@/pages/CargosPage'
import TeamsPage from '@/pages/TeamsPage'
import TeamDetailPage from '@/pages/TeamDetailPage'
import SettingsPage from '@/pages/SettingsPage'

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <Router>
        <Routes>
          <Route path="/auth/callback" element={<LoginPage />} />
          <Route path="/auth/error" element={<AuthErrorPage />} />
          <Route path="/auth/login" element={<LoginPage />} />
          <Route path="/auth/login/admin" element={<AdminLoginPage />} />

          <Route element={<ProtectedRoute />}>
            <Route element={<Layout />}>
              <Route index element={<Navigate to="/dashboard" replace />} />
              <Route path="/dashboard" element={<DashboardPage />} />
              <Route path="/ausencias" element={<VacationsPage />} />
              <Route path="/vacations" element={<Navigate to="/ausencias" replace />} />
              <Route path="/approvals" element={<ApprovalsPage />} />
              <Route path="/reports" element={<ReportsPage />} />
              <Route path="/configuracoes" element={<SettingsPage />} />
              <Route path="/settings" element={<Navigate to="/configuracoes" replace />} />
              <Route path="/profile" element={<ProfilePage />} />
              <Route
                path="/users"
                element={
                  <AdminOnly>
                    <UsersPage />
                  </AdminOnly>
                }
              />
              <Route
                path="/cargos"
                element={
                  <AdminOnly>
                    <CargosPage />
                  </AdminOnly>
                }
              />
              <Route
                path="/teams"
                element={
                  <AdminOnly>
                    <TeamsPage />
                  </AdminOnly>
                }
              />
              <Route
                path="/teams/:id"
                element={
                  <AdminOnly>
                    <TeamDetailPage />
                  </AdminOnly>
                }
              />
            </Route>
          </Route>

          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </Router>
      <Toaster position="top-right" />
    </QueryClientProvider>
  )
}
