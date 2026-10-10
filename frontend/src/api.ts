export type NamedRecord = {
  id: number
  name: string
}

export type Occupant = {
  check_in_id: number
  child_id: number
  name: string
}

async function request(path: string, tenantId: string | null, init: RequestInit = {}): Promise<Response> {
  const headers = new Headers(init.headers)
  headers.set('Accept', 'application/json')
  if (tenantId !== null) {
    headers.set('X-Tenant-ID', tenantId)
  }
  if (init.body !== undefined) {
    headers.set('Content-Type', 'application/json')
  }

  const response = await fetch(path, { ...init, headers })

  if (!response.ok) {
    const body = (await response.json().catch(() => null)) as { message?: string } | null
    throw new Error(body?.message ?? `Request failed (${response.status})`)
  }

  return response
}

function readList(path: string, tenantId: string | null): Promise<NamedRecord[]> {
  return request(path, tenantId).then((response) => response.json())
}

export function listTenants(): Promise<NamedRecord[]> {
  return readList('/api/tenants', null)
}

export function createTenant(name: string): Promise<NamedRecord> {
  return request('/api/tenants', null, {
    method: 'POST',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function updateTenant(id: number, name: string): Promise<NamedRecord> {
  return request(`/api/tenants/${id}`, null, {
    method: 'PUT',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function deleteTenant(id: number): Promise<void> {
  return request(`/api/tenants/${id}`, null, { method: 'DELETE' }).then(() => undefined)
}

export function listRooms(tenantId: string): Promise<NamedRecord[]> {
  return readList('/api/rooms', tenantId)
}

export function createRoom(tenantId: string, name: string): Promise<NamedRecord> {
  return request('/api/rooms', tenantId, {
    method: 'POST',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function updateRoom(tenantId: string, id: number, name: string): Promise<NamedRecord> {
  return request(`/api/rooms/${id}`, tenantId, {
    method: 'PUT',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function deleteRoom(tenantId: string, id: number): Promise<void> {
  return request(`/api/rooms/${id}`, tenantId, { method: 'DELETE' }).then(() => undefined)
}

export function listChildren(tenantId: string): Promise<NamedRecord[]> {
  return readList('/api/children', tenantId)
}

export function createChild(tenantId: string, name: string): Promise<NamedRecord> {
  return request('/api/children', tenantId, {
    method: 'POST',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function updateChild(tenantId: string, id: number, name: string): Promise<NamedRecord> {
  return request(`/api/children/${id}`, tenantId, {
    method: 'PUT',
    body: JSON.stringify({ name }),
  }).then((response) => response.json())
}

export function deleteChild(tenantId: string, id: number): Promise<void> {
  return request(`/api/children/${id}`, tenantId, { method: 'DELETE' }).then(() => undefined)
}

export function listOccupants(tenantId: string, roomId: string): Promise<Occupant[]> {
  return request(`/api/rooms/${roomId}/occupants`, tenantId).then((response) => response.json())
}

export function checkIn(tenantId: string, roomId: string, childId: string): Promise<void> {
  return request(`/api/rooms/${roomId}/check-ins`, tenantId, {
    method: 'POST',
    body: JSON.stringify({ child_id: Number(childId) }),
  }).then(() => undefined)
}

export function checkOut(tenantId: string, checkInId: number): Promise<void> {
  return request(`/api/check-ins/${checkInId}/check-out`, tenantId, {
    method: 'POST',
  }).then(() => undefined)
}
