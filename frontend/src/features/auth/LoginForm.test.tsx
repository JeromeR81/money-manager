import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { LoginForm } from './LoginForm'

describe('LoginForm', () => {
  it('renders email and password fields with a submit button', () => {
    render(<LoginForm onSubmit={vi.fn()} isLoading={false} error={null} />)
    expect(screen.getByLabelText('Adresse e-mail')).toBeTruthy()
    expect(screen.getByLabelText('Mot de passe')).toBeTruthy()
    expect(screen.getByRole('button', { name: 'Se connecter' })).toBeTruthy()
  })

  it('calls onSubmit with email and password on form submission', async () => {
    const onSubmit = vi.fn()
    const user = userEvent.setup()
    render(<LoginForm onSubmit={onSubmit} isLoading={false} error={null} />)

    await user.type(screen.getByLabelText('Adresse e-mail'), 'user@money-manager.local')
    await user.type(screen.getByLabelText('Mot de passe'), 'password')
    await user.click(screen.getByRole('button', { name: 'Se connecter' }))

    expect(onSubmit).toHaveBeenCalledOnce()
    expect(onSubmit).toHaveBeenCalledWith({
      email: 'user@money-manager.local',
      password: 'password',
    })
  })

  it('clears the password field after submission', async () => {
    const user = userEvent.setup()
    render(<LoginForm onSubmit={vi.fn()} isLoading={false} error={null} />)

    const passwordField = screen.getByLabelText('Mot de passe') as HTMLInputElement
    await user.type(passwordField, 'secret')
    await user.click(screen.getByRole('button', { name: 'Se connecter' }))

    expect(passwordField.value).toBe('')
  })

  it('shows the error message in an alert role element', () => {
    render(<LoginForm onSubmit={vi.fn()} isLoading={false} error="Identifiants invalides" />)
    expect(screen.getByRole('alert').textContent).toBe('Identifiants invalides')
  })

  it('shows no alert when error is null', () => {
    render(<LoginForm onSubmit={vi.fn()} isLoading={false} error={null} />)
    expect(screen.queryByRole('alert')).toBeNull()
  })

  it('disables the submit button and input fields while loading', () => {
    render(<LoginForm onSubmit={vi.fn()} isLoading={true} error={null} />)
    const button = screen.getByRole('button') as HTMLButtonElement
    expect(button.disabled).toBe(true)
    expect((screen.getByLabelText('Adresse e-mail') as HTMLInputElement).disabled).toBe(true)
    expect((screen.getByLabelText('Mot de passe') as HTMLInputElement).disabled).toBe(true)
  })
})
