# Contrat API — Authentification Frontend (US 18)

## Endpoints existants (backend livré, issues #16 et #17)

### POST /api/auth/login

**Rôle :** authentifier l'utilisateur et poser les cookies JWT.

**Request body :**
```json
{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Réponses :**

| Code | Body | Cookies posés |
|------|------|---------------|
| 200 | `{ "message": "Authenticated" }` | `BEARER` (HttpOnly, 15 min) + `REFRESH_TOKEN` (HttpOnly, 7 jours) |
| 401 | `{ "message": "Invalid credentials" }` | — |
| 429 | `{ "message": "Too many requests" }` | — |

**Contraintes :**
- Rate limit : 5 requêtes / 60 secondes par IP (sliding window)
- En cas d'erreur (champ vide, email invalide), le backend retourne systématiquement 401 `"Invalid credentials"` — pas de détail sur la cause (protection timing)

---

### POST /api/auth/logout

**Rôle :** invalider le refresh token en base et effacer les cookies.

**Request body :** aucun

**Réponses :**

| Code | Body | Cookies posés |
|------|------|---------------|
| 200 | `{ "message": "Logged out" }` | `BEARER` et `REFRESH_TOKEN` vidés (expiry = 1) |

**Comportement :** accessible sans authentification valide — le logout fonctionne même si le BEARER est expiré (public firewall).

---

### POST /api/auth/refresh

**Rôle :** renouveler silencieusement l'access token via le refresh token (rotation unique).

**Request body :** aucun (lit le cookie `REFRESH_TOKEN`)

**Réponses :**

| Code | Body | Cookies posés |
|------|------|---------------|
| 200 | `{ "message": "Token refreshed" }` | `BEARER` + `REFRESH_TOKEN` renouvelés |
| 401 | `{ "message": "Invalid refresh token" }` | — |
| 429 | `{ "message": "Too many requests" }` | — |

**Comportement :** le refresh token est consommé à usage unique (rotation) — l'ancien est supprimé, un nouveau est généré.

---

## Endpoint manquant — à implémenter par le Backend Developer

### GET /api/auth/me

**Rôle :** permettre au frontend de vérifier l'état d'authentification sans exposer le JWT à JavaScript.

**Motivation :** les cookies étant HttpOnly, JavaScript ne peut pas lire le BEARER. Le frontend a besoin d'un endpoint pour savoir si la session est active lors du `beforeLoad` des routes protégées.

**Request body :** aucun (lit le cookie `BEARER` via `CookieTokenExtractor`)

**Réponses :**

| Code | Body |
|------|------|
| 200 | `{ "email": "user@example.com" }` |
| 401 | `{ "code": 401, "message": "JWT Token not found" }` *(réponse standard API Platform)* |

**Firewall Symfony :** route sous le firewall `api` (authentifiée) — access_control `ROLE_USER`.

**Contrainte :** pas de rate limit spécifique (appelé uniquement depuis `beforeLoad`, pas en boucle).

**Payload minimal intentionnel :** l'application est mono-utilisateur, seul l'email est nécessaire pour confirmer l'identité.

---

## Récapitulatif des codes d'erreur à gérer côté frontend

| Endpoint | Code | Signification pour le frontend |
|----------|------|-------------------------------|
| POST /login | 401 | Identifiants invalides — afficher message d'erreur |
| POST /login | 429 | Trop de tentatives — désactiver le bouton temporairement |
| GET /me | 401 | Session expirée — tenter refresh, sinon redirect /login |
| POST /refresh | 401 | Refresh token invalide/expiré — redirect /login |
| Tout endpoint protégé | 401 | Tenter refresh silencieux avant d'abandonner |
