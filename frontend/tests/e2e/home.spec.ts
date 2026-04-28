import { test, expect } from '@playwright/test'

test('home page displays app title', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { name: 'Money Manager' })).toBeVisible()
})
