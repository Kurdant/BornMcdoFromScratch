# Configuration de l'automatisation des backups pour Centralis

## Installation de la tâche cron

Pour automatiser les backups, ajoutez cette ligne à votre crontab :

```bash
# Éditer le crontab
crontab -e

# Ajouter l'une de ces lignes selon vos besoins :

# Backup quotidien à 2h00 du matin
0 2 * * * cd /home/centralis/Centralis_11_11 && /bin/bash /home/centralis/Centralis_11_11/backup.sh >> /home/centralis/backups/cron.log 2>&1

# Backup toutes les 6 heures
0 */6 * * * cd /home/centralis/Centralis_11_11 && /bin/bash /home/centralis/Centralis_11_11/backup.sh >> /home/centralis/backups/cron.log 2>&1

# Backup tous les jours à 3h00 du matin (recommandé pour la production)
0 3 * * * cd /home/centralis/Centralis_11_11 && /bin/bash /home/centralis/Centralis_11_11/backup.sh >> /home/centralis/backups/cron.log 2>&1

# Backup hebdomadaire le dimanche à 4h00 du matin
0 4 * * 0 cd /home/centralis/Centralis_11_11 && /bin/bash /home/centralis/Centralis_11_11/backup.sh >> /home/centralis/backups/cron.log 2>&1
```

## Configuration alternative avec systemd timer (recommandé)

### 1. Créer le service systemd

Créez le fichier `/etc/systemd/system/centralis-backup.service` :

```ini
[Unit]
Description=Centralis Docker Backup
After=docker.service

[Service]
Type=oneshot
User=centralis
WorkingDirectory=/home/centralis/Centralis_11_11
ExecStart=/bin/bash /home/centralis/Centralis_11_11/backup.sh
StandardOutput=append:/home/centralis/backups/systemd.log
StandardError=append:/home/centralis/backups/systemd-error.log
```

### 2. Créer le timer systemd

Créez le fichier `/etc/systemd/system/centralis-backup.timer` :

```ini
[Unit]
Description=Centralis Docker Backup Timer
Requires=centralis-backup.service

[Timer]
# Exécution quotidienne à 2h00
OnCalendar=daily
OnCalendar=02:00
Persistent=true

[Install]
WantedBy=timers.target
```

### 3. Activer le timer

```bash
# Recharger systemd
sudo systemctl daemon-reload

# Activer et démarrer le timer
sudo systemctl enable centralis-backup.timer
sudo systemctl start centralis-backup.timer

# Vérifier le statut
sudo systemctl status centralis-backup.timer

# Lister les prochaines exécutions
sudo systemctl list-timers centralis-backup.timer
```

## Surveillance et notifications

### Script de surveillance avec envoi d'email (optionnel)

Si vous souhaitez recevoir des notifications par email, installez `mailutils` :

```bash
sudo apt-get install mailutils
```

Puis modifiez le script backup.sh pour ajouter à la fin de la fonction main() :

```bash
# Envoi d'email de notification
if command -v mail &> /dev/null; then
    echo "Backup terminé avec succès en ${DURATION} secondes. Archive: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | \
        mail -s "Backup Centralis - Succès" votre-email@example.com
fi
```

## Backup distant (recommandé pour la sécurité)

### Option 1: Rsync vers un serveur distant

Ajoutez à la fin du script backup.sh :

```bash
# Synchronisation avec un serveur distant
REMOTE_SERVER="backup-server.example.com"
REMOTE_USER="backup-user"
REMOTE_PATH="/backups/centralis"

rsync -avz --progress \
    "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" \
    "${BACKUP_USER}@${REMOTE_SERVER}:${REMOTE_PATH}/"
```

### Option 2: Upload vers un stockage cloud (AWS S3)

```bash
# Installer AWS CLI
# sudo apt-get install awscli

# Configurer AWS credentials
# aws configure

# Upload vers S3
aws s3 cp "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" \
    s3://votre-bucket/centralis-backups/ \
    --storage-class STANDARD_IA
```

## Monitoring des backups

### Script de vérification de la santé des backups

Créez un script `check-backups.sh` :

```bash
#!/bin/bash

BACKUP_DIR="/home/centralis/backups"
MAX_AGE_HOURS=48

# Trouver le backup le plus récent
LATEST_BACKUP=$(ls -t "${BACKUP_DIR}"/centralis_backup_*.tar.gz | head -n1)

if [ -z "${LATEST_BACKUP}" ]; then
    echo "CRITIQUE: Aucun backup trouvé"
    exit 2
fi

# Vérifier l'âge du backup
BACKUP_AGE=$(($(date +%s) - $(stat -c %Y "${LATEST_BACKUP}")))
BACKUP_AGE_HOURS=$((BACKUP_AGE / 3600))

if [ ${BACKUP_AGE_HOURS} -gt ${MAX_AGE_HOURS} ]; then
    echo "ALERTE: Le dernier backup a ${BACKUP_AGE_HOURS} heures"
    exit 1
else
    echo "OK: Backup récent trouvé (${BACKUP_AGE_HOURS} heures)"
    exit 0
fi
```

## Rotation et nettoyage automatique

Le script backup.sh inclut déjà une rotation automatique (7 jours par défaut).

Pour modifier la durée de rétention, éditez la variable dans backup.sh :

```bash
RETENTION_DAYS=30  # Garder 30 jours de backups
```

## Variables d'environnement

Le script utilise les répertoires suivants :

- `BACKUP_DIR` : Répertoire de stockage des backups (par défaut: `/home/centralis/backups`)
- `RETENTION_DAYS` : Nombre de jours de rétention (par défaut: 7)

## Restauration rapide

En cas de problème, utilisez le script de restauration :

```bash
# Lister les backups disponibles et restaurer
cd /home/centralis/Centralis_11_11
sudo bash restore.sh
```

## Sécurité

### Chiffrement des backups (recommandé pour la production)

Pour chiffrer les backups, installez GPG :

```bash
sudo apt-get install gnupg
```

Modifiez la fonction create_archive() dans backup.sh :

```bash
# Chiffrer l'archive
gpg --symmetric --cipher-algo AES256 "${BACKUP_NAME}.tar.gz"
rm "${BACKUP_NAME}.tar.gz"  # Supprimer la version non chiffrée
```

### Permissions des fichiers

```bash
# Définir les permissions appropriées
chmod 700 /home/centralis/Centralis_11_11/backup.sh
chmod 700 /home/centralis/Centralis_11_11/restore.sh
chmod 700 /home/centralis/backups
```

## Test du système de backup

```bash
# Test manuel du backup
cd /home/centralis/Centralis_11_11
sudo bash backup.sh

# Vérifier que le backup a été créé
ls -lh /home/centralis/backups/

# Test de restauration (sur un environnement de test!)
sudo bash restore.sh
```
