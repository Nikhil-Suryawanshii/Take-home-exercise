export type Page = 'groups' | 'rooms' | 'children' | 'here'

const items: { page: Page; label: string }[] = [
  { page: 'groups', label: 'Nursery groups' },
  { page: 'rooms', label: 'Rooms' },
  { page: 'children', label: 'Children' },
  { page: 'here', label: 'Who is here' },
]

type NavBarProps = {
  page: Page
  groupName: string | null
  onChange: (page: Page) => void
}

export function NavBar({ page, groupName, onChange }: NavBarProps) {
  return (
    <nav className="nav" aria-label="Sections">
      {items.map((item) => (
        <button
          key={item.page}
          type="button"
          aria-current={page === item.page ? 'page' : undefined}
          onClick={() => onChange(item.page)}
        >
          {item.label}
        </button>
      ))}
      <span className="nav-group">{groupName ? `Using ${groupName}` : 'No nursery group selected'}</span>
    </nav>
  )
}
