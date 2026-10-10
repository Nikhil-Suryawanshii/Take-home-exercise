import { useEffect, useState } from 'react'
import {
  checkIn,
  checkOut,
  createChild,
  createRoom,
  createTenant,
  deleteChild,
  deleteRoom,
  deleteTenant,
  listChildren,
  listOccupants,
  listRooms,
  listTenants,
  updateChild,
  updateRoom,
  updateTenant,
  type NamedRecord,
  type Occupant,
} from './api'
import { NameTable } from './components/NameTable'
import { NavBar, type Page } from './components/NavBar'
import { RoomBoard } from './components/RoomBoard'

export default function App() {
  const [page, setPage] = useState<Page>('groups')
  const [groups, setGroups] = useState<NamedRecord[]>([])
  const [rooms, setRooms] = useState<NamedRecord[]>([])
  const [children, setChildren] = useState<NamedRecord[]>([])
  const [group, setGroup] = useState<NamedRecord | null>(null)
  const [room, setRoom] = useState<NamedRecord | null>(null)
  const [occupants, setOccupants] = useState<Occupant[]>([])
  const [loadingRoom, setLoadingRoom] = useState(false)
  const [error, setError] = useState('')

  const tenantId = group === null ? '' : String(group.id)

  async function refreshGroups() {
    setGroups(await listTenants())
  }

  async function refreshRooms(nextTenantId: string) {
    setRooms(await listRooms(nextTenantId))
  }

  async function refreshChildren(nextTenantId: string) {
    setChildren(await listChildren(nextTenantId))
  }

  useEffect(() => {
    void refreshGroups().catch((caught: unknown) => {
      setError(caught instanceof Error ? caught.message : 'Could not load nursery groups.')
    })
  }, [])

  async function showPage(next: Page) {
    setError('')
    setPage(next)
    if (group === null || next === 'groups') {
      return
    }
    try {
      if (next === 'rooms' || next === 'here') {
        await refreshRooms(tenantId)
      }
      if (next === 'children' || next === 'here') {
        await refreshChildren(tenantId)
      }
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'Could not load this section.')
    }
  }

  async function chooseGroup(next: NamedRecord) {
    setError('')
    setGroup(next)
    setRoom(null)
    setOccupants([])
    setPage('rooms')
    try {
      await refreshRooms(String(next.id))
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : 'Could not load rooms.')
    }
  }

  async function openRoom(next: NamedRecord) {
    if (group === null) {
      return
    }
    setLoadingRoom(true)
    setError('')
    setRoom(next)
    setPage('here')
    try {
      const [nextChildren, nextOccupants] = await Promise.all([
        listChildren(tenantId),
        listOccupants(tenantId, String(next.id)),
      ])
      setChildren(nextChildren)
      setOccupants(nextOccupants)
    } catch (caught) {
      setOccupants([])
      setError(caught instanceof Error ? caught.message : 'Could not open the room.')
    } finally {
      setLoadingRoom(false)
    }
  }

  async function reloadOccupants() {
    if (group === null || room === null) {
      return
    }
    setOccupants(await listOccupants(tenantId, String(room.id)))
  }

  return (
    <main>
      <header className="mast">
        <p className="eyebrow">Nursery rooms</p>
        <h1>Who is here right now?</h1>
      </header>
      <NavBar page={page} groupName={group?.name ?? null} onChange={(next) => void showPage(next)} />
      {error ? <p className="banner error">{error}</p> : null}
      {page === 'groups' ? (
        <NameTable
          title="Nursery groups"
          fieldLabel="Nursery group name"
          rows={groups}
          empty="No nursery groups yet."
          useLabel="Use"
          onUse={(row) => void chooseGroup(row)}
          onCreate={async (name) => {
            setError('')
            await createTenant(name)
            await refreshGroups()
          }}
          onUpdate={async (id, name) => {
            setError('')
            await updateTenant(id, name)
            await refreshGroups()
            if (group?.id === id) {
              setGroup({ id, name })
            }
          }}
          onDelete={async (id) => {
            setError('')
            try {
              await deleteTenant(id)
              if (group?.id === id) {
                setGroup(null)
                setRoom(null)
                setRooms([])
                setChildren([])
              }
              await refreshGroups()
            } catch (caught) {
              setError(caught instanceof Error ? caught.message : 'Could not delete the nursery group.')
            }
          }}
        />
      ) : null}
      {page !== 'groups' && group === null ? (
        <section className="card">
          <p className="empty">Choose a nursery group first.</p>
          <button type="button" onClick={() => setPage('groups')}>
            Go to nursery groups
          </button>
        </section>
      ) : null}
      {page === 'rooms' && group ? (
        <NameTable
          title="Rooms"
          fieldLabel="Room name"
          rows={rooms}
          empty="No rooms yet."
          useLabel="Open"
          onUse={(row) => void openRoom(row)}
          onCreate={async (name) => {
            setError('')
            await createRoom(tenantId, name)
            await refreshRooms(tenantId)
          }}
          onUpdate={async (id, name) => {
            setError('')
            await updateRoom(tenantId, id, name)
            await refreshRooms(tenantId)
            if (room?.id === id) {
              setRoom({ id, name })
            }
          }}
          onDelete={async (id) => {
            setError('')
            try {
              await deleteRoom(tenantId, id)
              if (room?.id === id) {
                setRoom(null)
                setOccupants([])
              }
              await refreshRooms(tenantId)
            } catch (caught) {
              setError(caught instanceof Error ? caught.message : 'Could not delete the room.')
            }
          }}
        />
      ) : null}
      {page === 'children' && group ? (
        <NameTable
          title="Children"
          fieldLabel="Child name"
          rows={children}
          empty="No children yet."
          onCreate={async (name) => {
            setError('')
            await createChild(tenantId, name)
            await refreshChildren(tenantId)
          }}
          onUpdate={async (id, name) => {
            setError('')
            await updateChild(tenantId, id, name)
            await refreshChildren(tenantId)
            if (room) {
              await reloadOccupants()
            }
          }}
          onDelete={async (id) => {
            setError('')
            try {
              await deleteChild(tenantId, id)
              await refreshChildren(tenantId)
            } catch (caught) {
              setError(caught instanceof Error ? caught.message : 'Could not delete the child.')
            }
          }}
        />
      ) : null}
      {page === 'here' && group && room ? (
        <RoomBoard
          groupName={group.name}
          roomName={room.name}
          childChoices={children}
          occupants={occupants}
          loading={loadingRoom}
          onCheckIn={async (childId) => {
            setError('')
            try {
              await checkIn(tenantId, String(room.id), childId)
              await reloadOccupants()
            } catch (caught) {
              setError(caught instanceof Error ? caught.message : 'Could not check the child in.')
              throw caught
            }
          }}
          onCheckOut={async (checkInId) => {
            setError('')
            try {
              await checkOut(tenantId, checkInId)
              await reloadOccupants()
            } catch (caught) {
              setError(caught instanceof Error ? caught.message : 'Could not check the child out.')
              throw caught
            }
          }}
          onBack={() => {
            setRoom(null)
            setOccupants([])
            setError('')
            setPage('rooms')
          }}
        />
      ) : null}
      {page === 'here' && group && room === null ? (
        <section className="card">
          <h2>Who is here</h2>
          {rooms.length === 0 ? <p className="empty">No rooms yet. Add one on the Rooms page.</p> : null}
          {rooms.length > 0 ? (
            <ul className="occupants">
              {rooms.map((row) => (
                <li key={row.id}>
                  <strong>{row.name}</strong>
                  <button type="button" onClick={() => void openRoom(row)}>
                    Open
                  </button>
                </li>
              ))}
            </ul>
          ) : null}
        </section>
      ) : null}
    </main>
  )
}
