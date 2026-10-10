import { useState } from 'react'
import type { NamedRecord, Occupant } from '../api'

type RoomBoardProps = {
  groupName: string
  roomName: string
  childChoices: NamedRecord[]
  occupants: Occupant[]
  loading: boolean
  onCheckIn: (childId: string) => Promise<void>
  onCheckOut: (checkInId: number) => Promise<void>
  onBack: () => void
}

export function RoomBoard({
  groupName,
  roomName,
  childChoices,
  occupants,
  loading,
  onCheckIn,
  onCheckOut,
  onBack,
}: RoomBoardProps) {
  const [childId, setChildId] = useState('')
  const [busyId, setBusyId] = useState<number | 'check-in' | null>(null)

  async function submitCheckIn() {
    setBusyId('check-in')
    try {
      await onCheckIn(childId)
      setChildId('')
    } catch {
      // The parent stores the message and the board renders it.
    } finally {
      setBusyId(null)
    }
  }

  async function submitCheckOut(checkInId: number) {
    setBusyId(checkInId)
    try {
      await onCheckOut(checkInId)
    } catch {
      // The parent stores the message and the board renders it.
    } finally {
      setBusyId(null)
    }
  }

  return (
    <section className="card board">
      <h2>{roomName}</h2>
      <p className="lede">
        {groupName}. A child can be in only one room. Check them out before moving them.
      </p>

      {loading ? <p className="banner">Loading who is here…</p> : null}

      {!loading && occupants.length === 0 ? <p className="empty">No children in this room.</p> : null}

      {occupants.length > 0 ? (
        <table>
          <thead>
            <tr>
              <th>Child</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {occupants.map((occupant) => (
              <tr key={occupant.check_in_id}>
                <td>{occupant.name}</td>
                <td>
                  <button
                    type="button"
                    className="ghost"
                    disabled={busyId !== null}
                    onClick={() => void submitCheckOut(occupant.check_in_id)}
                  >
                    {busyId === occupant.check_in_id ? 'Checking out…' : 'Check out'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      ) : null}

      <form
        className="check-in"
        onSubmit={(event) => {
          event.preventDefault()
          void submitCheckIn()
        }}
      >
        <label htmlFor="child-id">Check a child in</label>
        <div className="check-in-row">
          <select
            id="child-id"
            value={childId}
            onChange={(event) => setChildId(event.target.value)}
            required
          >
            <option value="">Choose a child</option>
            {childChoices.map((child) => (
              <option key={child.id} value={child.id}>
                {child.name}
              </option>
            ))}
          </select>
          <button type="submit" disabled={busyId !== null || childChoices.length === 0}>
            {busyId === 'check-in' ? 'Checking in…' : 'Check in'}
          </button>
        </div>
      </form>

      <button type="button" className="ghost" onClick={onBack}>
        Close room
      </button>
    </section>
  )
}
