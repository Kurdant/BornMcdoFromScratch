# Restauration WCDO - Guide d'utilisation

## Structure des backups

Les backups sont créés automatiquement par `backup-iot.sh` et organisés par conteneur:

```
/home/hugo/backups/
├── wcdo-db/              # Base de données MariaDB
│   └── 2026-03-02_14-21-14/
│       ├── wcdo_*.sql.gz
│       └── checksums.sha256
├── wcdo-php/             # Application PHP
│   └── 2026-03-02_14-21-14/
│       ├── volumes_*.tar.gz
│       └── checksums.sha256
├── wcdo-nginx/           # Configuration Nginx
└── wcdo-phpmyadmin/      # PhpMyAdmin
```

## Restauration basique

### Derniers backups disponibles (recommandé)

```bash
cd /home/hugo/projects/BornMcdoFromScratch2
./restore-wcdo.sh
```

Le script affichera:
1. Tous les conteneurs détectés
2. Les backups disponibles pour chaque conteneur
3. Vous demande une confirmation

### Restaurer depuis une date spécifique

```bash
# Format: YYYY-MM-DD_HH-MM-SS
./restore-wcdo.sh 2026-03-02_14-21-14
```

## Que fait le script

1. **Vérifie les checksums** des fichiers backup
2. **Arrête les services** (docker compose down)
3. **Redémarre les services** (docker compose up -d)
4. **Restaure la base de données** depuis le `.sql.gz`
5. **Restaure les volumes** des conteneurs (fichiers applicatifs)

## Cas d'urgence - Restauration manuelle rapide

### Base de données uniquement

```bash
cd /home/hugo/projects/BornMcdoFromScratch2
docker compose up -d wcdo-db

# Trouver le backup le plus récent
BACKUP=$(ls -td /home/hugo/backups/wcdo-db/*/ | head -1)
SQL_FILE=$(ls ${BACKUP}/*.sql.gz)

# Restaurer
gunzip -c ${SQL_FILE} | docker exec -i wcdo-db \
  mysql -u wcdo -p${MYSQL_PASSWORD} wcdo
```

### Volumes uniquement

```bash
BACKUP=$(ls -td /home/hugo/backups/wcdo-php/*/ | head -1)
TAR_FILE=$(ls ${BACKUP}/*.tar.gz)

docker exec wcdo-php sh -c "cd / && tar xzf -" < ${TAR_FILE}
```

## Vérification post-restauration

```bash
# Vérifier les services
docker compose ps

# Vérifier les logs
docker compose logs wcdo-db | tail -20
docker compose logs wcdo-php | tail -20

# Accéder à l'application
curl http://localhost:8080  # ou votre port
```

## Logs

Les logs de restauration sont sauvegardés dans `/tmp/restore-wcdo-*.log`

```bash
# Dernier log
tail -f /tmp/restore-wcdo-*.log
```

## Limitations

- La restauration restaure **TOUS** les conteneurs wcdo* en même temps
- Les backups ne sont conservés que **7 jours** (rotation automatique)
- Le script restaure depuis le **dossier racine** - attention à la structure des volumes

## Dépannage

### Erreur: "Impossible d'accéder à wcdo-db"

```bash
# Redémarrer manuellement
docker compose restart wcdo-db
```

### Checksums invalides

Le script continue même si les checksums échouent - c'est juste un avertissement.

### Restauration lente

Cela peut prendre plusieurs minutes si les backups sont volumineux. Patientez ou consultez les logs:

```bash
tail -f /tmp/restore-wcdo-*.log
```
