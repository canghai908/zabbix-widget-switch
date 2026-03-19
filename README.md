# Switch Panel Widget

A Zabbix 7.x dashboard widget for rendering a modern switch front panel.

## Features

- Switch-style panel inspired by `OpensourceICTSolutions/zabbix-widget-switch`
- More modern visual treatment with theme presets
- Per-port trigger mapping with live OK/problem color states
- Optional traffic/speed telemetry overlay with per-port utilization heat strips
- Separate SFP uplink area and responsive layout
- Host-aware trigger selector in the widget edit form

## Install

1. Copy this directory into your Zabbix modules path, for example:
   `/usr/share/zabbix/modules/switchpanel`
2. Enable the module in `Administration -> General -> Modules`.
3. Add the `Switch Panel` widget to a dashboard.
4. Select a host, set the layout, then map each visible port to a trigger.
5. Optional: configure `Traffic in item pattern`, `Traffic out item pattern`, and `Speed item pattern`
   to light up the telemetry overlay.

## Deploy test script

- `scripts/deploy_test.sh` provides three quick actions:
  - `smoke`: validate API login and basic read access
  - `package`: build a module tarball in `/tmp`
  - `deploy`: sync the module to a remote Zabbix server over SSH
- The deploy step now supports password-based SSH via `sshpass`, which is convenient for `root` login on an internal lab server.
- If the remote host does not have `rsync`, the script automatically falls back to a `tar` stream upload.
- After each deploy, the script normalizes ownership and permissions so Zabbix can scan the module directory.
- Copy `scripts/deploy_test.env.example` to your own local env file or export variables directly:

```bash
cp scripts/deploy_test.env.example /tmp/switchpanel.env
$EDITOR /tmp/switchpanel.env
set -a
source /tmp/switchpanel.env
set +a
./scripts/deploy_test.sh smoke
```

- If you already have SSH access to the Zabbix host, you can deploy and verify in one go:

```bash
ZABBIX_PASSWORD='your-password' \
DEPLOY_SSH_HOST='172.16.60.41' \
DEPLOY_SSH_USER='root' \
DEPLOY_SSH_PASSWORD='your-root-password' \
DEPLOY_SSH_STRICT_HOSTKEY='no' \
DEPLOY_SSH_KNOWN_HOSTS_FILE='/dev/null' \
./scripts/deploy_test.sh all
```

- The script does not hardcode the password; keep it in an untracked env file or shell session.
- If the lab server has changed its SSH host key before, use `DEPLOY_SSH_STRICT_HOSTKEY=no` with `DEPLOY_SSH_KNOWN_HOSTS_FILE=/dev/null` to bypass local `known_hosts` conflicts.

## Notes

- The widget currently focuses on trigger-driven port state and a high-end front-panel UI.
- Port fieldsets beyond `rows * ports per row + sfp ports` are hidden automatically in the edit form.
- If host inventory includes vendor/model, those values can be used as sensible defaults.
