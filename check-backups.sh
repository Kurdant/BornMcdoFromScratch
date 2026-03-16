#!/bin/bash
set -euo pipefail

BACKUP_ROOT="${BACKUP_ROOT:-/home/hugo/backups}"
RETENTION_DAYS=7

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  Vérification des Backups WCDO        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}"
echo ""

for container in wcdo-db wcdo-php wcdo-nginx wcdo-phpmyadmin; do
  backup_dir="${BACKUP_ROOT}/${container}"
  
  if [ ! -d "${backup_dir}" ]; then
    echo -e "${RED}✗${NC} ${container}: Aucun backup trouvé"
    continue
  fi
  
  count=$(find "${backup_dir}" -mindepth 1 -maxdepth 1 -type d | wc -l)
  latest=$(ls -td "${backup_dir}"/*/ 2>/dev/null | head -1)
  latest_name=$(basename "${latest}")
  
  age_seconds=$(($(date +%s) - $(stat -c %Y "${latest}")))
  age_hours=$((age_seconds / 3600))
  
  if [ ${age_hours} -lt 24 ]; then
    age_text="${age_hours}h"
    status_icon="${GREEN}✓${NC}"
  elif [ ${age_hours} -lt 72 ]; then
    age_text="${age_hours}h"
    status_icon="${YELLOW}⚠${NC}"
  else
    age_text="${age_hours}h"
    status_icon="${RED}✗${NC}"
  fi
  
  size=$(du -sh "${backup_dir}" | cut -f1)
  
  echo -e "${status_icon} ${container}"
  echo "  Dernière sauvegarde: ${latest_name} (${age_text} ago)"
  echo "  Nombre de backups: ${count}"
  echo "  Espace utilisé: ${size}"
  echo ""
done

echo -e "${BLUE}Rétention: ${RETENTION_DAYS} jours${NC}"
echo -e "${BLUE}Restauration: ./restore-wcdo.sh${NC}"
