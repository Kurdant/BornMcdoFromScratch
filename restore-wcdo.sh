#!/bin/bash
set -euo pipefail
IFS=$'\n\t'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_ROOT="${BACKUP_ROOT:-/home/hugo/backups}"
TIMESTAMP=$(date +%Y-%m-%d_%H-%M-%S)
LOG_FILE="/tmp/restore-wcdo-${TIMESTAMP}.log"
BACKUP_DATE="${1:-}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log() { echo -e "${GREEN}[$(date +'%H:%M:%S')]${NC} $1" | tee -a "${LOG_FILE}"; }
error() { echo -e "${RED}[ERROR]${NC} $1" | tee -a "${LOG_FILE}"; exit 1; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1" | tee -a "${LOG_FILE}"; }
info() { echo -e "${BLUE}[INFO]${NC} $1" | tee -a "${LOG_FILE}"; }

list_backups() {
  local container="$1"
  local backup_dir="${BACKUP_ROOT}/${container}"
  
  if [ ! -d "${backup_dir}" ]; then
    echo "Aucun backup trouvé pour ${container}"
    return 1
  fi
  
  ls -td "${backup_dir}"/*/ 2>/dev/null | head -10 | while read -r dir; do
    timestamp=$(basename "${dir}")
    files=$(find "${dir}" -type f ! -name "checksums.sha256" | wc -l)
    size=$(du -sh "${dir}" | cut -f1)
    echo "${timestamp} (${files} fichiers, ${size})"
  done
}

select_latest_backup() {
  local container="$1"
  local date_filter="${BACKUP_DATE:-}"
  local backup_dir="${BACKUP_ROOT}/${container}"
  
  [ -d "${backup_dir}" ] || error "Aucun backup trouvé pour ${container}"
  
  local latest
  if [ -n "${date_filter}" ]; then
    latest="${backup_dir}/${date_filter}"
    [ -d "${latest}" ] || error "Backup ${date_filter} non trouvé pour ${container}"
  else
    latest=$(ls -td "${backup_dir}"/*/ 2>/dev/null | head -1)
  fi
  
  [ -n "${latest}" ] || error "Aucun backup trouvé pour ${container}"
  
  echo "${latest%/}"
}

verify_checksum() {
  local backup_dir="$1"
  local checksum_file="${backup_dir}/checksums.sha256"
  
  if [ ! -f "${checksum_file}" ]; then
    warn "Fichier checksums.sha256 non trouvé dans ${backup_dir}"
    return 0
  fi
  
  info "Vérification des checksums..."
  cd "${backup_dir}" || error "Impossible d'accéder à ${backup_dir}"
  sha256sum -c checksums.sha256 --status 2>/dev/null && {
    log "✓ Checksums valides"
    return 0
  } || {
    warn "Checksums invalides - certains fichiers peuvent être corrompus"
    return 0
  }
}

restore_db() {
  local backup_dir="$1"
  local db_file=$(find "${backup_dir}" -name "wcdo_*.sql.gz" | head -1)
  
  [ -f "${db_file}" ] || {
    info "Aucun backup DB trouvé"
    return 0
  }
  
  info "Restauration base de données depuis $(basename "${db_file}")..."
  
  cd "${SCRIPT_DIR}" || error "Impossible d'accéder à ${SCRIPT_DIR}"
  
  if ! docker compose ps database &>/dev/null; then
    log "Démarrage du service database..."
    docker compose up -d database
    sleep 5
  fi
  
  until docker exec wcdo-db pg_isready -U "${DB_USER:-wcdo}" &>/dev/null; do
    info "Attente du démarrage PostgreSQL..."
    sleep 2
  done
  
  log "PostgreSQL prêt, restauration en cours..."
  gunzip -c "${db_file}" | docker exec -i wcdo-db psql -U "${DB_USER:-wcdo}" -d wcdo 2>&1 | tail -5
  
  log "✓ Base de données restaurée"
}

restore_volumes() {
  local container="$1"
  local backup_dir="$2"
  local volume_file=$(find "${backup_dir}" -name "volumes_*.tar.gz" | head -1)
  
  [ -f "${volume_file}" ] || {
    info "Aucun backup de volumes pour ${container}"
    return 0
  }
  
  info "Restauration volumes de ${container}..."
  
  cd "${SCRIPT_DIR}" || error "Impossible d'accéder à ${SCRIPT_DIR}"
  
  if ! docker ps -f name="${container}" --format "{{.Names}}" | grep -q "${container}"; then
    log "Démarrage du service ${container}..."
    docker compose up -d "${container}"
    sleep 3
  fi
  
  log "Extraction des volumes..."
  docker run --rm \
    --volumes-from "${container}" \
    -v "${backup_dir}:/backup:ro" \
    alpine sh -c "cd / && tar xzf /backup/${volume_file##*/}" 2>&1 | tail -3
  
  log "✓ Volumes restaurés pour ${container}"
}

main() {
  echo ""
  echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
  echo -e "${BLUE}║  Restauration WCDO - Script Moderne   ║${NC}"
  echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
  echo ""
  
  if [ -n "${BACKUP_DATE}" ]; then
    log "Restauration depuis: ${BACKUP_DATE}"
  else
    info "Usage: $0 [YYYY-MM-DD_HH-MM-SS] pour restaurer depuis une date précise"
    info "Sinon, utilisera le dernier backup disponible"
  fi
  
  info "Log: ${LOG_FILE}"
  echo ""
  
  info "Conteneurs wcdo détectés:"
  docker ps --filter "name=wcdo" --format "table {{.Names}}\t{{.Image}}\t{{.Status}}" | tail -n +2 || true
  echo ""
  
  echo "Backups disponibles:"
  echo ""
  
  for container in wcdo-db wcdo-php wcdo-nginx wcdo-phpmyadmin; do
    if [ -d "${BACKUP_ROOT}/${container}" ]; then
      echo -e "${BLUE}${container}:${NC}"
      list_backups "${container}" | sed 's/^/  /'
      echo ""
    fi
  done
  
  read -p "Continuer la restauration depuis le dernier backup? (oui/non): " CONFIRM
  [ "${CONFIRM}" = "oui" ] || { info "Annulé"; exit 0; }
  
  echo ""
  read -p "Êtes-vous VRAIMENT sûr? Cette opération restaurera les données (oui/non): " CONFIRM2
  [ "${CONFIRM2}" = "oui" ] || { info "Annulé"; exit 0; }
  
  echo ""
  log "========== RESTAURATION WCDO =========="
  
  cd "${SCRIPT_DIR}" || error "Impossible d'accéder à ${SCRIPT_DIR}"
  
  if docker compose ps -q &>/dev/null; then
    log "Arrêt des services..."
    docker compose down
    sleep 2
  fi
  
  log "Redémarrage des services..."
  docker compose up -d
  sleep 5
  
  for container in wcdo-db wcdo-php wcdo-nginx wcdo-phpmyadmin; do
    backup_dir=$(select_latest_backup "${container}" 2>/dev/null) || continue
    
    echo ""
    info "Traitement: ${container}"
    info "Backup: $(basename "${backup_dir}")"
    
    verify_checksum "${backup_dir}"
    
    if [ "${container}" = "wcdo-db" ]; then
      restore_db "${backup_dir}"
    else
      restore_volumes "${container}" "${backup_dir}"
    fi
  done
  
  echo ""
  log "========== RESTAURATION TERMINÉE =========="
  log "Tous les services sont démarrés"
  log "Log complet: ${LOG_FILE}"
  echo ""
}

main "$@"
