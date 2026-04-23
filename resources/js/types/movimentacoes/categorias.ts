export interface Categoria {
  id: number;
  nome: string;
  tipo: 'ganho' | 'gasto' | 'gasto futuro';
  user_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface Paginated<T> {
  data: T[];
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
  current_page: number;
  from: number;
  last_page: number;
  path: string;
  per_page: number;
  to: number;
  total: number;
}

export interface Filter {
  search: string;
  tipo: 'ganho' | 'gasto' | 'gasto futuro';
  per_page: number;
}
