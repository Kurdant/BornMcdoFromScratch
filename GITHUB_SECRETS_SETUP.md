# Configuration des Secrets GitHub Actions

Pour que le pipeline CI/CD fonctionne, configure les secrets suivants dans GitHub:

**Settings → Secrets and variables → Actions → New repository secret**

## Secrets obligatoires

| Secret | Valeur | Description |
|--------|--------|-------------|
| `REGISTRY_HOST` | `hugo-registry.a3n.fr` | URL du registry Docker privé |
| `REGISTRY_USERNAME` | `hugo` | Username pour se connecter au registry |
| `REGISTRY_PASSWORD` | `Kurdant480!` | Password pour se connecter au registry |
| `VPS_HOST` | À définir (ex: `wakdo-prod.example.com`) | IP ou domaine du VPS |
| `VPS_USER` | À définir (ex: `deploy`, `root`) | Utilisateur SSH sur le VPS |
| `VPS_SSH_KEY` | Voir ci-dessous | Clé privée SSH (format PEM) |
| `VPS_APP_PATH` | À définir (ex: `/opt/wcdo`) | Chemin d'installation sur le VPS |

## Comment configurer VPS_SSH_KEY

1. Ouvre ton terminal
2. Copie ta clé privée SSH complète (avec BEGIN et END)
3. Va sur GitHub: Settings → Secrets and variables → Actions → New repository secret
4. Nom: `VPS_SSH_KEY`
5. Valeur: colle toute la clé (elle a déjà un début et une fin)

**Format attendu:**
```
-----BEGIN OPENSSH PRIVATE KEY-----
[contenu...]
-----END OPENSSH PRIVATE KEY-----
```

## Workflow déclenchement

- **Tests**: À chaque push/PR sur `main` et `develop`
- **Build + Push**: Seulement sur push vers `main` après tests OK
- **Deploy**: Seulement sur push vers `main` après build/push OK

## Vérification

Après avoir configuré les secrets, fais un test:
```bash
git push origin main
```

Regarde les actions sur GitHub: https://github.com/[ton-repo]/actions

## Étapes du pipeline

1. ✅ **Test** - Lance PHPUnit
2. ✅ **Build & Push** - Crée l'image Docker et la pousse au registry
3. ✅ **Deploy** - Se connecte au VPS et redéploie les services

