#!/bin/bash

################################################################################
# Script de Backup Automatisé pour Docker Compose Centralis
# Ce script effectue une sauvegarde complète des services et de la base de données
################################################################################

set -euo pipefail  # Arrêt en cas d'erreur

# Configuration
BACKUP_DIR="/home/centralis/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="centralis_backup_${TIMESTAMP}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"
LOG_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.log"

# Nombre de jours de rétention des backups
RETENTION_DAYS=7

# Créer le répertoire de backup dès le début si nécessaire
mkdir -p "${BACKUP_DIR}"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

################################################################################
# Fonction de logging
################################################################################
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "${LOG_FILE}"
}

log_error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "${LOG_FILE}"
}

log_warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1" | tee -a "${LOG_FILE}"
}

################################################################################
# Fonction de nettoyage en cas d'erreur
################################################################################
cleanup_on_error() {
    log_error "Une erreur s'est produite. Nettoyage..."
    if [ -d "${BACKUP_PATH}" ]; then
        rm -rf "${BACKUP_PATH}"
        log "Répertoire de backup temporaire supprimé"
    fi
    exit 1
}

trap cleanup_on_error ERR

################################################################################
# Vérifications préliminaires
################################################################################
check_prerequisites() {
    log "Vérification des prérequis..."
    
    # Vérifier que Docker est en cours d'exécution
    if ! docker info > /dev/null 2>&1; then
        log_error "Docker n'est pas en cours d'exécution"
        exit 1
    fi
    
    # Vérifier que le conteneur database existe
    if ! docker ps -a --format '{{.Names}}' | grep -q "^database$"; then
        log_error "Le conteneur 'database' n'existe pas"
        exit 1
    fi
    
    # Créer le répertoire de backup s'il n'existe pas
    mkdir -p "${BACKUP_DIR}"
    
    log "Prérequis validés ✓"
}

################################################################################
# Backup de la base de données PostgreSQL
################################################################################
backup_database() {
    log "=== Début du backup de la base de données ==="
    
    mkdir -p "${BACKUP_PATH}/database"
    
    # Récupérer les variables d'environnement
    DB_USER=$(docker exec database printenv POSTGRES_USER)
    DB_PASSWORD=$(docker exec database printenv POSTGRES_PASSWORD)
    
    # Backup global complet (toutes les bases + rôles + configurations)
    log "Backup global de toutes les bases de données PostgreSQL..."
    docker exec database pg_dumpall -U "${DB_USER}" | gzip > "${BACKUP_PATH}/database/pg_dumpall.sql.gz"
    
    if [ -s "${BACKUP_PATH}/database/pg_dumpall.sql.gz" ]; then
        log "✓ Backup global réussi ($(du -h "${BACKUP_PATH}/database/pg_dumpall.sql.gz" | cut -f1))"
    else
        log_error "Le fichier dump global est vide"
        exit 1
    fi
    
    # Liste des bases de données individuelles pour backup séparé
    log "Backup individuel de chaque base de données..."
    DATABASES=$(docker exec database psql -U "${DB_USER}" -d postgres -t -c "SELECT datname FROM pg_database WHERE datistemplate = false AND datname != 'postgres';")
    
    for DB in ${DATABASES}; do
        DB_CLEAN=$(echo "${DB}" | xargs)  # Trim whitespace
        if [ -n "${DB_CLEAN}" ]; then
            log "Backup de la base de données: ${DB_CLEAN}"
            
            # Dump de la base de données avec compression (format custom pour restauration flexible)
            docker exec database pg_dump -U "${DB_USER}" -Fc "${DB_CLEAN}" > "${BACKUP_PATH}/database/${DB_CLEAN}.dump"
            
            # Vérifier l'intégrité du dump
            if [ -s "${BACKUP_PATH}/database/${DB_CLEAN}.dump" ]; then
                log "✓ Backup de ${DB_CLEAN} réussi ($(du -h "${BACKUP_PATH}/database/${DB_CLEAN}.dump" | cut -f1))"
            else
                log_warning "Le fichier dump pour ${DB_CLEAN} est vide ou a échoué"
            fi
        fi
    done
    
    log "=== Backup de la base de données terminé ✓ ==="
}

################################################################################
# Backup des volumes Docker
################################################################################
backup_volumes() {
    log "=== Début du backup des volumes Docker ==="
    
    mkdir -p "${BACKUP_PATH}/volumes"
    
    # Backup du volume de données PostgreSQL
    log "Backup du volume de données PostgreSQL..."
    docker run --rm \
        -v "$(pwd)/db:/source:ro" \
        -v "${BACKUP_PATH}/volumes:/backup" \
        alpine tar czf /backup/postgres_data.tar.gz -C /source .
    
    log "✓ Volume PostgreSQL sauvegardé ($(du -h "${BACKUP_PATH}/volumes/postgres_data.tar.gz" | cut -f1))"
    
    log "=== Backup des volumes terminé ✓ ==="
}

################################################################################
# Backup des fichiers de configuration
################################################################################
backup_config() {
    log "=== Début du backup des fichiers de configuration ==="
    
    mkdir -p "${BACKUP_PATH}/config"
    
    # Fichiers à sauvegarder
    declare -a CONFIG_FILES=(
        "docker-compose.yml"
        ".env"
        "uploads.ini"
    )
    
    for file in "${CONFIG_FILES[@]}"; do
        if [ -f "${file}" ]; then
            cp "${file}" "${BACKUP_PATH}/config/"
            log "✓ ${file} sauvegardé"
        else
            log_warning "${file} non trouvé, ignoré"
        fi
    done
    
    # Backup des certificats
    if [ -d "certs" ]; then
        log "Backup des certificats..."
        cp -r certs "${BACKUP_PATH}/config/"
        log "✓ Certificats sauvegardés"
    fi
    
    log "=== Backup de la configuration terminé ✓ ==="
}

################################################################################
# Backup des applications
################################################################################
backup_applications() {
    log "=== Début du backup des applications ==="
    
    mkdir -p "${BACKUP_PATH}/applications"
    
    # Backup complet de chaque service (incluant Dockerfile, entrypoint.sh, etc.)
    declare -a SERVICES=(
        "bdc"
        "bdci"
        "core"
        "subsequent"
        "frontend"
    )
    
    for service in "${SERVICES[@]}"; do
        if [ -d "${service}" ]; then
            log "Backup complet du service ${service}..."
            tar czf "${BACKUP_PATH}/applications/${service}.tar.gz" "${service}"
            log "✓ ${service} sauvegardé ($(du -h "${BACKUP_PATH}/applications/${service}.tar.gz" | cut -f1))"
        else
            log_warning "${service} non trouvé, ignoré"
        fi
    done
    
    log "=== Backup des applications terminé ✓ ==="
}

################################################################################
# Sauvegarde des logs Docker
################################################################################
backup_docker_logs() {
    log "=== Début du backup des logs Docker ==="
    
    mkdir -p "${BACKUP_PATH}/logs"
    
    # Liste des conteneurs
    CONTAINERS=$(docker ps -a --format '{{.Names}}')
    
    for container in ${CONTAINERS}; do
        log "Sauvegarde des logs de ${container}..."
        docker logs "${container}" > "${BACKUP_PATH}/logs/${container}.log" 2>&1 || log_warning "Impossible de récupérer les logs de ${container}"
    done
    
    log "=== Backup des logs terminé ✓ ==="
}

################################################################################
# Création d'une archive compressée
################################################################################
create_archive() {
    log "=== Création de l'archive finale ==="
    
    cd "${BACKUP_DIR}"
    tar czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}"
    
    # Vérifier la taille de l'archive
    ARCHIVE_SIZE=$(du -h "${BACKUP_NAME}.tar.gz" | cut -f1)
    log "✓ Archive créée: ${BACKUP_NAME}.tar.gz (${ARCHIVE_SIZE})"
    
    # Supprimer le répertoire temporaire
    rm -rf "${BACKUP_NAME}"
    
    # Calculer le checksum
    CHECKSUM=$(sha256sum "${BACKUP_NAME}.tar.gz" | cut -d' ' -f1)
    echo "${CHECKSUM}  ${BACKUP_NAME}.tar.gz" > "${BACKUP_NAME}.tar.gz.sha256"
    log "✓ Checksum SHA256: ${CHECKSUM}"
    
    log "=== Archive créée avec succès ✓ ==="
}

################################################################################
# Nettoyage des anciens backups
################################################################################
cleanup_old_backups() {
    log "=== Nettoyage des anciens backups (> ${RETENTION_DAYS} jours) ==="
    
    find "${BACKUP_DIR}" -name "centralis_backup_*.tar.gz" -type f -mtime +${RETENTION_DAYS} -delete
    find "${BACKUP_DIR}" -name "centralis_backup_*.tar.gz.sha256" -type f -mtime +${RETENTION_DAYS} -delete
    find "${BACKUP_DIR}" -name "backup_*.log" -type f -mtime +${RETENTION_DAYS} -delete
    
    log "✓ Anciens backups supprimés"
}

################################################################################
# Fonction principale
################################################################################
main() {
    log "=========================================="
    log "Début du backup Centralis"
    log "=========================================="
    
    START_TIME=$(date +%s)
    
    # Exécution des étapes de backup
    check_prerequisites
    backup_database
    backup_volumes
    backup_config
    backup_applications
    backup_docker_logs
    create_archive
    cleanup_old_backups
    
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    log "=========================================="
    log "Backup terminé avec succès en ${DURATION} secondes"
    log "Archive: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
    log "=========================================="
}

# Exécution
main
