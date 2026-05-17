export interface Region {
  id: string;
  code: string;
  name: string;
}

export interface Locality {
  id: string;
  name: string;
  regionId: string;
}
