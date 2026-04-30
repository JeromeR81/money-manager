import { test, expect } from '@playwright/test'

const API_URL = 'http://localhost:8080/api'

test('home page displays app title when authenticated', async ({ page, context }) => {
  await context.request.post(`${API_URL}/auth/login`, {
    data: { email: 'user@money-manager.local', password: 'password' },
  })
  await page.goto('/')
  await expect(page.getByRole('heading', { name: 'Money Manager' })).toBeVisible()
})
