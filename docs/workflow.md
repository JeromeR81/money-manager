# Workflow des features

## Gates de validation

Les ⏸ gates sont des points d'arrêt où l'utilisateur doit valider avant que le flux continue. Aucun agent ne démarre après un gate sans feu vert explicite.

| Gate | Flux | Après | Avant |
|---|---|---|---|
| ⏸ Gate 1 | US | PO | Architecte |
| ⏸ Gate 2 | US | Architecte | Backend Developer + UI/UX Designer |
| ⏸ Gate 3 | US | UI/UX Designer | Frontend Developer |
| ⏸ Gate 4 | US | Security & Code Reviewer | QA |
| ⏸ Gate 5 | US | QA | Documentaliste + DevOps |
| ⏸ Gate TS1 | TS-Technique, TS-Transverse | Architecte (rédaction TS) | Backend/Frontend Developer |
| ⏸ Gate TSI1 | TS-Infra | DevOps (rédaction TS) | DevOps (implémentation) |
| ⏸ Gate TS2 / TSI2 | TS-Technique / TS-Infra | Security & Code Reviewer | QA / merge |
| ⏸ Gate TS3 | TS-Technique | QA | DevOps |

## Flux US (User Story)

```
PO
└── Rédige la user story + critères d'acceptance
    │
    ▼
⏸ Gate 1 — validation utilisateur
    │
    ▼
Architecte
└── Définit le contrat API (endpoints, types partagés)
    └── Valide le modèle de données
    │
    ▼
⏸ Gate 2 — validation utilisateur
    │
    ▼
┌──────────────────────┬──────────────────────┐
Backend Developer      UI/UX Designer
└── Entité + migration └── Maquettes
└── Service + tests    └── Specs design
└── API Resource       │
    │                  ▼
    │              ⏸ Gate 3 — validation utilisateur
    │                  │
    │         Frontend Developer
    │         └── Routes + composants
    │         └── Queries (mockées puis réelles)
    │         └── Tests
    │                  │
    └──────────────────┘
                │
                ▼
    Security & Code Reviewer
        └── Revue sécurité + qualité
                │
                ▼
        ⏸ Gate 4 — validation utilisateur
                │
                ▼
               QA
        └── Tests d'intégration + E2E
                │
                ▼
        ⏸ Gate 5 — validation utilisateur
                │
         ┌──────┴──────┐
   Documentaliste    DevOps
   └── Docs API      └── Déploiement
```

**Points clés :**
- Backend et UI/UX travaillent en parallèle — ils sont indépendants
- Frontend démarre dès que l'UI/UX a terminé **et que le Gate 3 est franchi**
- Frontend branche sur la vraie API quand le Backend est prêt
- Security & Code Reviewer intervient avant QA — on corrige avant de valider
- QA valide sur du code déjà revu et sécurisé

## Flux TS (Technical Story)

Trois types de TS, chacun avec son propre flux.

### TS-Infra — infrastructure, Docker, CI/CD, secrets

```
DevOps
└── Rédige la TS + liste de tâches
    │
    ▼
⏸ Gate TSI1 — validation utilisateur
    │
    ▼
DevOps
└── Implémente
    │
    ▼
Security & Code Reviewer
    └── Revue sécurité + qualité
        │
        ▼
⏸ Gate TSI2 — validation utilisateur
        │
        ▼
      merge
```

### TS-Technique — migration, refactoring, modèle de données

```
Architecte
└── Rédige la TS + liste de tâches
    └── Spécifie le contrat technique
        │
        ▼
⏸ Gate TS1 — validation utilisateur
        │
        ▼
Backend Developer (et/ou Frontend Developer)
└── Implémente les tâches
    │
    ▼
Security & Code Reviewer
    └── Revue sécurité + qualité
        │
        ▼
⏸ Gate TS2 — validation utilisateur
        │
        ▼
       QA
└── Tests de non-régression
        │
        ▼
⏸ Gate TS3 — validation utilisateur
        │
        ▼
     DevOps
└── Déploiement
```

### TS-Transverse — cross-cutting (auth, observabilité, typage partagé)

Le PO choisit l'initiateur (Architecte ou DevOps), puis le flux TS-Technique s'applique.

**Points clés :**
- Le PO n'intervient pas dans les TS sauf pour les TS-Transverses (choix de l'initiateur)
- L'UI/UX Designer n'intervient jamais dans un flux TS
- Le QA est allégé sur les TS : tests de non-régression uniquement, pas de validation des critères d'acceptance métier
- Une TS-Infra sans impact sur le code applicatif ne nécessite pas de passage QA
