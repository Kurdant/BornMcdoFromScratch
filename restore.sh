#!/bin/bash

################################################################################
# Script de Restauration pour Docker Compose Centralis
# Ce script restaure une sauvegarde complète des services et de la base de données
#
# Usage:
#   ./restore.sh [--backup-dir /chemin/backups] [--target-dir /chemin/cible] [--backup-file /chemin/backup.tar.gz]
#
# Options:
#   --backup-dir    : Répertoire contenant les backups (défaut: /home/centralis/backups)
#   --target-dir    : Répertoire cible pour la restauration (défaut: répertoire courant)
#   --backup-file   : Fichier backup spécifique à restaurer (ignore backup-dir)
################################################################################

set -euo pipefail

# Valeurs par défaut
DEFAULT_BACKUP_DIR="/home/centralis/backups"
DEFAULT_TARGET_DIR="$(pwd)"
BACKUP_DIR=""
TARGET_DIR=""
BACKUP_FILE_ARG=""
DEV_MODE=false
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Parser les arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --backup-dir)
            BACKUP_DIR="$2"
            shift 2
            ;;
        --target-dir)
            TARGET_DIR="$2"
            shift 2
            ;;
        --backup-file)
            BACKUP_FILE_ARG="$2"
            shift 2
            ;;
        --dev)
            DEV_MODE=true
            shift
            ;;
        -h|--help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --backup-dir PATH    Répertoire contenant les backups"
            echo "  --target-dir PATH    Répertoire cible pour la restauration"
            echo "  --backup-file PATH   Fichier backup spécifique à restaurer"
            echo "  --dev                Mode développement (conserve docker-compose.yml et .env actuels)"
            echo "  -h, --help          Afficher cette aide"
            exit 0
            ;;
        *)
            echo "Option inconnue: $1"
            echo "Utilisez --help pour voir les options disponibles"
            exit 1
            ;;
    esac
done

# Configuration interactive si non spécifié
if [ -z "${BACKUP_DIR}" ] && [ -z "${BACKUP_FILE_ARG}" ]; then
    echo -e "\033[0;34m📁 Répertoire des backups\033[0m"
    read -p "Répertoire des backups [${DEFAULT_BACKUP_DIR}]: " BACKUP_DIR
    BACKUP_DIR="${BACKUP_DIR:-${DEFAULT_BACKUP_DIR}}"
fi

if [ -z "${TARGET_DIR}" ]; then
    echo -e "\033[0;34m🎯 Répertoire cible\033[0m"
    read -p "Répertoire cible de restauration [${DEFAULT_TARGET_DIR}]: " TARGET_DIR
    TARGET_DIR="${TARGET_DIR:-${DEFAULT_TARGET_DIR}}"
fi

# Utiliser le répertoire par défaut si pas d'argument
BACKUP_DIR="${BACKUP_DIR:-${DEFAULT_BACKUP_DIR}}"
TARGET_DIR="${TARGET_DIR:-${DEFAULT_TARGET_DIR}}"

# Convertir en chemins absolus pour éviter les problèmes
BACKUP_DIR=$(cd "${BACKUP_DIR}" 2>/dev/null && pwd || echo "${BACKUP_DIR}")
TARGET_DIR=$(cd "${TARGET_DIR}" 2>/dev/null && pwd || echo "${TARGET_DIR}")

# Si backup-file spécifié, convertir aussi en chemin absolu
if [ -n "${BACKUP_FILE_ARG}" ]; then
    # Si le fichier existe, obtenir son chemin absolu
    if [ -f "${BACKUP_FILE_ARG}" ]; then
        BACKUP_FILE_ARG=$(cd "$(dirname "${BACKUP_FILE_ARG}")" && pwd)/$(basename "${BACKUP_FILE_ARG}")
    fi
fi

# Créer le répertoire de backup dès le début si nécessaire
mkdir -p "${BACKUP_DIR}"
LOG_FILE="${BACKUP_DIR}/restore_$(date +"%Y%m%d_%H%M%S").log"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

log_info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO:${NC} $1" | tee -a "${LOG_FILE}"
}

################################################################################
# Sélection du backup à restaurer
################################################################################
select_backup() {
    # Si un fichier backup spécifique est fourni en argument
    if [ -n "${BACKUP_FILE_ARG}" ]; then
        if [ -f "${BACKUP_FILE_ARG}" ]; then
            log "Backup spécifié: $(basename "${BACKUP_FILE_ARG}")"
            echo "${BACKUP_FILE_ARG}"
            return 0
        else
            log_error "Fichier backup spécifié non trouvé: ${BACKUP_FILE_ARG}"
            exit 1
        fi
    fi
    
    echo "" >&2
    echo -e "${BLUE}Répertoire des backups: ${BACKUP_DIR}${NC}" >&2
    echo -e "${BLUE}Backups disponibles:${NC}" >&2
    echo "" >&2
    
    BACKUPS=($(ls -t "${BACKUP_DIR}"/centralis_backup_*.tar.gz 2>/dev/null))
    
    if [ ${#BACKUPS[@]} -eq 0 ]; then
        log_error "Aucun backup trouvé dans ${BACKUP_DIR}"
        exit 1
    fi
    
    for i in "${!BACKUPS[@]}"; do
        BACKUP_FILE="${BACKUPS[$i]}"
        BACKUP_DATE=$(basename "${BACKUP_FILE}" | sed 's/centralis_backup_\(.*\)\.tar\.gz/\1/')
        BACKUP_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
        echo -e "${BLUE}[$((i+1))]${NC} ${BACKUP_DATE} (${BACKUP_SIZE})" >&2
    done
    
    echo "" >&2
    read -p "Sélectionnez le numéro du backup à restaurer: " SELECTION >&2
    
    if ! [[ "${SELECTION}" =~ ^[0-9]+$ ]] || [ "${SELECTION}" -lt 1 ] || [ "${SELECTION}" -gt ${#BACKUPS[@]} ]; then
        log_error "Sélection invalide"
        exit 1
    fi
    
    SELECTED_BACKUP="${BACKUPS[$((SELECTION-1))]}"
    log "Backup sélectionné: $(basename "${SELECTED_BACKUP}")" >&2
    echo "${SELECTED_BACKUP}"
}

################################################################################
# Vérification du checksum
################################################################################
verify_checksum() {
    local backup_file=$1
    local checksum_file="${backup_file}.sha256"
    
    if [ -f "${checksum_file}" ]; then
        log "Vérification de l'intégrité du backup..."
        if cd "$(dirname "${backup_file}")" && sha256sum -c "$(basename "${checksum_file}")" > /dev/null 2>&1; then
            log "✓ Intégrité vérifiée"
            return 0
        else
            log_error "Échec de la vérification de l'intégrité"
            return 1
        fi
    else
        log_warning "Fichier de checksum non trouvé, vérification ignorée"
        return 0
    fi
}

################################################################################
# Extraction du backup
################################################################################
extract_backup() {
    local backup_file=$1
    local extract_dir="${BACKUP_DIR}/restore_tmp_$(date +%s)"
    
    log "Extraction du backup..." >&2
    mkdir -p "${extract_dir}"
    tar xzf "${backup_file}" -C "${extract_dir}"
    
    # Trouver le répertoire extrait
    RESTORE_PATH=$(find "${extract_dir}" -maxdepth 1 -type d -name "centralis_backup_*" | head -n1)
    
    if [ -z "${RESTORE_PATH}" ]; then
        log_error "Impossible de trouver le répertoire du backup"
        rm -rf "${extract_dir}"
        exit 1
    fi
    
    log "✓ Backup extrait dans ${RESTORE_PATH}" >&2
    echo "${RESTORE_PATH}"
}

################################################################################
# Arrêt des services Docker
################################################################################
stop_services() {
    log "Arrêt des services Docker dans ${TARGET_DIR}..."
    
    cd "${TARGET_DIR}"
    
    if docker-compose ps -q 2>/dev/null | grep -q .; then
        docker-compose down
        log "✓ Services arrêtés"
    else
        log_info "Aucun service en cours d'exécution"
    fi
}

################################################################################
# Restauration de la base de données
################################################################################
restore_database() {
    local restore_path=$1
    
    log "=== Début de la restauration de la base de données ==="
    
    cd "${TARGET_DIR}"
    
    # Vérifier que docker-compose.yml existe
    if [ ! -f "${TARGET_DIR}/docker-compose.yml" ]; then
        log_error "Fichier docker-compose.yml introuvable dans ${TARGET_DIR}"
        log_error "En mode dev, assurez-vous que docker-compose.yml existe dans le répertoire cible"
        exit 1
    fi
    
    # Récupérer l'utilisateur PostgreSQL depuis le conteneur
    DB_USER=$(docker exec database printenv POSTGRES_USER 2>/dev/null || echo "centralis")
    
    # Démarrer uniquement le service database
    log "Démarrage du service database..."
    docker-compose up -d database
    
    # Attendre que PostgreSQL soit prêt
    log "Attente du démarrage de PostgreSQL..."
    sleep 10
    
    until docker exec database pg_isready -U "${DB_USER}" > /dev/null 2>&1; do
        log_info "PostgreSQL n'est pas encore prêt, attente..."
        sleep 2
    done
    
    log "✓ PostgreSQL est prêt (utilisateur: ${DB_USER})"
    
    # Méthode 1: Restauration depuis le backup global complet (pg_dumpall)
    if [ -f "${restore_path}/database/pg_dumpall.sql.gz" ]; then
        log "Restauration complète depuis pg_dumpall (toutes les bases + rôles)..."
        
        # Décompresser et restaurer en une seule commande
        gunzip -c "${restore_path}/database/pg_dumpall.sql.gz" | docker exec -i database psql -U "${DB_USER}" -d postgres
        
        log "✓ Restauration complète effectuée depuis pg_dumpall"
    else
        log_warning "Fichier pg_dumpall.sql.gz non trouvé, tentative de restauration individuelle..."
        
        # Méthode 2: Restauration individuelle (fallback pour anciens backups)
        # Restaurer les rôles globaux si disponibles
        if [ -f "${restore_path}/database/globals.sql" ]; then
            log "Restauration des rôles globaux..."
            docker cp "${restore_path}/database/globals.sql" database:/tmp/
            docker exec database psql -U "${DB_USER}" -d postgres -f /tmp/globals.sql > /dev/null 2>&1 || log_warning "Certains rôles existent déjà"
            docker exec database rm /tmp/globals.sql
        fi
        
        # Restaurer chaque base de données individuellement
        for dump_file in "${restore_path}"/database/*.dump; do
            if [ -f "${dump_file}" ]; then
                DB_NAME=$(basename "${dump_file}" .dump)
                log "Restauration de la base de données: ${DB_NAME}"
                
                # Supprimer la base si elle existe
                docker exec database psql -U "${DB_USER}" -d postgres -c "DROP DATABASE IF EXISTS ${DB_NAME};" 2>/dev/null || true
                
                # Créer la base
                docker exec database psql -U "${DB_USER}" -d postgres -c "CREATE DATABASE ${DB_NAME};"
                
                # Copier le dump dans le conteneur
                docker cp "${dump_file}" database:/tmp/restore.dump
                
                # Restaurer le dump
                docker exec database pg_restore -U "${DB_USER}" -d "${DB_NAME}" /tmp/restore.dump 2>&1 | grep -v "WARNING" || true
                
                # Nettoyer
                docker exec database rm /tmp/restore.dump
                
                log "✓ Base de données ${DB_NAME} restaurée"
            fi
        done
    fi
    
    log "=== Restauration de la base de données terminée ✓ ==="
}

################################################################################
# Restauration des fichiers de configuration
################################################################################
restore_config() {
    local restore_path=$1
    
    log "=== Début de la restauration de la configuration ==="
    
    cd "${TARGET_DIR}"
    
    # Sauvegarder les fichiers actuels
    BACKUP_OLD="/tmp/config_old_$(date +%s)"
    mkdir -p "${BACKUP_OLD}"
    
    if [ -f "docker-compose.yml" ]; then
        cp docker-compose.yml "${BACKUP_OLD}/"
        log_info "docker-compose.yml actuel sauvegardé dans ${BACKUP_OLD}"
    fi
    
    if [ -f ".env" ]; then
        cp .env "${BACKUP_OLD}/"
        log_info ".env actuel sauvegardé dans ${BACKUP_OLD}"
    fi
    
    # Mode développement : conserver les fichiers de configuration actuels
    if [ "${DEV_MODE}" = true ]; then
        log_info "Mode développement activé : utilisation de docker-compose.yml et .env du répertoire du script"
        
        # Vérifier que les fichiers existent dans le répertoire du script
        if [ ! -f "${SCRIPT_DIR}/docker-compose.yml" ]; then
            log_error "docker-compose.yml introuvable dans ${SCRIPT_DIR}"
            log_error "En mode dev, le fichier doit exister dans le répertoire du script de restauration"
            exit 1
        fi
        
        if [ ! -f "${SCRIPT_DIR}/.env" ]; then
            log_error ".env introuvable dans ${SCRIPT_DIR}"
            log_error "En mode dev, le fichier doit exister dans le répertoire du script de restauration"
            exit 1
        fi
        
        # Copier les fichiers depuis le répertoire du script vers le répertoire cible
        log_info "Copie de docker-compose.yml depuis ${SCRIPT_DIR}"
        cp -f "${SCRIPT_DIR}/docker-compose.yml" "${TARGET_DIR}/"
        log "✓ docker-compose.yml copié depuis le répertoire du script"
        
        log_info "Copie de .env depuis ${SCRIPT_DIR}"
        cp -f "${SCRIPT_DIR}/.env" "${TARGET_DIR}/"
        log "✓ .env copié depuis le répertoire du script"
        
        # Restaurer uniquement les autres fichiers de configuration (hors docker-compose.yml et .env)
        if [ -d "${restore_path}/config" ]; then
            shopt -s dotglob nullglob
            for item in "${restore_path}"/config/*; do
                local filename=$(basename "${item}")
                # Ignorer docker-compose.yml et .env en mode dev (déjà copiés depuis SCRIPT_DIR)
                if [ "${filename}" = "docker-compose.yml" ] || [ "${filename}" = ".env" ]; then
                    continue
                fi
                
                if [ -f "${item}" ]; then
                    cp -f "${item}" "${TARGET_DIR}/"
                    log_info "Restauré: ${filename}"
                elif [ -d "${item}" ] && [ "${filename}" != "certs" ]; then
                    cp -r "${item}" "${TARGET_DIR}/"
                fi
            done
            shopt -u dotglob nullglob
            
            # Gérer spécifiquement les certificats
            if [ -d "${restore_path}/config/certs" ]; then
                rm -rf "${TARGET_DIR}/certs"
                cp -r "${restore_path}/config/certs" "${TARGET_DIR}/"
                log "✓ Certificats restaurés"
            fi
            
            log "✓ Configuration restaurée (mode développement)"
        else
            log_warning "Répertoire config non trouvé dans le backup"
        fi
        
        return 0
    fi
    
    # Mode normal : restaurer tous les fichiers de configuration
    if [ -d "${restore_path}/config" ]; then
        # Copier tous les fichiers (y compris les fichiers cachés)
        shopt -s dotglob nullglob
        for item in "${restore_path}"/config/*; do
            if [ -f "${item}" ]; then
                cp -f "${item}" "${TARGET_DIR}/"
                log_info "Restauré: $(basename "${item}")"
            elif [ -d "${item}" ] && [ "$(basename "${item}")" != "certs" ]; then
                cp -r "${item}" "${TARGET_DIR}/"
            fi
        done
        shopt -u dotglob nullglob
        
        # Gérer spécifiquement les certificats
        if [ -d "${restore_path}/config/certs" ]; then
            rm -rf "${TARGET_DIR}/certs"
            cp -r "${restore_path}/config/certs" "${TARGET_DIR}/"
            log "✓ Certificats restaurés"
        fi
        
        log "✓ Configuration restaurée"
    else
        log_warning "Répertoire config non trouvé dans le backup"
    fi
    
    # Proposition de modification du nom de domaine (seulement en mode normal)
    if [ "${DEV_MODE}" = false ] && [ -f "${TARGET_DIR}/.env" ]; then
        # Extraire le domaine actuel du .env restauré
        CURRENT_DOMAIN=$(grep "^DOMAIN_NAME=" "${TARGET_DIR}/.env" | cut -d'=' -f2 | tr -d '"' | tr -d "'")
        
        if [ -n "${CURRENT_DOMAIN}" ]; then
            log_info "Nom de domaine actuel dans le backup: ${CURRENT_DOMAIN}"
            echo ""
            echo -e "${YELLOW}Voulez-vous changer le nom de domaine ?${NC}"
            read -p "Nouveau nom de domaine (Entrée pour conserver '${CURRENT_DOMAIN}'): " NEW_DOMAIN
            
            if [ -n "${NEW_DOMAIN}" ] && [ "${NEW_DOMAIN}" != "${CURRENT_DOMAIN}" ]; then
                log "Changement du nom de domaine: ${CURRENT_DOMAIN} → ${NEW_DOMAIN}"
                
                # Remplacer le DOMAIN_NAME dans le .env
                sed -i "s/^DOMAIN_NAME=.*/DOMAIN_NAME=${NEW_DOMAIN}/" "${TARGET_DIR}/.env"
                
                log "✓ Nom de domaine mis à jour dans .env"
                log_warning "N'oubliez pas de :"
                log_warning "  1. Mettre à jour vos DNS pour pointer vers ce serveur"
                log_warning "  2. Configurer les certificats SSL pour ${NEW_DOMAIN}"
                log_warning "  3. Vérifier la configuration Traefik si utilisé"
            else
                log_info "Nom de domaine conservé: ${CURRENT_DOMAIN}"
            fi
        fi
    elif [ "${DEV_MODE}" = true ]; then
        log_info "Mode développement : conservation du .env actuel, pas de modification du domaine"
    fi
    
    log "=== Restauration de la configuration terminée ✓ ==="
}

################################################################################
# Restauration des applications
################################################################################
restore_applications() {
    local restore_path=$1
    
    log "=== Début de la restauration des applications ==="
    
    if [ -d "${restore_path}/applications" ]; then
        for archive in "${restore_path}"/applications/*.tar.gz; do
            if [ -f "${archive}" ]; then
                log "Restauration de $(basename "${archive}" .tar.gz)..."
                # Extraire vers le répertoire cible
                tar xzf "${archive}" -C "${TARGET_DIR}"
                log "✓ $(basename "${archive}" .tar.gz) restauré"
            fi
        done
    fi
    
    log "=== Restauration des applications terminée ✓ ==="
}

################################################################################
# Redémarrage des services
################################################################################
start_services() {
    log "=== Démarrage des services ==="
    
    cd "${TARGET_DIR}"
    
    # Vérifier que les répertoires et Dockerfiles nécessaires existent
    local missing_dirs=()
    for dir in bdc bdci core subsequent frontend; do
        if [ ! -d "${TARGET_DIR}/${dir}" ]; then
            missing_dirs+=("${dir}")
        elif [ ! -f "${TARGET_DIR}/${dir}/Dockerfile" ]; then
            missing_dirs+=("${dir}/Dockerfile")
        fi
    done
    
    if [ ${#missing_dirs[@]} -gt 0 ]; then
        log_warning "Répertoires ou Dockerfiles manquants: ${missing_dirs[*]}"
        log_warning "Tentative de démarrage des services disponibles uniquement..."
    fi
    
    # Démarrer les services
    if docker-compose up -d 2>&1 | tee -a "${LOG_FILE}"; then
        log "✓ Services démarrés"
    else
        log_error "Erreur lors du démarrage des services"
        log_info "Vérifiez les logs ci-dessus pour plus de détails"
    fi
    
    log "Attente du démarrage des services..."
    sleep 15
    
    # Vérifier l'état des services
    docker-compose ps
    
    log "=== Services démarrés ✓ ==="
}

################################################################################
# Fonction principale
################################################################################
main() {
    log "=========================================="
    log "Début de la restauration Centralis"
    log "=========================================="
    log_info "Répertoire du script: ${SCRIPT_DIR}"
    log_info "Répertoire des backups: ${BACKUP_DIR}"
    log_info "Répertoire cible: ${TARGET_DIR}"
    if [ "${DEV_MODE}" = true ]; then
        log_info "Mode: DÉVELOPPEMENT (utilisation de docker-compose.yml et .env du répertoire du script)"
    else
        log_info "Mode: PRODUCTION (restauration complète)"
    fi
    log ""
    
    # Vérifier que le répertoire cible existe
    if [ ! -d "${TARGET_DIR}" ]; then
        log_warning "Le répertoire cible n'existe pas: ${TARGET_DIR}"
        read -p "Voulez-vous le créer? (oui/non): " CREATE_DIR
        if [ "${CREATE_DIR}" = "oui" ]; then
            mkdir -p "${TARGET_DIR}"
            log "✓ Répertoire créé: ${TARGET_DIR}"
        else
            log_error "Restauration annulée"
            exit 1
        fi
    fi
    
    echo ""
    echo -e "${RED}ATTENTION:${NC} Cette opération va écraser les données dans ${TARGET_DIR}"
    read -p "Êtes-vous sûr de vouloir continuer? (oui/non): " CONFIRM
    
    if [ "${CONFIRM}" != "oui" ]; then
        log "Restauration annulée par l'utilisateur"
        exit 0
    fi
    
    START_TIME=$(date +%s)
    
    # Sélection et vérification du backup
    BACKUP_FILE=$(select_backup)
    verify_checksum "${BACKUP_FILE}"
    
    # Extraction
    RESTORE_PATH=$(extract_backup "${BACKUP_FILE}")
    
    # Arrêt des services
    stop_services
    
    # Vérifier et corriger les permissions si nécessaire
    if [ ! -w "${TARGET_DIR}" ] || ! touch "${TARGET_DIR}/.write_test" 2>/dev/null; then
        log_warning "Permissions insuffisantes sur ${TARGET_DIR}"
        echo ""
        echo -e "${YELLOW}Le répertoire cible nécessite des permissions élevées.${NC}"
        echo "Options:"
        echo "  1) Corriger les permissions (recommandé)"
        echo "  2) Continuer sans correction (peut échouer)"
        echo "  3) Annuler"
        read -p "Votre choix [1]: " PERM_CHOICE
        PERM_CHOICE="${PERM_CHOICE:-1}"
        
        case "${PERM_CHOICE}" in
            1)
                log "Correction des permissions..."
                sudo chown -R $(whoami):$(id -gn) "${TARGET_DIR}"
                log "✓ Permissions corrigées"
                ;;
            2)
                log_warning "Continuation sans correction des permissions"
                ;;
            3)
                log "Restauration annulée"
                rm -rf "$(dirname "${RESTORE_PATH}")"
                exit 0
                ;;
            *)
                log_error "Choix invalide"
                rm -rf "$(dirname "${RESTORE_PATH}")"
                exit 1
                ;;
        esac
    else
        rm -f "${TARGET_DIR}/.write_test"
    fi
    
    # Restauration (ordre important : config d'abord pour avoir docker-compose.yml)
    restore_config "${RESTORE_PATH}"
    restore_applications "${RESTORE_PATH}"
    restore_database "${RESTORE_PATH}"
    
    # Redémarrage
    start_services
    
    # Nettoyage
    rm -rf "$(dirname "${RESTORE_PATH}")"
    
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    log "=========================================="
    log "Restauration terminée avec succès en ${DURATION} secondes"
    log "=========================================="
}

# Exécution
main
