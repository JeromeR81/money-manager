import { describe, it, expect } from 'vitest'
import { cn } from '../lib/utils'

describe('cn utility', () => {
  it('merges class names', () => {
    expect(cn('foo', 'bar')).toBe('foo bar')
  })

  it('deduplicates tailwind classes via tailwind-merge', () => {
    expect(cn('p-4', 'p-2')).toBe('p-2')
  })
})
