import { useState } from 'react'
import type { NamedRecord } from '../api'

type NameTableProps = {
  title: string
  fieldLabel: string
  rows: NamedRecord[]
  empty: string
  onCreate: (name: string) => Promise<void>
  onUpdate: (id: number, name: string) => Promise<void>
  onDelete: (id: number) => Promise<void>
  onUse?: (row: NamedRecord) => void
  useLabel?: string
}

export function NameTable({
  title,
  fieldLabel,
  rows,
  empty,
  onCreate,
  onUpdate,
  onDelete,
  onUse,
  useLabel = 'Open',
}: NameTableProps) {
  const [draft, setDraft] = useState('')
  const [editingId, setEditingId] = useState<number | null>(null)
  const [editingName, setEditingName] = useState('')
  const [busy, setBusy] = useState(false)

  async function run(action: () => Promise<void>) {
    setBusy(true)
    try {
      await action()
    } catch {
      // The page stores the message above the tables.
    } finally {
      setBusy(false)
    }
  }

  return (
    <section className="card">
      <h2>{title}</h2>
      {rows.length === 0 ? <p className="empty">{empty}</p> : null}
      {rows.length > 0 ? (
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.id}>
                <td>
                  {editingId === row.id ? (
                    <input
                      aria-label={`Edit ${row.name}`}
                      value={editingName}
                      onChange={(event) => setEditingName(event.target.value)}
                    />
                  ) : (
                    row.name
                  )}
                </td>
                <td className="row-actions">
                  {editingId === row.id ? (
                    <button
                      type="button"
                      disabled={busy}
                      onClick={() =>
                        void run(async () => {
                          await onUpdate(row.id, editingName)
                          setEditingId(null)
                        })
                      }
                    >
                      Save
                    </button>
                  ) : (
                    <button
                      type="button"
                      className="ghost"
                      onClick={() => {
                        setEditingId(row.id)
                        setEditingName(row.name)
                      }}
                    >
                      Edit
                    </button>
                  )}
                  <button
                    type="button"
                    className="ghost"
                    disabled={busy}
                    onClick={() => void run(() => onDelete(row.id))}
                  >
                    Delete
                  </button>
                  {onUse ? (
                    <button type="button" onClick={() => onUse(row)}>
                      {useLabel}
                    </button>
                  ) : null}
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
          void run(async () => {
            await onCreate(draft)
            setDraft('')
          })
        }}
      >
        <label htmlFor={fieldLabel}>{fieldLabel}</label>
        <div className="check-in-row">
          <input
            id={fieldLabel}
            value={draft}
            onChange={(event) => setDraft(event.target.value)}
            required
          />
          <button type="submit" disabled={busy}>
            Add
          </button>
        </div>
      </form>
    </section>
  )
}
