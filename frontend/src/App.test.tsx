import { render, screen, within } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import App from './App'

function json(body: unknown, status = 200): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { 'Content-Type': 'application/json' },
  })
}

type Directory = {
  tenants: { id: number; name: string }[]
  rooms: { id: number; name: string }[]
  children: { id: number; name: string }[]
  occupants: { check_in_id: number; child_id: number; name: string }[]
}

function mockApi(directory: Directory) {
  vi.stubGlobal(
    'fetch',
    vi.fn(async (url: string, init?: RequestInit) => {
      const method = init?.method ?? 'GET'
      if (url === '/api/tenants' && method === 'GET') {
        return json(directory.tenants)
      }
      if (url === '/api/rooms' && method === 'GET') {
        return json(directory.rooms)
      }
      if (url === '/api/children' && method === 'GET') {
        return json(directory.children)
      }
      if (url === '/api/rooms' && method === 'POST') {
        directory.rooms = [...directory.rooms, { id: 8, name: 'Little Explorers' }]
        return json({ id: 8, name: 'Little Explorers' }, 201)
      }
      if (url === '/api/rooms/4/occupants') {
        return json(directory.occupants)
      }
      if (url === '/api/rooms/4/check-ins' && method === 'POST') {
        directory.occupants = [{ check_in_id: 12, child_id: 3, name: 'Ava' }]
        return json({ id: 12 }, 201)
      }
      if (url === '/api/check-ins/9/check-out' && method === 'POST') {
        directory.occupants = []
        return json({ id: 9 })
      }
      return json({ message: `Unexpected ${method} ${url}` }, 500)
    }),
  )
}

async function useGroupA(user: ReturnType<typeof userEvent.setup>) {
  expect(await screen.findByText('Group A')).toBeInTheDocument()
  await user.click(screen.getByRole('button', { name: 'Use' }))
  expect(await screen.findByRole('heading', { name: 'Rooms' })).toBeInTheDocument()
}

describe('directory tables', () => {
  it('shows nursery groups, room names, and child names', async () => {
    const user = userEvent.setup()
    mockApi({
      tenants: [{ id: 1, name: 'Group A' }],
      rooms: [{ id: 4, name: 'Sunflowers' }],
      children: [{ id: 3, name: 'Ava' }],
      occupants: [],
    })

    render(<App />)
    await useGroupA(user)

    expect(screen.getByRole('heading', { name: 'Rooms' })).toBeInTheDocument()
    expect(screen.getByText('Sunflowers')).toBeInTheDocument()
    expect(screen.queryByText('Ava')).not.toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Children' }))

    expect(await screen.findByRole('heading', { name: 'Children' })).toBeInTheDocument()
    expect(screen.getByText('Ava')).toBeInTheDocument()
    expect(screen.queryByText('Sunflowers')).not.toBeInTheDocument()
  })

  it('adds a room by name', async () => {
    const user = userEvent.setup()
    mockApi({
      tenants: [{ id: 1, name: 'Group A' }],
      rooms: [],
      children: [],
      occupants: [],
    })

    render(<App />)
    await useGroupA(user)

    const rooms = screen.getByRole('heading', { name: 'Rooms' }).closest('section')
    if (rooms === null) {
      throw new Error('Rooms table is missing')
    }
    await user.type(within(rooms).getByLabelText('Room name'), 'Little Explorers')
    await user.click(within(rooms).getByRole('button', { name: 'Add' }))

    expect(await within(rooms).findByText('Little Explorers')).toBeInTheDocument()
  })

  it('opens a room by name and checks a child in by name', async () => {
    const user = userEvent.setup()
    mockApi({
      tenants: [{ id: 1, name: 'Group A' }],
      rooms: [{ id: 4, name: 'Sunflowers' }],
      children: [{ id: 3, name: 'Ava' }],
      occupants: [],
    })

    render(<App />)
    await useGroupA(user)
    await user.click(screen.getByRole('button', { name: 'Open' }))

    const boardHeading = await screen.findByRole('heading', { name: 'Sunflowers' })
    const board = boardHeading.closest('section')
    if (board === null) {
      throw new Error('Room board is missing')
    }
    expect(within(board).getByText('No children in this room.')).toBeInTheDocument()

    await user.selectOptions(screen.getByLabelText('Check a child in'), 'Ava')
    await user.click(screen.getByRole('button', { name: 'Check in' }))

    expect(await within(board).findByRole('cell', { name: 'Ava' })).toBeInTheDocument()
  })

  it('checks a named child out of the room', async () => {
    const user = userEvent.setup()
    mockApi({
      tenants: [{ id: 1, name: 'Group A' }],
      rooms: [{ id: 4, name: 'Sunflowers' }],
      children: [{ id: 3, name: 'Ava' }],
      occupants: [{ check_in_id: 9, child_id: 3, name: 'Ava' }],
    })

    render(<App />)
    await useGroupA(user)
    await user.click(screen.getByRole('button', { name: 'Open' }))

    const boardHeading = await screen.findByRole('heading', { name: 'Sunflowers' })
    const board = boardHeading.closest('section')
    if (board === null) {
      throw new Error('Room board is missing')
    }
    expect(await within(board).findByRole('cell', { name: 'Ava' })).toBeInTheDocument()
    await user.click(within(board).getByRole('button', { name: 'Check out' }))

    expect(await within(board).findByText('No children in this room.')).toBeInTheDocument()
  })
})
