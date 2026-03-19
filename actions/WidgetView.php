<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Actions;

use API;
use CValueMapHelper;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use CWebUser;
use Modules\SwitchPanelWidget\Includes\PortDiscovery;

class WidgetView extends CControllerDashboardWidgetView {
    private const SOURCE_MANUAL = 0;
    private const SOURCE_ITEM = 1;
    private const CARD_LANGUAGE_AUTO = 0;
    private const CARD_LANGUAGE_ZH_CN = 1;
    private const CARD_LANGUAGE_EN_US = 2;
    private const THEME_GRAPHITE = 0;
    private const THEME_AURORA = 1;
    private const THEME_EMBER = 2;
    private const DEFAULT_ROW_COUNT = 2;
    private const DEFAULT_PORTS_PER_ROW = 12;
    private const DEFAULT_TRAFFIC_IN_PATTERN = 'net.if.in[*]';
    private const DEFAULT_TRAFFIC_OUT_PATTERN = 'net.if.out[*]';
    private const DEFAULT_SPEED_PATTERN = 'net.if.speed[*]';
    private const DEFAULT_STATUS_PATTERN = 'net.if.status[*]';
    private const DEFAULT_PORT_INDEX_START = 1;
    private const TRAFFIC_LOOKBACK_SECONDS = 1800;
    private const TRAFFIC_POINTS = 18;
    private const MAX_ROW_COUNT = 6;
    private const MAX_PORTS_PER_ROW = 24;
    private const MAX_TOTAL_PORTS = 96;

    protected function doAction(): void {
        $hostid = $this->extractHostId();
        $host = $this->loadHostMeta($hostid);
        $widget_name = $this->resolveWidgetName();
        $item_texts = $this->loadSelectedItemTexts([
            'switch_brand_itemids' => $this->extractFirstId('switch_brand_itemids'),
            'switch_model_itemids' => $this->extractFirstId('switch_model_itemids'),
            'switch_role_itemids' => $this->extractFirstId('switch_role_itemids')
        ]);
        $switch_brand = $this->resolveTextSource(
            'switch_brand',
            (string) ($item_texts['switch_brand_itemids'] ?? ''),
            (string) ($host['vendor'] ?? ''),
            'EDGECORE'
        );
        $switch_model = $this->resolveTextSource(
            'switch_model',
            (string) ($item_texts['switch_model_itemids'] ?? ''),
            (string) ($host['model'] ?? ''),
            'S5850-48T4Q'
        );
        $switch_role = $this->resolveTextSource(
            'switch_role',
            (string) ($item_texts['switch_role_itemids'] ?? ''),
            '',
            'Campus Aggregation'
        );
        $card_language_mode = (int) ($this->fields_values['card_language'] ?? self::CARD_LANGUAGE_AUTO);
        $visual_theme = $this->resolveTheme();
        $panel_scale = $this->clamp($this->extractPositiveInt($this->fields_values['panel_scale'] ?? 92), 84, 100);
        $utilization_overlay_enabled = true;
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
        $status_pattern = $this->sanitizeItemPattern(
            (string) ($this->fields_values['status_item_pattern'] ?? self::DEFAULT_STATUS_PATTERN),
            self::DEFAULT_STATUS_PATTERN
        );
        $port_index_start = max(0, $this->extractPositiveInt($this->fields_values['port_index_start'] ?? self::DEFAULT_PORT_INDEX_START));
        if ($port_index_start === 0) {
            $port_index_start = self::DEFAULT_PORT_INDEX_START;
        }

        $discovery = PortDiscovery::discover($hostid, [
            'traffic_in' => $traffic_in_pattern,
            'traffic_out' => $traffic_out_pattern,
            'speed' => $speed_pattern,
            'status' => $status_pattern
        ]);
        $layout = $this->getLayout($discovery['ports'] ?? []);
        $ports = $this->loadPortsFromFields($layout['total_ports'], $layout['sfp_ports'], $port_index_start, $discovery['ports'] ?? []);

        if ($hostid !== '' && !$this->hasHostAccess($hostid)) {
            $this->setResponse(new CControllerResponseData([
                'name' => $widget_name,
                'access_denied' => true,
                'hostid' => $hostid,
                'host' => [],
                'switch_brand' => $switch_brand,
                'switch_model' => $switch_model,
                'switch_role' => $switch_role,
                'card_language_mode' => $card_language_mode,
                'user_lang' => CWebUser::getLang(),
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
            $port['traffic_in_item_key'] = $this->resolvePortItemKey(
                $traffic_in_pattern,
                (int) $port['mapped_index'],
                (string) ($port['traffic_in_token'] ?? '')
            );
            $port['traffic_out_item_key'] = $this->resolvePortItemKey(
                $traffic_out_pattern,
                (int) $port['mapped_index'],
                (string) ($port['traffic_out_token'] ?? '')
            );
            $port['speed_item_key'] = $this->resolvePortItemKey(
                $speed_pattern,
                (int) $port['mapped_index'],
                (string) ($port['speed_token'] ?? '')
            );
            $port['status_item_key'] = $this->resolvePortItemKey(
                $status_pattern,
                (int) $port['mapped_index'],
                (string) ($port['status_token'] ?? '')
            );
        }
        unset($port);

        $trigger_meta = $this->loadTriggerMeta($ports);
        $active_problem_meta = $this->loadActivePortProblemMeta($hostid, $ports);
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
        $status_values = $this->loadLatestItemValues(
            $hostid,
            array_values(array_unique(array_filter(array_map(
                static fn(array $port): string => (string) ($port['status_item_key'] ?? ''),
                $ports
            ), static fn(string $key): bool => $key !== '')))
        );

        foreach ($ports as &$port) {
            $triggerid = $port['triggerid'];
            $meta = $triggerid !== '' ? ($trigger_meta[$triggerid] ?? null) : null;
            $problem_meta = $active_problem_meta[(int) ($port['index'] ?? 0)] ?? null;
            $port['has_trigger'] = $meta !== null;
            $port['has_problem_trigger'] = $problem_meta !== null;
            $port['is_problem'] = $problem_meta !== null || ($meta !== null ? $meta['is_problem'] : false);
            $port['priority'] = $problem_meta !== null
                ? (int) ($problem_meta['priority'] ?? 0)
                : ($meta !== null ? $meta['priority'] : 0);
            $port['trigger_name'] = $problem_meta !== null
                ? (string) ($problem_meta['description'] ?? '')
                : ($meta !== null ? $meta['description'] : '');
            $port['url'] = $problem_meta !== null
                ? 'zabbix.php?action=problem.view&filter_set=1&triggerids%5B0%5D='.(string) $problem_meta['triggerid']
                : ($port['has_trigger']
                    ? 'zabbix.php?action=problem.view&filter_set=1&triggerids%5B0%5D='.$triggerid
                    : '');

            $port['traffic_in_series'] = $traffic_series[$port['traffic_in_item_key']] ?? [];
            $port['traffic_out_series'] = $traffic_series[$port['traffic_out_item_key']] ?? [];
            $port['traffic_in_bps'] = $port['traffic_in_series'] !== []
                ? (float) $port['traffic_in_series'][count($port['traffic_in_series']) - 1]
                : 0.0;
            $port['traffic_out_bps'] = $port['traffic_out_series'] !== []
                ? (float) $port['traffic_out_series'][count($port['traffic_out_series']) - 1]
                : 0.0;

            $speed_key = (string) ($port['speed_item_key'] ?? '');
            $speed_meta = $speed_values[$speed_key] ?? ['value' => 0.0, 'units' => ''];
            $speed_bps = $this->toSpeedBps(
                (float) ($speed_meta['value'] ?? 0.0),
                $speed_key,
                (string) ($speed_meta['units'] ?? '')
            );
            $status_key = (string) ($port['status_item_key'] ?? '');
            if ($status_key !== '' && array_key_exists($status_key, $status_values)) {
                $status_meta = $status_values[$status_key];
                $status_state = $this->normalizeInterfaceStatus(
                    $status_meta['raw_value'] ?? null,
                    $status_meta['mapped_value'] ?? ''
                );
                $port['status_value'] = $status_state['value'];
                $port['status_label'] = $status_state['label'];
                $port['is_online'] = $status_state['is_online'];
            }
            else {
                $port['status_value'] = null;
                $port['status_label'] = '';
                $port['is_online'] = null;
            }
            $port['speed_bps'] = $speed_bps;
            $peak_traffic_bps = max((float) $port['traffic_in_bps'], (float) $port['traffic_out_bps']);
            $port['utilization_percent'] = $speed_bps > 0.0 ? min(100.0, ($peak_traffic_bps / $speed_bps) * 100.0) : null;
            $port['utilization_color'] = $this->getUtilizationColor($port['utilization_percent']);
            $port['active_color'] = $this->resolvePortColor($port);
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
            'card_language_mode' => $card_language_mode,
            'user_lang' => CWebUser::getLang(),
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
        $theme = $this->fields_values['visual_theme'] ?? self::THEME_GRAPHITE;

        if (is_numeric($theme)) {
            return match ((int) $theme) {
                self::THEME_AURORA => 'aurora',
                self::THEME_EMBER => 'ember',
                default => 'graphite'
            };
        }

        $theme = strtolower(trim((string) $theme));
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

    private function resolveTextSource(string $field, string $item_value, string $preferred, string $fallback): string {
        $source = (int) ($this->fields_values[$field.'_source'] ?? self::SOURCE_MANUAL);

        if ($source === self::SOURCE_ITEM) {
            $value = trim($item_value);
            if ($value !== '') {
                return $value;
            }
        }

        return $this->resolveText($field, $preferred, $fallback);
    }

    private function extractHostId(): string {
        return $this->extractFirstId('hostids');
    }

    private function extractFirstId(string $field): string {
        $value = $this->fields_values[$field] ?? [];
        if (is_array($value)) {
            $first = reset($value);
            return $first !== false ? trim((string) $first) : '';
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function getLayout(array $discovered_ports = []): array {
        $requested_row_count = $this->clamp(
            $this->extractPositiveInt($this->fields_values['row_count'] ?? self::DEFAULT_ROW_COUNT),
            1,
            self::MAX_ROW_COUNT
        );

        if ($discovered_ports !== []) {
            $visible_ports = array_slice($discovered_ports, 0, self::MAX_TOTAL_PORTS);
            $total_ports = count($visible_ports);
            $sfp_ports = count(array_filter($visible_ports, static fn(array $port): bool => !empty($port['is_sfp'])));
            $base_ports = max(0, $total_ports - $sfp_ports);
            $row_count = $base_ports > 0 ? min($requested_row_count, $base_ports) : 1;
            $ports_per_row = $this->calculatePortsPerRow($base_ports, $row_count);

            return [
                'row_count' => $row_count,
                'ports_per_row' => $ports_per_row,
                'sfp_ports' => $sfp_ports,
                'total_ports' => $total_ports,
                'base_ports' => $base_ports
            ];
        }

        $legacy_ports_per_row = $this->clamp(
            $this->extractPositiveInt($this->fields_values['ports_per_row'] ?? self::DEFAULT_PORTS_PER_ROW),
            1,
            self::MAX_PORTS_PER_ROW
        );
        $sfp_ports = 0;
        $stored_total_ports = min(self::MAX_TOTAL_PORTS, $requested_row_count * $legacy_ports_per_row);
        $base_ports = max(0, $stored_total_ports - $sfp_ports);
        $row_count = $base_ports > 0 ? min($requested_row_count, $base_ports) : 1;
        $ports_per_row = $this->calculatePortsPerRow($base_ports, $row_count);

        return [
            'row_count' => $row_count,
            'ports_per_row' => $ports_per_row,
            'sfp_ports' => $sfp_ports,
            'total_ports' => $stored_total_ports,
            'base_ports' => $base_ports
        ];
    }

    private function calculatePortsPerRow(int $base_ports, int $row_count): int {
        if ($base_ports <= 0) {
            return 1;
        }

        $row_count = max(1, min(self::MAX_ROW_COUNT, $row_count));
        return $this->clamp((int) ceil($base_ports / $row_count), 1, self::MAX_PORTS_PER_ROW);
    }

    private function loadPortsFromFields(int $total_ports, int $sfp_ports, int $port_index_start, array $discovered_ports = []): array {
        if ($discovered_ports !== []) {
            $ports = [];
            $visible_ports = array_slice($discovered_ports, 0, self::MAX_TOTAL_PORTS);
            $copper_index = 0;
            $sfp_index = 0;

            foreach ($visible_ports as $index => $discovered_port) {
                $is_sfp = !empty($discovered_port['is_sfp']);
                if ($is_sfp) {
                    $sfp_index++;
                    $default_label = sprintf('SFP %02d', $sfp_index);
                }
                else {
                    $copper_index++;
                    $default_label = sprintf('GE %02d', $copper_index);
                }

                $field_index = $index + 1;
                $mapped_index = (int) ($discovered_port['mapped_index'] ?? 0);
                if ($mapped_index <= 0) {
                    $mapped_index = $port_index_start + $index;
                }

                $manual_triggerid = trim((string) ($this->fields_values['port'.$field_index.'_triggerid'] ?? ''));
                $ports[] = [
                    'index' => $field_index,
                    'mapped_index' => $mapped_index,
                    'name' => $this->resolvePortName($field_index, (string) ($discovered_port['name'] ?? $default_label)),
                    'port_code' => $default_label,
                    'is_sfp' => $is_sfp,
                    'triggerid' => $manual_triggerid !== ''
                        ? $manual_triggerid
                        : trim((string) ($discovered_port['default_triggerid'] ?? '')),
                    'default_color' => $this->safeColor((string) ($this->fields_values['port'.$field_index.'_default_color'] ?? '#64748B'), '#64748B'),
                    'ok_color' => $this->safeColor((string) ($this->fields_values['port'.$field_index.'_ok_color'] ?? '#34D399'), '#34D399'),
                    'problem_color' => $this->safeColor((string) ($this->fields_values['port'.$field_index.'_problem_color'] ?? '#FB7185'), '#FB7185'),
                    'traffic_in_token' => trim((string) ($discovered_port['traffic_in_token'] ?? '')),
                    'traffic_out_token' => trim((string) ($discovered_port['traffic_out_token'] ?? '')),
                    'speed_token' => trim((string) ($discovered_port['speed_token'] ?? '')),
                    'status_token' => trim((string) ($discovered_port['status_token'] ?? ''))
                ];
            }

            return $ports;
        }

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

    private function loadSelectedItemTexts(array $field_itemids): array {
        $itemids = array_values(array_unique(array_filter($field_itemids, static fn(string $itemid): bool => $itemid !== '')));
        if ($itemids === []) {
            return [];
        }

        $rows = API::Item()->get([
            'output' => ['itemid', 'lastvalue', 'name'],
            'itemids' => $itemids,
            'preservekeys' => true
        ]);

        $values_by_itemid = [];
        foreach ($rows as $itemid => $row) {
            $value = trim((string) ($row['lastvalue'] ?? ''));
            if ($value === '') {
                $value = trim((string) ($row['name'] ?? ''));
            }

            $values_by_itemid[(string) $itemid] = $value;
        }

        $result = [];
        foreach ($field_itemids as $field => $itemid) {
            if ($itemid !== '' && array_key_exists($itemid, $values_by_itemid)) {
                $result[$field] = $values_by_itemid[$itemid];
            }
        }

        return $result;
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

    private function loadActivePortProblemMeta(string $hostid, array $ports): array {
        if ($hostid === '' || $ports === []) {
            return [];
        }

        $rows = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'priority', 'value'],
            'hostids' => [$hostid],
            'filter' => ['status' => 0, 'value' => 1]
        ]);

        if (!is_array($rows) || $rows === []) {
            return [];
        }

        $ports_by_name = [];
        $ports_by_index = [];
        foreach ($ports as $port) {
            $port_index = (int) ($port['index'] ?? 0);
            if ($port_index <= 0) {
                continue;
            }

            $normalized_name = $this->normalizeMatchText((string) ($port['name'] ?? ''));
            if ($normalized_name !== '') {
                $ports_by_name[$normalized_name][] = $port;
            }

            $mapped_index = (int) ($port['mapped_index'] ?? 0);
            if ($mapped_index > 0) {
                $ports_by_index[$mapped_index][] = $port;
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $trigger_port_name = $this->extractInterfaceNameFromTriggerDescription((string) ($row['description'] ?? ''));
            if ($trigger_port_name === '') {
                continue;
            }

            $matched_ports = [];
            $normalized_name = $this->normalizeMatchText($trigger_port_name);
            if ($normalized_name !== '' && array_key_exists($normalized_name, $ports_by_name)) {
                $matched_ports = $ports_by_name[$normalized_name];
            }
            else {
                $trigger_index = $this->extractTrailingIndex($trigger_port_name);
                if ($trigger_index > 0 && array_key_exists($trigger_index, $ports_by_index)) {
                    $matched_ports = $ports_by_index[$trigger_index];
                }
            }

            foreach ($matched_ports as $port) {
                $port_index = (int) ($port['index'] ?? 0);
                $current = $result[$port_index] ?? null;
                $current_priority = $current !== null ? (int) ($current['priority'] ?? 0) : -1;
                $new_priority = (int) ($row['priority'] ?? 0);

                if ($current !== null && $current_priority > $new_priority) {
                    continue;
                }

                $result[$port_index] = [
                    'triggerid' => (string) ($row['triggerid'] ?? ''),
                    'description' => (string) ($row['description'] ?? ''),
                    'priority' => $new_priority
                ];
            }
        }

        return $result;
    }

    private function normalizeMatchText(string $value): string {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }

    private function extractInterfaceNameFromTriggerDescription(string $description): string {
        if (preg_match('/Interface\s+(.+?)\(\):/i', $description, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        if (preg_match('/Port\s+(.+?):/i', $description, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private function extractTrailingIndex(string $value): int {
        if (preg_match('/(\d+)(?!.*\d)/', $value, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private function sanitizeItemPattern(string $value, string $fallback): string {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        return substr($value, 0, 255);
    }

    private function resolvePortItemKey(string $pattern, int $port_index, string $wildcard = ''): string {
        if (strpos($pattern, '*') !== false) {
            return str_replace('*', $wildcard !== '' ? $wildcard : (string) $port_index, $pattern);
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
            'output' => ['itemid', 'key_', 'name', 'units', 'value_type'],
            'hostids' => [$hostid],
            'filter' => ['key_' => $keys]
        ]);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key_'] ?? '');
            $name = (string) ($row['name'] ?? '');
            $units = (string) ($row['units'] ?? '');
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

                if ($this->usesDirectTrafficValues($key, $name, $units)) {
                    $series[] = $this->normalizeTrafficToBps($value, $key, $units);
                }
                elseif ($this->looksLikeCounterKey($key, $name, $units)) {
                    $series[] = $this->normalizeTrafficToBps($delta_value / $delta_time, $key, $units);
                }
                else {
                    $series[] = $this->normalizeTrafficToBps($value, $key, $units);
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
            'output' => ['key_', 'lastvalue', 'units', 'value_type', 'valuemapid'],
            'hostids' => [$hostid],
            'filter' => ['key_' => $keys],
            'selectValueMap' => ['mappings']
        ]);

        $result = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key_'] ?? '');
            if ($key === '') {
                continue;
            }

            $result[$key] = [
                'raw_value' => (string) ($row['lastvalue'] ?? ''),
                'value' => $this->toFloat($row['lastvalue'] ?? 0),
                'units' => (string) ($row['units'] ?? ''),
                'value_type' => (int) ($row['value_type'] ?? 3),
                'mapped_value' => $this->mapItemValue($row)
            ];
        }

        return $result;
    }

    private function mapItemValue(array $item): string {
        $raw_value = (string) ($item['lastvalue'] ?? '');
        if ($raw_value === '') {
            return '';
        }

        $valuemap = $item['valuemap'] ?? [];
        if (!is_array($valuemap) || $valuemap === []) {
            return '';
        }

        $mapped_value = CValueMapHelper::getMappedValue((int) ($item['value_type'] ?? 3), $raw_value, $valuemap);
        return $mapped_value !== false ? (string) $mapped_value : '';
    }

    private function normalizeInterfaceStatus($value, string $mapped_value = ''): array {
        $mapped_value = trim($mapped_value);
        if ($mapped_value !== '') {
            return [
                'value' => is_numeric((string) $value) ? (int) round($this->toFloat($value)) : null,
                'label' => $mapped_value,
                'is_online' => $this->inferOnlineFromMappedLabel($mapped_value)
            ];
        }

        if ($value === null || $value === '') {
            return [
                'value' => null,
                'label' => 'unknown',
                'is_online' => null
            ];
        }

        $status = (int) round($this->toFloat($value));
        return match ($status) {
            1 => ['value' => 1, 'label' => 'up', 'is_online' => true],
            2 => ['value' => 2, 'label' => 'down', 'is_online' => false],
            3 => ['value' => 3, 'label' => 'testing', 'is_online' => false],
            4 => ['value' => 4, 'label' => 'unknown', 'is_online' => null],
            5 => ['value' => 5, 'label' => 'dormant', 'is_online' => false],
            6 => ['value' => 6, 'label' => 'not present', 'is_online' => false],
            7 => ['value' => 7, 'label' => 'lower layer down', 'is_online' => false],
            default => ['value' => $status, 'label' => 'unknown', 'is_online' => null]
        };
    }

    private function inferOnlineFromMappedLabel(string $label): ?bool {
        $normalized = strtolower(trim($label));
        if ($normalized === '') {
            return null;
        }

        if (preg_match('/\b(up|online|ok|enabled|connected)\b/', $normalized) === 1) {
            return true;
        }

        if (preg_match('/\b(down|offline|disabled|disconnected|dormant|testing|not present|lower layer down)\b/', $normalized) === 1) {
            return false;
        }

        return null;
    }

    private function resolvePortColor(array $port): string {
        if (!empty($port['is_problem'])) {
            return (string) ($port['problem_color'] ?? '#FB7185');
        }

        if (array_key_exists('is_online', $port) && $port['is_online'] !== null) {
            return $port['is_online']
                ? (string) ($port['ok_color'] ?? '#34D399')
                : (string) ($port['default_color'] ?? '#64748B');
        }

        if (!empty($port['has_trigger'])) {
            return (string) ($port['ok_color'] ?? '#34D399');
        }

        return (string) ($port['default_color'] ?? '#64748B');
    }

    private function buildSummary(array $layout, array $ports, array $host): array {
        $configured = 0;
        $problem_ports = 0;
        $up_ports = 0;
        $down_ports = 0;
        $peak_utilization = 0.0;
        $hot_ports = 0;

        foreach ($ports as $port) {
            if (!empty($port['has_trigger'])) {
                $configured++;
            }

            if (!empty($port['is_problem'])) {
                $problem_ports++;
            }

            if (array_key_exists('is_online', $port) && $port['is_online'] !== null) {
                if ($port['is_online']) {
                    $up_ports++;
                }
                else {
                    $down_ports++;
                }
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

        return [
            'configured_ports' => $configured,
            'problem_ports' => $problem_ports,
            'healthy_ports' => $up_ports,
            'up_ports' => $up_ports,
            'down_ports' => $down_ports,
            'total_ports' => $total,
            'peak_utilization' => round($peak_utilization, 1),
            'hot_ports' => $hot_ports,
            'maintenance' => !empty($host) && (int) ($host['maintenance_status'] ?? 0) === 1,
            'monitoring_enabled' => empty($host) || (int) ($host['status'] ?? 0) === 0,
            'last_updated' => date('H:i')
        ];
    }

    private function usesDirectTrafficValues(string $key, string $name, string $units): bool {
        $key = strtolower($key);
        $name = strtolower($name);
        $units = strtolower(trim($units));

        if ($units === 'bps' || $units === 'b/s' || $units === 'bit/s') {
            return true;
        }

        return str_starts_with($key, 'net.if.in[')
            || str_starts_with($key, 'net.if.out[')
            || strpos($name, 'bits received') !== false
            || strpos($name, 'bits sent') !== false;
    }

    private function looksLikeCounterKey(string $key, string $name = '', string $units = ''): bool {
        $key = strtolower($key);
        $name = strtolower($name);
        $units = strtolower(trim($units));

        if ($this->usesDirectTrafficValues($key, $name, $units)) {
            return false;
        }

        return strpos($key, 'octet') !== false
            || strpos($key, 'byte') !== false
            || strpos($key, 'counter') !== false
            || strpos($units, 'octets') !== false
            || strpos($units, 'bytes') !== false;
    }

    private function normalizeTrafficToBps(float $value, string $key, string $units = ''): float {
        $key = strtolower($key);
        $units = strtolower(trim($units));

        if ($units === 'bps' || $units === 'b/s' || $units === 'bit/s') {
            return $value;
        }

        if (strpos($key, 'octet') !== false || strpos($key, 'byte') !== false || strpos($units, 'bytes') !== false || strpos($units, 'octets') !== false) {
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

    private function toSpeedBps(float $speed_value, string $speed_key, string $speed_units = ''): float {
        if ($speed_value <= 0.0) {
            return 0.0;
        }

        $speed_units = strtolower(trim($speed_units));
        if ($speed_units === 'bps' || $speed_units === 'b/s' || $speed_units === 'bit/s') {
            return $speed_value;
        }

        if (stripos($speed_key, 'ifhighspeed') !== false) {
            return $speed_value * 1000000.0;
        }

        return $speed_value;
    }
}
