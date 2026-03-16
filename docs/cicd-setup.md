# CI/CD Setup Guide - WCDO

## Architecture du pipeline

```
[Local] push dev
    → [GitHub] detecte le push
        → [stark - self-hosted runner] checkout → tests → build image → push registry → deploy
    → verification manuelle
    → [Local] push prod
        → [GitHub]
            → [stark - self-hosted runner] retag :dev → :prod
            → [GitHub runner] SSH vision → docker compose pull :prod → up
            → health check https://wakdo-back.acadenice.fr/api/health
            → merge prod → main
```

---

## Etape 1 — Installer le self-hosted runner sur stark

Se connecter sur stark :

```bash
ssh hugo@stark.a3n.fr
```

### 1.1 Creer le dossier et telecharger le runner

```bash
mkdir -p /home/hugo/actions-runner
cd /home/hugo/actions-runner

curl -o actions-runner-linux-x64.tar.gz -L \
  https://github.com/actions/runner/releases/download/v2.323.0/actions-runner-linux-x64-2.323.0.tar.gz

tar xzf actions-runner-linux-x64.tar.gz
```

### 1.2 Obtenir le token de registration

Sur GitHub : **repo → Settings → Actions → Runners → New self-hosted runner**
- Choisir : Linux / x64
- Copier la commande `./config.sh --url ... --token ...`

### 1.3 Configurer le runner

```bash
cd /home/hugo/actions-runner

# Coller la commande copiee depuis GitHub, exemple :
./config.sh --url https://github.com/TON_USER/BornMcdoFromScratch2 --token TON_TOKEN

# Questions posees :
# - Enter the name of the runner group: appuyer Entree (default)
# - Enter the name of runner: taper "stark" puis Entree
# - Enter any additional labels: appuyer Entree (default)
# - Enter name of work folder: appuyer Entree (default)
```

### 1.4 Installer et demarrer le runner comme service systemd

```bash
cd /home/hugo/actions-runner

sudo ./svc.sh install hugo
sudo ./svc.sh start

# Verifier que le service tourne
sudo ./svc.sh status
```

### 1.5 Verifier sur GitHub

Sur GitHub : **repo → Settings → Actions → Runners**
Le runner "stark" doit apparaitre en vert avec le statut **Idle**.

---

## Etape 2 — Verifier les prerequis sur stark

Le runner va executer les jobs dans `/home/hugo/actions-runner/_work/`.
Verifier que les outils sont accessibles :

```bash
# PHP et composer
php -v
composer -v

# Extensions PHP requises
php -m | grep -E "pdo|json"

# Docker (accessible sans sudo pour l'user hugo)
docker -v
docker compose version
docker ps

# Si docker necessite sudo, ajouter hugo au groupe docker
sudo usermod -aG docker hugo
# Puis se reconnecter pour que ca prenne effet
```

---

## Etape 3 — Configurer vision pour le deploiement prod

Le deploy sur vision se fait toujours via SSH depuis un runner GitHub (ubuntu-latest).
Il faut donc une cle SSH sans passphrase pour vision uniquement.

### 3.1 Generer une cle SSH CI/CD (depuis ta machine locale)

```powershell
ssh-keygen -t ed25519 -C "github-actions-vision" -f "$env:USERPROFILE\.ssh\github_actions_vision"
# Appuyer deux fois sur Entree = pas de passphrase
```

### 3.2 Ajouter la cle publique sur vision

```powershell
type "$env:USERPROFILE\.ssh\github_actions_vision.pub" | ssh hugo@vision.a3n.fr "cat >> ~/.ssh/authorized_keys"
```

### 3.3 Verifier

```powershell
ssh -i "$env:USERPROFILE\.ssh\github_actions_vision" hugo@vision.a3n.fr "echo OK vision"
```

### 3.4 Setup du dossier sur vision

```bash
ssh hugo@vision.a3n.fr

mkdir -p /home/hugo/wcdo
cd /home/hugo/wcdo

# Le docker-compose.yml doit etre present avec l'image :prod
# Le .env de production doit etre present
ls -la
```

---

## Etape 4 — Secrets GitHub a configurer

**GitHub repo → Settings → Secrets and variables → Actions**

| Secret | Valeur | Utilise par |
|--------|--------|-------------|
| `REGISTRY_USER` | Login hugo-registry.a3n.fr | stark runner + vision |
| `REGISTRY_PASSWORD` | Mot de passe registry | stark runner + vision |
| `VISION_SSH_KEY` | Contenu de `~\.ssh\github_actions_vision` (privee) | deploy vision |
| `VISION_SSH_PASSPHRASE` | Laisser vide si cle sans passphrase | deploy vision |
| `GH_TOKEN` | Personal Access Token (scope: repo) | merge prod→main |

Supprimer si presents : `STARK_SSH_KEY`, `STARK_SSH_PASSPHRASE` (plus necessaires).

---

## Etape 5 — Setup docker-compose sur vision

Copier le docker-compose depuis local vers vision :

```powershell
scp "docker-compose.yml" hugo@vision.a3n.fr:/home/hugo/wcdo/docker-compose.yml
```

Sur vision, verifier que le service php utilise bien l'image prod :

```bash
cat /home/hugo/wcdo/docker-compose.yml | grep image
# doit afficher : image: hugo-registry.a3n.fr/wcdo:prod
```

Creer le `.env` de production sur vision :

```bash
nano /home/hugo/wcdo/.env
```

---

## Flux de travail quotidien

### Deployer sur dev (stark)

```bash
git checkout dev
git add .
git commit -m "feat: description"
git push origin dev
# stark runner : tests → build → push :dev → docker compose up
```

### Promouvoir en production (vision)

```bash
git checkout prod
git merge dev
git push origin prod
# stark runner : retag :dev→:prod
# GitHub runner : SSH vision → docker compose pull :prod → up
# health check → merge prod→main automatique
```

---

## Troubleshooting

### Runner ne demarre pas
```bash
cd /home/hugo/actions-runner
sudo ./svc.sh status
journalctl -u actions.runner.* -f
```

### docker: permission denied sur le runner
```bash
sudo usermod -aG docker hugo
# Redemarrer le runner
sudo ./svc.sh stop
sudo ./svc.sh start
```

### Le runner reste Offline sur GitHub
```bash
cd /home/hugo/actions-runner
sudo ./svc.sh restart
```

