# Switch Panel Widget

A Zabbix 7.x dashboard widget for rendering switch ports with interface status, traffic, and trigger state.

- Chinese guide: [`README.zh-CN.md`](README.zh-CN.md)
- Reference project: [OpensourceICTSolutions/zabbix-widget-switch](https://github.com/OpensourceICTSolutions/zabbix-widget-switch)

## Screenshots

### Dashboard - Aurora theme

![Dashboard - Aurora theme](screenshots/dashboard-aurora-theme.png)

### Dashboard - Ember theme

![Dashboard - Ember theme](screenshots/dashboard-ember-theme.png)

### Widget edit form

![Widget edit form](screenshots/widget-edit-form.png)

## Features

- Auto-discover switch ports from host items and triggers
- Auto-layout ports by row count
- Show `Up`, `Down`, and `Alert` port statistics
- Support interface traffic, speed, and status item patterns
- Support interface value map display for status text
- Support host-scoped Brand, Model, and Role item selection
- Support Chinese and English card text selection

## Install

1. Copy this directory to your Zabbix modules path, for example `/usr/share/zabbix/modules/switchpanel`.
2. Enable the module in `Administration -> General -> Modules`.
3. Add the `Switch Panel` widget to a dashboard.
4. Select a host and configure:
   - `Rows`
   - `Traffic in item pattern`
   - `Traffic out item pattern`
   - `Speed item pattern`
   - `Status item pattern`

## Deploy test script

`scripts/deploy_test.sh` supports:

- `smoke`: validate API login and basic read access
- `package`: build a module tarball in `/tmp`
- `deploy`: sync the module to a remote Zabbix server over SSH

Example:

```bash
DEPLOY_SSH_HOST='172.16.60.41' DEPLOY_SSH_USER='root' DEPLOY_SSH_PASSWORD='your-root-password' DEPLOY_SSH_STRICT_HOSTKEY='no' DEPLOY_SSH_KNOWN_HOSTS_FILE='/dev/null' DEPLOY_TARGET_DIR='/usr/share/zabbix/modules/switchpanel' DEPLOY_TARGET_OWNER='zabbix' DEPLOY_TARGET_GROUP='zabbix' ./scripts/deploy_test.sh deploy
```

## Notes

- Author: `canghai809`
- Version: `0.0.1`
