#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

MODULE_ID="switchpanel"
MODULE_DIR_DEFAULT="/usr/share/zabbix/modules/${MODULE_ID}"
MODULE_SOURCES=(
    "Widget.php"
    "manifest.json"
    "actions"
    "assets"
    "includes"
    "views"
)

ZABBIX_URL="${ZABBIX_URL:-https://172.16.60.41}"
ZABBIX_USERNAME="${ZABBIX_USERNAME:-Admin}"
ZABBIX_PASSWORD="${ZABBIX_PASSWORD:-}"
ZABBIX_PASSWORD_FILE="${ZABBIX_PASSWORD_FILE:-}"
ZABBIX_INSECURE="${ZABBIX_INSECURE:-1}"
DEPLOY_SSH_HOST="${DEPLOY_SSH_HOST:-}"
DEPLOY_SSH_USER="${DEPLOY_SSH_USER:-root}"
DEPLOY_SSH_PASSWORD="${DEPLOY_SSH_PASSWORD:-}"
DEPLOY_SSH_PASSWORD_FILE="${DEPLOY_SSH_PASSWORD_FILE:-}"
DEPLOY_TARGET_DIR="${DEPLOY_TARGET_DIR:-${MODULE_DIR_DEFAULT}}"
DEPLOY_USE_SUDO="${DEPLOY_USE_SUDO:-0}"
DEPLOY_SSH_STRICT_HOSTKEY="${DEPLOY_SSH_STRICT_HOSTKEY:-accept-new}"
DEPLOY_SSH_KNOWN_HOSTS_FILE="${DEPLOY_SSH_KNOWN_HOSTS_FILE:-}"
DEPLOY_TARGET_OWNER="${DEPLOY_TARGET_OWNER:-zabbix}"
DEPLOY_TARGET_GROUP="${DEPLOY_TARGET_GROUP:-zabbix}"

log() {
    printf '[deploy-test] %s\n' "$*"
}

die() {
    printf '[deploy-test] error: %s\n' "$*" >&2
    exit 1
}

usage() {
    cat <<'EOF'
Usage:
  ./scripts/deploy_test.sh smoke
  ./scripts/deploy_test.sh package
  ./scripts/deploy_test.sh deploy
  ./scripts/deploy_test.sh all

Commands:
  smoke    Check Zabbix API login and basic read access.
  package  Build a module tarball under /tmp.
  deploy   Copy the widget module to the remote Zabbix modules path over SSH.
  all      Run package, deploy, then smoke.

Environment:
  ZABBIX_URL            Zabbix base URL or api_jsonrpc.php URL.
  ZABBIX_USERNAME       API username. Default: Admin
  ZABBIX_PASSWORD       API password.
  ZABBIX_PASSWORD_FILE  Read the password from a file instead of env.
  ZABBIX_INSECURE       Set to 1 to allow self-signed HTTPS. Default: 1
  DEPLOY_SSH_HOST       SSH host for deploy.
  DEPLOY_SSH_USER       SSH user for deploy. Default: root
  DEPLOY_SSH_PASSWORD   SSH password for password auth.
  DEPLOY_SSH_PASSWORD_FILE  Read SSH password from a file instead of env.
  DEPLOY_SSH_STRICT_HOSTKEY  SSH StrictHostKeyChecking value. Default: accept-new
  DEPLOY_SSH_KNOWN_HOSTS_FILE  Optional UserKnownHostsFile override, for example /dev/null
  DEPLOY_TARGET_DIR     Remote module path. Default: /usr/share/zabbix/modules/switchpanel
  DEPLOY_TARGET_OWNER   Owner to apply after deploy. Default: zabbix
  DEPLOY_TARGET_GROUP   Group to apply after deploy. Default: zabbix
  DEPLOY_USE_SUDO       Set to 1 to deploy with sudo rsync on the remote host.

Examples:
  ZABBIX_PASSWORD='zabbix' ./scripts/deploy_test.sh smoke
  ZABBIX_PASSWORD='zabbix' DEPLOY_SSH_HOST='172.16.60.41' DEPLOY_SSH_PASSWORD='***' ./scripts/deploy_test.sh all
EOF
}

require_cmd() {
    local cmd
    for cmd in "$@"; do
        command -v "$cmd" >/dev/null 2>&1 || die "missing required command: ${cmd}"
    done
}

read_password() {
    if [[ -n "${ZABBIX_PASSWORD}" ]]; then
        printf '%s' "${ZABBIX_PASSWORD}"
        return
    fi

    if [[ -n "${ZABBIX_PASSWORD_FILE}" ]]; then
        [[ -f "${ZABBIX_PASSWORD_FILE}" ]] || die "password file not found: ${ZABBIX_PASSWORD_FILE}"
        tr -d '\r\n' <"${ZABBIX_PASSWORD_FILE}"
        return
    fi

    die "set ZABBIX_PASSWORD or ZABBIX_PASSWORD_FILE before running this script"
}

read_ssh_password() {
    if [[ -n "${DEPLOY_SSH_PASSWORD}" ]]; then
        printf '%s' "${DEPLOY_SSH_PASSWORD}"
        return
    fi

    if [[ -n "${DEPLOY_SSH_PASSWORD_FILE}" ]]; then
        [[ -f "${DEPLOY_SSH_PASSWORD_FILE}" ]] || die "ssh password file not found: ${DEPLOY_SSH_PASSWORD_FILE}"
        tr -d '\r\n' <"${DEPLOY_SSH_PASSWORD_FILE}"
        return
    fi

    printf ''
}

api_url() {
    local url="${ZABBIX_URL%/}"
    if [[ "${url}" == */api_jsonrpc.php ]]; then
        printf '%s' "${url}"
    else
        printf '%s/api_jsonrpc.php' "${url}"
    fi
}

curl_args() {
    if [[ "${ZABBIX_INSECURE}" == "1" ]]; then
        printf '%s\n' "-k"
    fi
}

api_request() {
    local payload="$1"
    local response
    local -a args

    mapfile -t args < <(curl_args)
    if ! response="$(
        curl -fsS "${args[@]}" \
            -H 'Content-Type: application/json-rpc' \
            --data "${payload}" \
            "$(api_url)"
    )"; then
        printf '[deploy-test] error: request failed: %s\n' "$(api_url)" >&2
        return 1
    fi

    printf '%s' "${response}"
}

json_rpc_payload() {
    local method="$1"
    local params_json="$2"
    local auth="${3:-}"

    METHOD="${method}" PARAMS_JSON="${params_json}" AUTH_TOKEN="${auth}" python3 - <<'PY'
import json
import os

payload = {
    "jsonrpc": "2.0",
    "method": os.environ["METHOD"],
    "params": json.loads(os.environ["PARAMS_JSON"]),
    "id": 1,
}

auth = os.environ.get("AUTH_TOKEN", "")
if auth:
    payload["auth"] = auth

print(json.dumps(payload))
PY
}

extract_result() {
    python3 -c '
import json
import sys

data = json.load(sys.stdin)
if "error" in data:
    error = data["error"]
    message = error.get("message", "unknown error")
    details = error.get("data", "")
    if details:
        print(f"{message}: {details}", file=sys.stderr)
    else:
        print(message, file=sys.stderr)
    sys.exit(1)

result = data.get("result")
if isinstance(result, (dict, list)):
    print(json.dumps(result))
elif result is None:
    print("")
else:
    print(result)
'
}

login_payload() {
    local password="$1"
    local user_key="$2"

    PASSWORD_VALUE="${password}" USER_KEY="${user_key}" USER_VALUE="${ZABBIX_USERNAME}" python3 - <<'PY'
import json
import os

params = {
    os.environ["USER_KEY"]: os.environ["USER_VALUE"],
    "password": os.environ["PASSWORD_VALUE"],
}

print(json.dumps(params))
PY
}

login() {
    local password="$1"
    local response
    local token

    response="$(api_request "$(json_rpc_payload "user.login" "$(login_payload "${password}" "username")")")" || return 1
    if token="$(printf '%s' "${response}" | extract_result 2>/dev/null)"; then
        printf '%s' "${token}"
        return
    fi

    response="$(api_request "$(json_rpc_payload "user.login" "$(login_payload "${password}" "user")")")" || return 1
    printf '%s' "${response}" | extract_result
}

api_call() {
    local method="$1"
    local params_json="$2"
    local auth="$3"

    api_request "$(json_rpc_payload "${method}" "${params_json}" "${auth}")"
}

module_version() {
    python3 - <<'PY' "${REPO_ROOT}/manifest.json"
import json
import sys

with open(sys.argv[1], "r", encoding="utf-8") as fh:
    data = json.load(fh)

print(data.get("version", "0.0.0"))
PY
}

stage_module() {
    local stage_dir="$1"
    local entry

    mkdir -p "${stage_dir}"
    for entry in "${MODULE_SOURCES[@]}"; do
        [[ -e "${REPO_ROOT}/${entry}" ]] || die "module source missing: ${entry}"
        cp -R "${REPO_ROOT}/${entry}" "${stage_dir}/${entry}"
    done
}

ssh_cmd() {
    local password="$1"
    shift

    if [[ -n "${password}" ]]; then
        SSHPASS="${password}" sshpass -e ssh "$@"
    else
        ssh "$@"
    fi
}

rsync_cmd() {
    local password="$1"
    shift

    if [[ -n "${password}" ]]; then
        SSHPASS="${password}" rsync "$@"
    else
        rsync "$@"
    fi
}

ssh_options() {
    printf '%s\n' "-o" "StrictHostKeyChecking=${DEPLOY_SSH_STRICT_HOSTKEY}"
    if [[ -n "${DEPLOY_SSH_KNOWN_HOSTS_FILE}" ]]; then
        printf '%s\n' "-o" "UserKnownHostsFile=${DEPLOY_SSH_KNOWN_HOSTS_FILE}"
    fi
}

package_module() {
    local version artifact tmp_dir

    version="$(module_version)"
    artifact="/tmp/${MODULE_ID}-${version}.tar.gz"
    tmp_dir="$(mktemp -d)"

    stage_module "${tmp_dir}/${MODULE_ID}"
    tar -C "${tmp_dir}" -czf "${artifact}" "${MODULE_ID}"
    rm -rf "${tmp_dir}"

    log "package created: ${artifact}"
}

deploy_module() {
    local remote stage_dir ssh_password remote_has_rsync
    local remote_prepare_cmd remote_extract_cmd remote_fix_cmd
    local -a rsync_args ssh_opts ssh_rsh

    [[ -n "${DEPLOY_SSH_HOST}" ]] || die "set DEPLOY_SSH_HOST before running deploy"

    require_cmd ssh tar
    ssh_password="$(read_ssh_password)"
    if [[ -n "${ssh_password}" ]]; then
        require_cmd sshpass
    fi

    remote="${DEPLOY_SSH_USER}@${DEPLOY_SSH_HOST}"
    stage_dir="$(mktemp -d)"
    mapfile -t ssh_opts < <(ssh_options)

    stage_module "${stage_dir}"

    if [[ "${DEPLOY_USE_SUDO}" == "1" ]]; then
        remote_prepare_cmd="sudo mkdir -p '${DEPLOY_TARGET_DIR}'"
        rsync_args=(--rsync-path="sudo rsync")
        remote_extract_cmd="sudo tar -xf - -C '${DEPLOY_TARGET_DIR}'"
    else
        remote_prepare_cmd="mkdir -p '${DEPLOY_TARGET_DIR}'"
        rsync_args=()
        remote_extract_cmd="tar -xf - -C '${DEPLOY_TARGET_DIR}'"
    fi

    remote_fix_cmd="$(
        cat <<EOF
chown -R '${DEPLOY_TARGET_OWNER}:${DEPLOY_TARGET_GROUP}' '${DEPLOY_TARGET_DIR}' && \
find '${DEPLOY_TARGET_DIR}' -type d -exec chmod 755 {} + && \
find '${DEPLOY_TARGET_DIR}' -type f -exec chmod 644 {} +
EOF
    )"

    if [[ "${DEPLOY_USE_SUDO}" == "1" ]]; then
        remote_fix_cmd="sudo /bin/sh -lc $(printf '%q' "${remote_fix_cmd}")"
    fi

    ssh_cmd "${ssh_password}" "${ssh_opts[@]}" "${remote}" "${remote_prepare_cmd}"
    remote_has_rsync="$(ssh_cmd "${ssh_password}" "${ssh_opts[@]}" "${remote}" "command -v rsync >/dev/null 2>&1 && printf yes || printf no")"

    if command -v rsync >/dev/null 2>&1 && [[ "${remote_has_rsync}" == "yes" ]]; then
        ssh_rsh=(ssh "${ssh_opts[@]}")
        if [[ -n "${ssh_password}" ]]; then
            ssh_rsh=(sshpass -e "${ssh_rsh[@]}")
        fi

        rsync_cmd "${ssh_password}" -az --delete -e "${ssh_rsh[*]}" "${rsync_args[@]}" "${stage_dir}/" "${remote}:${DEPLOY_TARGET_DIR}/"
        log "deploy mode: rsync"
    else
        tar -C "${stage_dir}" -cf - . | ssh_cmd "${ssh_password}" "${ssh_opts[@]}" "${remote}" "${remote_extract_cmd}"
        log "deploy mode: tar stream fallback"
    fi

    ssh_cmd "${ssh_password}" "${ssh_opts[@]}" "${remote}" "${remote_fix_cmd}"

    rm -rf "${stage_dir}"

    log "module synced to ${remote}:${DEPLOY_TARGET_DIR}"
    log "if this is the first install, enable '${MODULE_ID}' in Administration -> General -> Modules"
}

smoke_test() {
    local password token version host_count

    password="$(read_password)"
    token="$(login "${password}")" || die "login failed"
    version="$(
        api_call "apiinfo.version" "{}" "" | extract_result
    )" || die "failed to read api version"
    host_count="$(
        api_call "host.get" '{"countOutput": true}' "${token}" | extract_result
    )" || die "failed to read visible host count"

    log "api url: $(api_url)"
    log "login ok for ${ZABBIX_USERNAME}"
    log "zabbix version: ${version}"
    log "visible host count: ${host_count}"
}

main() {
    local command="${1:-smoke}"

    require_cmd curl python3 tar

    case "${command}" in
        smoke)
            smoke_test
            ;;
        package)
            package_module
            ;;
        deploy)
            deploy_module
            ;;
        all)
            package_module
            deploy_module
            smoke_test
            ;;
        help|-h|--help)
            usage
            ;;
        *)
            usage
            exit 1
            ;;
    esac
}

main "$@"
