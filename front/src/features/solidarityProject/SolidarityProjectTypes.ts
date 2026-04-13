export interface SolidarityProject {
  id: string;
  title: string;
  description: string;
  imageUrl: string | null;
  status: 'active' | 'closed';
  createdAt: string;
}
