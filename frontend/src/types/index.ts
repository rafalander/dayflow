export type UserRole = 'admin' | 'user';

export interface Cargo {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  role: UserRole;
  level: number;
  created_at: string;
  updated_at: string;
}

export interface Team {
  id: number;
  name: string;
  description: string | null;
  color: string;
  lead_id: number;
  lead?: User;
  members_count?: number;
  created_at: string;
  updated_at: string;
}

/** Resposta de GET /teams/:id — árvore sob o gestor (manager_id dentro do time). */
export interface HierarchyNode {
  id: number;
  name: string;
  email: string;
  display_avatar: string | null;
  role: UserRole;
  level: number;
  cargo?: Cargo | null;
  is_lead: boolean;
  children: HierarchyNode[];
}

export interface TeamDetailPayload {
  team: Team;
  hierarchy: HierarchyNode | null;
  member_ids: number[];
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  custom_avatar: string | null;
  display_avatar: string | null;
  role: UserRole;
  level: number;
  cargo_id: number | null;
  manager_id: number | null;
  team_id: number | null;
  is_active: boolean;
  last_login_at: string | null;
  created_at: string;
  updated_at: string;
  cargo?: Cargo;
  manager?: User;
  subordinates?: User[];
  vacationRequests?: VacationRequest[];
}

/** Compatível com GET /roles (lista fixa para selects). */
export interface Role {
  id: number;
  name: string;
  slug: string;
  weight: number;
  color: string;
  permissions: string[] | null;
  description: string | null;
  is_admin: boolean;
  created_at?: string;
  updated_at?: string;
}

/** Paginação no formato Laravel */
export interface LaravelPaginator<T> {
  current_page: number;
  data: T[];
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface VacationRequest {
  id: number;
  user_id: number;
  approver_id: number | null;
  start_date: string;
  end_date: string;
  reason: string | null;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
  updated_at: string;
  user?: User;
  approver?: User;
  comments?: string;
}

export interface VacationReportGroup {
  user: User;
  vacations: VacationRequest[];
  total_days: number;
}

export interface Setting {
  id: number;
  key: string;
  value: string;
  description: string | null;
  created_at: string;
  updated_at: string;
}

export interface AuditLog {
  id: number;
  user_id: number;
  action: string;
  model_type: string;
  model_id: number | null;
  ip_address: string;
  user_agent: string;
  created_at: string;
  user?: User;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  status: 'success' | 'error' | 'pending';
  errors?: Record<string, string[]>;
}
