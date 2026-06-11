export interface KeyFigure {
  value: string;
  label: string;
}

export interface SolidarityProject {
  id: string;
  title: string;
  description: string;
  imageUrl: string | null;
  status: 'active' | 'closed';
  createdAt: string;
  isDefault: boolean;
  keyFigures?: KeyFigure[];
}
