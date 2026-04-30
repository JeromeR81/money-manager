import { test, expect, type BrowserContext } from '@playwright/test'

// Dev fixtures credentials (UserFixtures.php)
const VALID_EMAIL = 'user@money-manager.local'
const VALID_PASSWORD = 'password'
const API_URL = 'http://localhost:8080/api'

async function loginViaApi(context: BrowserContext): Promise<void> {
  await context.request.post(`${API_URL}/auth/login`, {
    data: { email: VALID_EMAIL, password: VALID_PASSWORD },
  })
}

// --- Scenario: Connexion réussie ---
test('connexion réussie — redirige vers la page d\'accueil', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('Adresse e-mail').fill(VALID_EMAIL)
  await page.getByLabel('Mot de passe').fill(VALID_PASSWORD)
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page).toHaveURL('/')
  await expect(page.getByRole('heading', { name: 'Money Manager' })).toBeVisible()
})

// --- Scenario: Connexion échouée — mauvais mot de passe ---
test('connexion échouée — message d\'erreur affiché, reste sur /login', async ({ page }) => {
  await page.goto('/login')
  await page.getByLabel('Adresse e-mail').fill(VALID_EMAIL)
  await page.getByLabel('Mot de passe').fill('mauvais-mot-de-passe')
  await page.getByRole('button', { name: 'Se connecter' }).click()

  await expect(page.getByRole('alert')).toContainText('Identifiants invalides')
  await expect(page).toHaveURL('/login')
})

// --- Scenario: Accès direct à une page protégée sans être connecté ---
test('accès direct à une page protégée sans session — redirige vers /login', async ({ page }) => {
  await page.goto('/')
  await expect(page).toHaveURL('/login')
})

// --- Scenario: Déconnexion ---
test('déconnexion — redirige vers /login et efface la session', async ({ page, context }) => {
  await loginViaApi(context)
  await page.goto('/')

  await expect(page.getByRole('heading', { name: 'Money Manager' })).toBeVisible()

  await page.getByRole('button', { name: 'Se déconnecter' }).click()

  await expect(page).toHaveURL('/login')

  // Vérifier que la session est bien révoquée : recharger / doit rediriger
  await page.goto('/')
  await expect(page).toHaveURL('/login')
})

// --- Scenario: Déjà connecté sur /login — redirige vers / ---
test('utilisateur déjà connecté sur /login — redirige vers /', async ({ page, context }) => {
  await loginViaApi(context)
  await page.goto('/login')
  await expect(page).toHaveURL('/')
})
