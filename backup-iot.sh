#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

# Charger le .env s'il existe
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[ -f "${SCRIPT_DIR}/.env" ] && set -a && source "${SCRIPT_DIR}/.env" && set +a

# ==========================================
# CONFIG (surchargée par .env si présent)
# ==========================================
BACKUP_DIR="${BACKUP_DIR:-/home/hugo/backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
DATE=$(date +%Y-%m-%d_%H-%M-%S)
TEST_MODE="${1:-}"

# ==========================================
# GESTION D'ERREUR
# ==========================================
on_error() {
  echo "[ERREUR] Ligne $1 - Code de sortie : $2"
  echo "[ERREUR] Nettoyage des fichiers temporaires..."
  find "${BACKUP_DIR}" -name "*.tmp" -delete 2>/dev/null || true
  exit 1
}
trap 'on_error ${LINENO:-0} "$?"' ERR

# ==========================================
# MODULES DE BACKUP
# ==========================================
backup_mysql() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[MariaDB/MySQL] Backup de ${container} → ${workdir}"

  local db_user db_password db_name
  db_user=$(docker exec "${container}" printenv MYSQL_USER)
  db_password=$(docker exec "${container}" printenv MYSQL_PASSWORD)
  db_name=$(docker exec "${container}" printenv MYSQL_DATABASE)

  if [[ "${TEST_MODE}" == "--test-mode" ]]; then
    echo "[TEST] mysqldump simulé pour ${db_name}"
    echo "-- TEST BACKUP ${DATE}" > "${workdir}/${db_name}_${DATE}.sql"
  else
    docker exec "${container}" \
      mysqldump -u"${db_user}" -p"${db_password}" \
      --single-transaction --routines --triggers \
      "${db_name}" > "${workdir}/${db_name}_${DATE}.sql"
  fi

  gzip "${workdir}/${db_name}_${DATE}.sql"
  echo "[MariaDB/MySQL] ✓ ${db_name}_${DATE}.sql.gz créé"
}

backup_postgres() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[PostgreSQL] Backup de ${container} → ${workdir}"

  docker exec "${container}" pg_dumpall -U postgres --globals-only \
    > "${workdir}/pg_globals_${DATE}.sql"
  docker exec "${container}" pg_dump -U postgres -Fc \
    -f "/tmp/pg_dump_${DATE}.dump" postgres
  docker cp "${container}:/tmp/pg_dump_${DATE}.dump" "${workdir}/"
  gzip "${workdir}/pg_globals_${DATE}.sql"
  echo "[PostgreSQL] ✓ Dump créé"
}

backup_mongo() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[MongoDB] Backup de ${container} → ${workdir}"

  docker exec "${container}" \
    mongodump --archive="/tmp/mongo_${DATE}.archive" --gzip
  docker cp "${container}:/tmp/mongo_${DATE}.archive" "${workdir}/"
  echo "[MongoDB] ✓ Archive créée"
}

backup_redis() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[Redis] Backup de ${container} → ${workdir}"

  docker exec "${container}" redis-cli BGSAVE
  sleep 2  # Laisse le temps au BGSAVE de terminer
  docker cp "${container}:/data/dump.rdb" "${workdir}/dump_${DATE}.rdb"
  echo "[Redis] ✓ dump.rdb copié"
}

backup_influxdb() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[InfluxDB] Backup de ${container} → ${workdir}"

  docker exec "${container}" \
    influx backup --portable "/tmp/influx_${DATE}" 2>/dev/null || \
  docker exec "${container}" \
    influxd backup -portable "/tmp/influx_${DATE}"
  docker cp "${container}:/tmp/influx_${DATE}" "${workdir}/"
  echo "[InfluxDB] ✓ Backup créé"
}

backup_docker_volumes() {
  local container="$1"; local workdir="$2"
  mkdir -p "${workdir}"
  echo "[Volumes] Backup volumes de ${container} → ${workdir}"

  docker run --rm \
    --volumes-from "${container}" \
    -v "${workdir}:/backup" \
    alpine tar czf "/backup/volumes_${DATE}.tar.gz" /var/www/html 2>/dev/null || true
  echo "[Volumes] ✓ Backup volumes terminé"
}

# ==========================================
# GÉNÉRATION DES CHECKSUMS SHA256
# ==========================================
generate_checksums() {
  local workdir="$1"
  echo "[Checksum] Calcul des SHA256 dans ${workdir}"
  find "${workdir}" -type f ! -name "checksums.sha256" \
    -exec sha256sum {} \; > "${workdir}/checksums.sha256"
  echo "[Checksum] ✓ checksums.sha256 généré"
}

# ==========================================
# DISPATCHER PRINCIPAL
# ==========================================
echo "======================================"
echo " Backup démarré — ${DATE}"
[[ "${TEST_MODE}" == "--test-mode" ]] && echo " ⚠ MODE TEST ACTIVÉ"
echo "======================================"

for cid in $(docker ps -q --filter "name=wcdo-"); do
  image=$(docker inspect "${cid}" --format '{{.Config.Image}}')
  name=$(docker inspect "${cid}" --format '{{.Name}}' | tr -d '/')
  workdir="${BACKUP_DIR}/${name}/${DATE}"

  echo "→ Détecté : ${name} (${image})"

  case "${image}" in
    postgres*|*/postgres*)
      backup_postgres "${cid}" "${workdir}" ;;
    mariadb*|*/mariadb*|mysql*|*/mysql*)
      backup_mysql "${cid}" "${workdir}" ;;
    mongo*|*/mongo*)
      backup_mongo "${cid}" "${workdir}" ;;
    redis*|*/redis*)
      backup_redis "${cid}" "${workdir}" ;;
    influx*|*/influx*)
      backup_influxdb "${cid}" "${workdir}" ;;
    *)
      echo "[SKIP] ${name} → backup volumes uniquement"
      backup_docker_volumes "${cid}" "${workdir}" ;;
  esac

  # Génère les checksums pour ce container
  generate_checksums "${workdir}"
done

# ==========================================
# ROTATION — SUPPRESSION DES VIEUX BACKUPS
# ==========================================
echo "Rotation : suppression des backups > ${RETENTION_DAYS} jours..."
find "${BACKUP_DIR}" -type f -mtime +${RETENTION_DAYS} -delete
find "${BACKUP_DIR}" -type d -empty -delete
echo "✓ Rotation terminée"

echo "======================================"
echo " Backup terminé avec succès !"
echo "======================================"
