<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;

class WidgetView extends CControllerDashboardWidgetView {
    private const DEFAULT_ROW_COUNT = 2;
    private const DEFAULT_PORTS_PER_ROW = 12;
    private const DEFAULT_SFP_PORTS = 4;
    private const DEFAULT_TRAFFIC_IN_PATTERN = 'ifInOctets[*]';
    private const DEFAULT_TRAFFIC_OUT_PATTERN = 'ifOutOctets[*]';
    private const DEFAULT_SPEED_PATTERN = 'ifHighSpeed[*]';
    private const DEFAULT_PORT_INDEX_START = 1;
    private const TRAFFIC_LOOKBACK_SECONDS = 1800;
    private const TRAFFIC_POINTS = 18;
    private const MAX_ROW_COUNT = 6;
    private const MAX_PORTS_PER_ROW = 24;
    private const MAX_TOTAL_PORTS = 96;

    protected function doAction(): void {
        $layout = $this->getLayout();
        $hostid = $this->extractHostId();
        $host = $this->loadHostMeta($hostid);
        $widget_name = $this->resolveWidgetName();
        $switch_brand = $this->resolveText('switch_brand', (string) ($host['vendor'] ?? ''), 'EDGECORE');
        $switch_model = $this->resolveText('switch_model', (string) ($host['model'] ?? ''), 'S5850-48T4Q');
        $switch_role = $this->resolveText('switch_role', '', 'Campus Aggregation');
        $visual_theme = $this->resolveTheme();
        $panel_scale = $this->clamp($this->extractPositiveInt($this->fields_values['panel_scale'] ?? 92), 84, 100);
        $utilization_overlay_enabled = ((int) ($this->fields_values['utilization_overlay_enabled'] ?? 1)) === 1;
        $traffic_in_pattern = $this->sanitizeItemPattern(
            (string) ($this->fields_values['traffic_in_item_pattern'] ?? self::DEFAULT_TRAFFIC_IN_PATTERN),
            self::DEFAULT_TRAFFIC_IN_PATTERN
        );
        $traffic_out_pattern = $this->sanitizeItemPattern(
            (string) ($this->fields_values['traffic_out_item_pattern'] ?? self::DEFAULT_TRAFFIC_OUT_PATTERN),
            self::DEFAULT_TRAFFIC_OUT_PATTERN
        );
        $speed_pattern = $this->sanitizeItemPattern(
            (string) ($this->fields_values['speed_item_pattern'] ?? self::DEFAULT_SPEED_PATTERN),
            self::DEFAULT_SPEED_PATTERN
        );
        $port_index_start = max(0, $this->extractPositiveInt($this->fields_values['port_index_start'] ?? self::DEFAULT_PORT_INDEX_START));
        if ($port_index_start === 0) {
            $port_index_start = self::DEFAULT_PORT_INDEX_START;
        }

        $ports = $this->loadPortsFromFields($layout['total_ports'], $layout['sfp_ports'], $port_index_start);

        if ($hostid !== '' && !$this->hasHostAccess($hostid)) {
            $this->setResponse(new CControllerResponseData([
                'name' => $widget_name,
                'access_denied' => true,
                'hostid' => $hostid,
                'host' => [],
                'switch_brand' => $switch_brand,
                'switch_model' => $switch_model,
                'switch_role' => $switch_role,
                'visual_theme' => $visual_theme,
                'panel_scale' => $panel_scale,
                'utilization_overlay_enabled' => $utilization_overlay_enabled,
                'layout' => $layout,
                'ports' => [],
                'summary' => $this->buildSummary($layout, [], []),
                'user' => [
                    'debug_mode' => $this->getDebugMode()
                ]
            ]));

            return;
        }

        foreach ($ports as &$port) {
            $port['traffic_in_item_key'] = $this->resolvePortItemKey($traffic_in_pattern, (int) $port['mapped_index']);
            $port['traffic_out_item_key'] = $this->resolvePortItemKey($traffic_out_pattern, (int) $port['mapped_index']);
            $port['speed_item_key'] = $this->resolvePortItemKey($speed_pattern, (int) $port['mapped_index']);
        }
        unset($port);

        $trigger_meta = $this->loadTriggerMeta($ports);
        $traffic_series = $utilization_overlay_enabled ? $this->loadTrafficSeries($hostid, $ports) : [];
        $speed_values = $utilization_overlay_enabled
            ? $this->loadLatestItemValues(
                $hostid,
                array_values(array_unique(array_filter(array_map(
                    static fn(array $port): string => (string) ($port['speed_item_key'] ?? ''),
                    $ports
                ), static fn(string $key): bool => $key !== '')))
            )
            : [];

        foreach ($ports as &$port) {
            $triggerid = $port['triggerid'];
            $meta = $triggerid !== '' ? ($trigger_meta[$triggerid] ?? null) : null;
            $port['has_trigger'] = $meta !== null;
            $port['is_problem'] = $meta !== null ? $meta['is_problem'] : false;
            $port['priority'] = $meta !== null ? $meta['priority'] : 0;
            $port['trigger_name'] = $meta !== null ? $meta['description'] : '';
            $port['active_color'] = !$port['has_trigger']
                ? $port['default_color']
                : ($port['is_problem'] ? $port['problem_color'] : $port['ok_color']);
            $port['url'] = $port['has_trigger']
                ? 'zabbix.php?action=problem.view&filter_set=1&triggerids%5B0%5D='.$triggerid
                : '';

            $port['traffic_in_series'] = $traffic_series[$port['traffic_in_item_key']] ?? [];
            $port['traffic_out_series'] = $traffic_series[$port['traffic_out_item_key']] ?? [];
            $port['traffic_in_bps'] = $port['traffic_in_series'] !== []
                ? (float) $port['traffic_in_series'][count($port['traffic_in_series']) - 1]
                : 0.0;
            $port['traffic_out_bps'] = $port['traffic_out_series'] !== []
                ? (float) $port['traffic_out_series'][count($port['traffic_out_series']) - 1]
                : 0.0;

            $speed_key = (string) ($port['speed_item_key'] ?? '');
            $speed_bps = $this->toSpeedBps((float) ($speed_values[$speed_key] ?? 0.0), $speed_key);
            $port['speed_bps'] = $speed_bps;
            $peak_traffic_bps = max((float) $port['traffic_in_bps'], (float) $port['traffic_out_bps']);
            $port['utilization_percent'] = $speed_bps > 0.0 ? min(100.0, ($peak_traffic_bps / $speed_bps) * 100.0) : null;
            $port['utilization_color'] = $this->getUtilizationColor($port['utilization_percent']);
        }
        unset($port);

        $this->setResponse(new CControllerResponseData([
            'name' => $widget_name,
            'access_denied' => false,
            'hostid' => $hostid,
            'host' => $host,
            'switch_brand' => $switch_brand,
            'switch_model' => $switch_model,
            'switch_role' => $switch_role,
            'visual_theme' => $visual_theme,
            'panel_scale' => $panel_scale,
            'utilization_overlay_enabled' => $utilization_overlay_enabled,
            'layout' => $layout,
            'ports' => $ports,
            'summary' => $this->buildSummary($layout, $ports, $host),
            'user' => [
                'debug_mode' => $this->getDebugMode()
            ]
        ]));
    }

    private function resolveWidgetName(): string {
        $widget_name = trim((string) $this->getInput('name', ''));
        if ($widget_name === '') {
            $widget_name = trim((string) ($this->fields_values['name'] ?? ''));
        }
        if ($widget_name === '' && method_exists($this->widget, 'getName')) {
            $widget_name = trim((string) $this->widget->getName());
        }
        if ($widget_name === '') {
            $widget_name = $this->widget->getDefaultName();
        }

        return $widget_name;
    }

    private function resolveTheme(): string {
        $theme = strtolower(trim((string) ($this->fields_values['visual_theme'] ?? 'graphite')));
        return in_array($theme, ['graphite', 'aurora', 'ember'], true) ? $theme : 'graphite';
    }

    private function resolveText(string $field, string $preferred, string $fallback): string {
        $value = trim((string) ($this->fields_values[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
        if (trim($preferred) !== '') {
            return trim($preferred);
        }
        return $fallback;
    }

    private function extractHostId(): string {
        $value = $this->fields_values['hostids'] ?? [];
        if (is_array($value)) {
            $first = reset($value);
            return $first !== false ? (string) $first : '';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function getLayout(): array {
        $row_count = $this->clamp(
            $this->extractPositiveInt($this->fields_values['row_count'] ?? self::DEFAULT_ROW_COUNT),
            1,
            self::MAX_ROW_COUNT
        );
        $ports_per_row = $this->clamp(
            $this->extractPositiveInt($this->fields_values['ports_per_row'] ?? self::DEFAULT_PORTS_PER_ROW),
            1,
            self::MAX_PORTS_PER_ROW
        );
        $base_ports = $row_count * $ports_per_row;
        $max_sfp = max(0, self::MAX_TOTAL_PORTS - $base_ports);
        $sfp_ports = $this->clamp(
            $this->extractNonNegativeInt($this->fields_values['sfp_ports'] ?? self::DEFAULT_SFP_PORTS),
            0,
            min(12, $max_sfp)
        );

        return [
            'row_count' => $row_count,
            'ports_per_row' => $ports_per_row,
            'sfp_ports' => $sfp_ports,
            'total_ports' => min(self::MAX_TOTAL_PORTS, $base_ports + $sfp_ports),
            'base_ports' => $base_ports
        ];
    }

    private function loadPortsFromFields(int $total_ports, int $sfp_ports, int $port_index_start): array {
        $ports = [];
        $sfp_start_index = $sfp_ports > 0 ? ($total_ports - $sfp_ports + 1) : PHP_INT_MAX;

        for ($i = 1; $i <= $total_ports; $i++) {
            $is_sfp = $i >= $sfp_start_index;
            $default_label = $is_sfp
                ? sprintf('SFP %02d', $i - $sfp_start_index + 1)
                : sprintf('GE %02d', $i);

            $ports[] = [
                'index' => $i,
                'mapped_index' => $port_index_start + $i - 1,
                'name' => $this->resolvePortName($i, $default_label),
                'port_code' => $default_label,
                'is_sfp' => $is_sfp,
                'triggerid' => trim((string) ($this->fields_values['port'.$i.'_triggerid'] ?? '')),
                'default_color' => $this->safeColor((string) ($this->fields_values['port'.$i.'_default_color'] ?? '#64748B'), '#64748B'),
                'ok_color' => $this->safeColor((string) ($this->fields_values['port'.$i.'_ok_color'] ?? '#34D399'), '#34D399'),
                'problem_color' => $this->safeColor((string) ($this->fields_values['port'.$i.'_problem_color'] ?? '#FB7185'), '#FB7185')
            ];
        }

        return $ports;
    }

    private function resolvePortName(int $index, string $fallback): string {
        $value = trim((string) ($this->fields_values['port'.$index.'_name'] ?? ''));
        return $value !== '' ? $value : $fallback;
    }

    private function hasHostAccess(string $hostid): bool {
        if ($hostid === '') {
            return true;
        }

        $count = API::Host()->get([
            'countOutput' => true,
            'hostids' => [$hostid]
        ]);

        return (int) $count > 0;
    }

    private function loadHostMeta(string $hostid): array {
        if ($hostid === '') {
            return [];
        }

        $rows = API::Host()->get([
            'output' => ['hostid', 'name', 'status', 'maintenance_status'],
            'hostids' => [$hostid],
            'selectInterfaces' => ['ip', 'dns'],
            'selectInventory' => ['vendor', 'model', 'serialno_a', 'os'],
            'limit' => 1
        ]);

        if (!$rows) {
            return [];
        }

        $host = $rows[0];
        $interface = isset($host['interfaces'][0]) && is_array($host['interfaces'][0]) ? $host['interfaces'][0] : [];
        $inventory = isset($host['inventory']) && is_array($host['inventory']) ? $host['inventory'] : [];

        return [
            'hostid' => (string) ($host['hostid'] ?? ''),
            'name' => (string) ($host['name'] ?? ''),
            'status' => (int) ($host['status'] ?? 0),
            'maintenance_status' => (int) ($host['maintenance_status'] ?? 0),
            'ip' => trim((string) ($interface['ip'] ?? '')),
            'dns' => trim((string) ($interface['dns'] ?? '')),
            'vendor' => trim((string) ($inventory['vendor'] ?? '')),
            'model' => trim((string) ($inventory['model'] ?? '')),
            'serial' => trim((string) ($inventory['serialno_a'] ?? '')),
            'os' => trim((string) ($inventory['os'] ?? ''))
        ];
    }

    private function loadTriggerMeta(array $ports): array {
        $triggerids = [];
        foreach ($ports as $port) {
            if ($port['triggerid'] !== '') {
                $triggerids[] = $port['triggerid'];
            }
        }
        $triggerids = array_values(array_unique($triggerids));

        if ($triggerids === []) {
            return [];
        }

        $rows = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'value', 'priority'],
            'triggerids' => $triggerids,
            'preservekeys' => true
        ]);

        $result = [];
        foreach ($rows as $triggerid => $row) {
            $result[(string) $triggerid] = [
                'description' => (string) ($row['description'] ?? ''),
                'is_problem' => ((int) ($row['value'] ?? 0)) === 1,
                'priority' => (int) ($row['priority'] ?? 0)
            ];
        }

        return $result;
    }

    private function sanitizeItemPattern(string $value, string $fallback): string {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        return substr($value, 0, 255);
    }

    private function resolvePortItemKey(string $pattern, int $port_index): string {
        if (strpos($pattern, '*') !== false) {
            return str_replace('*', (string) $port_index, $pattern);
        }

        return $pattern;
    }

    private function loadTrafficSeries(string $hostid, array $ports): array {
        if ($hostid === '') {
            return [];
        }

        $keys = [];
        foreach ($ports as $port) {
            if (!empty($port['traffic_in_item_key'])) {
                $keys[] = (string) $port['traffic_in_item_key'];
            }
            if (!empty($port['traffic_out_item_key'])) {
                $keys[] = (string) $port['traffic_out_item_key'];
            }
        }
        $keys = array_values(array_unique(array_filter($keys, static fn(string $key): bool => $key !== '')));
        if ($keys === []) {
            return [];
        }

        $rows = API::Item()->get([
            'output' => ['itemid', 'key_', 'value_type'],
            'hostids' => [$hostid],
            'filter' => ['key_' => $keys]
        ]);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key_'] ?? '');
            $value_type = (int) ($row['value_type'] ?? 3);
            if ($key === '' || !in_array($value_type, [0, 3], true)) {
                continue;
            }

            $history = API::History()->get([
                'output' => ['clock', 'value'],
                'itemids' => [(string) $row['itemid']],
                'history' => $value_type,
                'time_from' => time() - self::TRAFFIC_LOOKBACK_SECONDS,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => self::TRAFFIC_POINTS
            ]);

            if (!is_array($history) || $history === []) {
                $result[$key] = [];
                continue;
            }

            $history = array_reverse($history);
            $series = [];
            $previous = null;
            foreach ($history as $point) {
                $clock = (int) ($point['clock'] ?? 0);
                $value = $this->toFloat($point['value'] ?? 0);

                if ($previous === null) {
                    $previous = ['clock' => $clock, 'value' => $value];
                    continue;
                }

                $delta_time = max(1, $clock - (int) $previous['clock']);
                $delta_value = $value - (float) $previous['value'];
                if ($delta_value < 0) {
                    $delta_value = 0.0;
                }

                if ($this->looksLikeCounterKey($key)) {
                    $series[] = $this->normalizeTrafficToBps($delta_value / $delta_time, $key);
                }
                else {
                    $series[] = $this->normalizeTrafficToBps($value, $key);
                }

                $previous = ['clock' => $clock, 'value' => $value];
            }

            $result[$key] = $series;
        }

        return $result;
    }

    private function loadLatestItemValues(string $hostid, array $keys): array {
        if ($hostid === '' || $keys === []) {
            return [];
        }

        $rows = API::Item()->get([
            'output' => ['key_', 'lastvalue'],
            'hostids' => [$hostid],
            'filter' => ['key_' => $keys]
        ]);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key_'] ?? '');
            if ($key === '') {
                continue;
            }

            $result[$key] = $this->toFloat($row['lastvalue'] ?? 0);
        }

        return $result;
    }

    private function buildSummary(array $layout, array $ports, array $host): array {
        $configured = 0;
        $problems = 0;
        $peak_utilization = 0.0;
        $hot_ports = 0;

        foreach ($ports as $port) {
            if (!empty($port['has_trigger'])) {
                $configured++;
            }
            if (!empty($port['is_problem'])) {
                $problems++;
            }
            if (isset($port['utilization_percent']) && $port['utilization_percent'] !== null) {
                $utilization = (float) $port['utilization_percent'];
                if ($utilization > $peak_utilization) {
                    $peak_utilization = $utilization;
                }
                if ($utilization >= 70.0) {
                    $hot_ports++;
                }
            }
        }

        $total = (int) ($layout['total_ports'] ?? 0);
        $healthy = max(0, $configured - $problems);

        return [
            'configured_ports' => $configured,
            'problem_ports' => $problems,
            'healthy_ports' => $healthy,
            'total_ports' => $total,
            'peak_utilization' => round($peak_utilization, 1),
            'hot_ports' => $hot_ports,
            'maintenance' => !empty($host) && (int) ($host['maintenance_status'] ?? 0) === 1,
            'monitoring_enabled' => empty($host) || (int) ($host['status'] ?? 0) === 0,
            'last_updated' => date('H:i')
        ];
    }

    private function looksLikeCounterKey(string $key): bool {
        $key = strtolower($key);
        return strpos($key, 'octet') !== false
            || strpos($key, 'byte') !== false
            || strpos($key, 'counter') !== false;
    }

    private function normalizeTrafficToBps(float $value, string $key): float {
        $key = strtolower($key);
        if (strpos($key, 'octet') !== false || strpos($key, 'byte') !== false) {
            return $value * 8.0;
        }

        return $value;
    }

    private function getUtilizationColor(?float $utilization): string {
        if ($utilization === null) {
            return '#64748B';
        }
        if ($utilization >= 85.0) {
            return '#FB7185';
        }
        if ($utilization >= 60.0) {
            return '#F59E0B';
        }
        if ($utilization >= 25.0) {
            return '#38BDF8';
        }

        return '#34D399';
    }

    private function extractPositiveInt($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }
        $value = trim((string) $value);
        return ctype_digit($value) ? (int) $value : 0;
    }

    private function extractNonNegativeInt($value): int {
        return max(0, $this->extractPositiveInt($value));
    }

    private function clamp(int $value, int $min, int $max): int {
        return max($min, min($max, $value));
    }

    private function safeColor(string $value, string $fallback): string {
        $value = trim($value);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1) {
            return strtoupper($value);
        }
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $value) === 1) {
            return '#'.strtoupper($value);
        }
        return $fallback;
    }

    private function toFloat($value): float {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (!is_string($value)) {
            return 0.0;
        }

        $text = trim($value);
        if ($text === '') {
            return 0.0;
        }

        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function toSpeedBps(float $speed_value, string $speed_key): float {
        if ($speed_value <= 0.0) {
            return 0.0;
        }

        if (stripos($speed_key, 'ifhighspeed') !== false) {
            return $speed_value * 1000000.0;
        }

        return $speed_value;
    }
}
