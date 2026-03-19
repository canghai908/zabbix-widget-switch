<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Includes;

use API;

class PortDiscovery {
    private const MAX_TOTAL_PORTS = 96;

    public static function discover(string $hostid, array $patterns): array {
        if ($hostid === '') {
            return [
                'ports' => [],
                'layout' => self::buildLayout([])
            ];
        }

        $triggers = self::loadTriggers($hostid);
        $ports = self::discoverPortsFromItems($hostid, $patterns);

        if ($ports === []) {
            $ports = self::discoverPortsFromTriggers($triggers);
        }

        self::assignDefaultTriggers($ports, $triggers);

        return [
            'ports' => array_values(array_slice($ports, 0, self::MAX_TOTAL_PORTS)),
            'layout' => self::buildLayout($ports)
        ];
    }

    private static function discoverPortsFromItems(string $hostid, array $patterns): array {
        $rows = API::Item()->get([
            'output' => ['key_', 'name'],
            'hostids' => [$hostid]
        ]);

        if (!is_array($rows) || $rows === []) {
            return [];
        }

        $matchers = [];
        foreach ([
            'traffic_in' => (string) ($patterns['traffic_in'] ?? ''),
            'traffic_out' => (string) ($patterns['traffic_out'] ?? ''),
            'speed' => (string) ($patterns['speed'] ?? ''),
            'status' => (string) ($patterns['status'] ?? '')
        ] as $type => $pattern) {
            $regex = self::buildPatternRegex($pattern);
            if ($regex !== null) {
                $matchers[$type] = $regex;
            }
        }

        if ($matchers === []) {
            return [];
        }

        $ports = [];
        foreach ($rows as $row) {
            $key = (string) ($row['key_'] ?? '');
            $item_name = trim((string) ($row['name'] ?? ''));
            if ($key === '') {
                continue;
            }

            foreach ($matchers as $type => $regex) {
                if (preg_match($regex, $key, $matches) !== 1) {
                    continue;
                }

                $token = trim((string) ($matches[1] ?? ''));
                if ($token === '') {
                    continue;
                }

                $port_name = self::extractInterfaceNameFromItemName($item_name);
                $port_key = self::buildPortKey($token, $port_name);

                if (!array_key_exists($port_key, $ports)) {
                    $mapped_index = self::resolveMappedIndex($token, $port_name);

                    $ports[$port_key] = [
                        'name' => $port_name !== '' ? $port_name : $token,
                        'mapped_index' => $mapped_index,
                        'sort_index' => $mapped_index > 0 ? $mapped_index : PHP_INT_MAX,
                        'is_sfp' => self::isSfpPort($port_name),
                        'traffic_in_token' => '',
                        'traffic_out_token' => '',
                        'speed_token' => '',
                        'status_token' => '',
                        'default_triggerid' => ''
                    ];
                }

                $ports[$port_key][$type.'_token'] = $token;
                if ($ports[$port_key]['name'] === '' && $port_name !== '') {
                    $ports[$port_key]['name'] = $port_name;
                }
            }
        }

        return self::sortPorts($ports);
    }

    private static function discoverPortsFromTriggers(array $triggers): array {
        $ports = [];

        foreach ($triggers as $trigger) {
            $description = (string) ($trigger['description'] ?? '');
            $port_name = self::extractInterfaceNameFromTriggerDescription($description);

            if ($port_name === '') {
                continue;
            }

            $port_key = self::buildPortKey($port_name, $port_name);
            if (!array_key_exists($port_key, $ports)) {
                $mapped_index = self::resolveMappedIndex($port_name, $port_name);

                $ports[$port_key] = [
                    'name' => $port_name,
                    'mapped_index' => $mapped_index,
                    'sort_index' => $mapped_index > 0 ? $mapped_index : PHP_INT_MAX,
                    'is_sfp' => self::isSfpPort($port_name),
                    'traffic_in_token' => '',
                    'traffic_out_token' => '',
                    'speed_token' => '',
                    'status_token' => '',
                    'default_triggerid' => ''
                ];
            }
        }

        return self::sortPorts($ports);
    }

    private static function loadTriggers(string $hostid): array {
        $rows = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'priority'],
            'hostids' => [$hostid],
            'filter' => ['status' => 0]
        ]);

        return is_array($rows) ? $rows : [];
    }

    private static function assignDefaultTriggers(array &$ports, array $triggers): void {
        foreach ($ports as &$port) {
            $best_triggerid = '';
            $best_score = 0;

            foreach ($triggers as $trigger) {
                $score = self::scoreTrigger($port, $trigger);
                if ($score <= $best_score) {
                    continue;
                }

                $best_score = $score;
                $best_triggerid = (string) ($trigger['triggerid'] ?? '');
            }

            $port['default_triggerid'] = $best_triggerid;
        }
        unset($port);
    }

    private static function scoreTrigger(array $port, array $trigger): int {
        $description = (string) ($trigger['description'] ?? '');
        if ($description === '') {
            return 0;
        }

        $description_lc = strtolower($description);
        $description_norm = self::normalizeText($description);
        $port_name = trim((string) ($port['name'] ?? ''));
        $port_name_lc = strtolower($port_name);
        $port_name_norm = self::normalizeText($port_name);
        $mapped_index = (int) ($port['mapped_index'] ?? 0);
        $priority = (int) ($trigger['priority'] ?? 0);
        $score = 0;

        if ($port_name !== '') {
            if ($port_name_lc !== '' && strpos($description_lc, $port_name_lc) !== false) {
                $score += 120;
            }
            elseif ($port_name_norm !== '' && strpos($description_norm, $port_name_norm) !== false) {
                $score += 100;
            }
        }

        if ($mapped_index > 0 && preg_match('/(?:^|[^0-9])'.preg_quote((string) $mapped_index, '/').'(?:[^0-9]|$)/', $description) === 1) {
            if (preg_match('/interface|port|eth|ge|xe|sfp|uplink|down|link/i', $description) === 1) {
                $score += 18;
            }
        }

        if (strpos($description_lc, 'link down') !== false || strpos($description_lc, 'link is down') !== false) {
            $score += 80;
        }
        elseif (strpos($description_lc, ' down') !== false && preg_match('/interface|port|ethernet/i', $description) === 1) {
            $score += 55;
        }

        if (strpos($description_lc, 'high error rate') !== false) {
            $score += 20;
        }
        if (strpos($description_lc, 'high bandwidth usage') !== false) {
            $score += 12;
        }
        if (strpos($description_lc, 'lower speed') !== false) {
            $score += 10;
        }

        if ($score > 0) {
            $score += min(7, $priority);
        }

        return $score;
    }

    private static function buildLayout(array $ports): array {
        $visible_ports = array_values(array_slice($ports, 0, self::MAX_TOTAL_PORTS));
        $total_ports = count($visible_ports);
        $sfp_ports = count(array_filter($visible_ports, static fn(array $port): bool => !empty($port['is_sfp'])));
        $base_ports = max(0, $total_ports - $sfp_ports);

        if ($base_ports > 0) {
            $row_count = max(1, min(6, (int) ceil($base_ports / 24)));
            $ports_per_row = max(1, min(24, (int) ceil($base_ports / $row_count)));
        }
        else {
            $row_count = 1;
            $ports_per_row = 1;
        }

        return [
            'row_count' => $row_count,
            'ports_per_row' => $ports_per_row,
            'sfp_ports' => $sfp_ports,
            'total_ports' => $total_ports,
            'base_ports' => $base_ports
        ];
    }

    private static function sortPorts(array $ports): array {
        $list = array_values($ports);

        usort($list, static function(array $left, array $right): int {
            $left_sfp = !empty($left['is_sfp']);
            $right_sfp = !empty($right['is_sfp']);

            if ($left_sfp !== $right_sfp) {
                return $left_sfp <=> $right_sfp;
            }

            $left_index = (int) ($left['sort_index'] ?? PHP_INT_MAX);
            $right_index = (int) ($right['sort_index'] ?? PHP_INT_MAX);
            if ($left_index !== $right_index) {
                return $left_index <=> $right_index;
            }

            return strnatcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $list;
    }

    private static function buildPatternRegex(string $pattern): ?string {
        $pattern = trim($pattern);
        if ($pattern === '' || strpos($pattern, '*') === false) {
            return null;
        }

        return '/^'.str_replace('\*', '(.+)', preg_quote($pattern, '/')).'$/i';
    }

    private static function buildPortKey(string $token, string $port_name): string {
        $mapped_index = self::resolveMappedIndex($token, $port_name);
        if ($mapped_index > 0) {
            return 'idx:'.$mapped_index;
        }

        $normalized_name = self::normalizeText($port_name);
        if ($normalized_name !== '') {
            return 'name:'.$normalized_name;
        }

        return 'token:'.self::normalizeText($token);
    }

    private static function resolveMappedIndex(string $token, string $port_name): int {
        $from_name = self::extractTrailingIndex($port_name);
        if ($from_name > 0) {
            return $from_name;
        }

        return self::extractTrailingIndex($token);
    }

    private static function extractTrailingIndex(string $value): int {
        if (preg_match('/(\d+)(?!.*\d)/', $value, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private static function extractInterfaceNameFromItemName(string $item_name): string {
        if (preg_match('/Interface\s+(.+?)\(\):/i', $item_name, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        if (preg_match('/Interface\s+(.+?):/i', $item_name, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private static function extractInterfaceNameFromTriggerDescription(string $description): string {
        if (preg_match('/Interface\s+(.+?)\(\):/i', $description, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        if (preg_match('/Port\s+(.+?):/i', $description, $matches) === 1) {
            return trim((string) $matches[1]);
        }

        return '';
    }

    private static function isSfpPort(string $port_name): bool {
        $port_name = strtolower($port_name);
        return strpos($port_name, 'sfp') !== false
            || strpos($port_name, 'qsfp') !== false
            || strpos($port_name, 'uplink') !== false;
    }

    private static function normalizeText(string $value): string {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }
}
