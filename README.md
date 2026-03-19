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

## Notes

- The widget currently focuses on trigger-driven port state and a high-end front-panel UI.
- Port fieldsets beyond `rows * ports per row + sfp ports` are hidden automatically in the edit form.
- If host inventory includes vendor/model, those values can be used as sensible defaults.
