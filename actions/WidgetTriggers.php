<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Actions;

use API;
use CController;
use CControllerResponseData;
use Modules\SwitchPanelWidget\Includes\PortDiscovery;

class WidgetTriggers extends CController {
    private const DEFAULT_TRAFFIC_IN_PATTERN = 'net.if.in[*]';
    private const DEFAULT_TRAFFIC_OUT_PATTERN = 'net.if.out[*]';
    private const DEFAULT_SPEED_PATTERN = 'net.if.speed[*]';

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return $this->validateInput([
            'hostid' => 'required|id',
            'traffic_in_item_pattern' => 'string',
            'traffic_out_item_pattern' => 'string',
            'speed_item_pattern' => 'string'
        ]);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $hostid = (string) $this->getInput('hostid');
        $traffic_in_pattern = $this->sanitizeItemPattern(
            (string) $this->getInput('traffic_in_item_pattern', self::DEFAULT_TRAFFIC_IN_PATTERN),
            self::DEFAULT_TRAFFIC_IN_PATTERN
        );
        $traffic_out_pattern = $this->sanitizeItemPattern(
            (string) $this->getInput('traffic_out_item_pattern', self::DEFAULT_TRAFFIC_OUT_PATTERN),
            self::DEFAULT_TRAFFIC_OUT_PATTERN
        );
        $speed_pattern = $this->sanitizeItemPattern(
            (string) $this->getInput('speed_item_pattern', self::DEFAULT_SPEED_PATTERN),
            self::DEFAULT_SPEED_PATTERN
        );
        $discovery = PortDiscovery::discover($hostid, [
            'traffic_in' => $traffic_in_pattern,
            'traffic_out' => $traffic_out_pattern,
            'speed' => $speed_pattern
        ]);

        $rows = API::Trigger()->get([
            'output' => ['triggerid', 'description', 'priority'],
            'hostids' => [$hostid],
            'filter' => ['status' => 0],
            'sortfield' => ['priority', 'description'],
            'sortorder' => ZBX_SORT_DOWN
        ]);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (string) $row['triggerid'],
                'name' => sprintf('[P%s] %s', (string) $row['priority'], (string) $row['description'])
            ];
        }

        $ports = [];
        foreach ($discovery['ports'] ?? [] as $index => $port) {
            $ports[] = [
                'index' => $index + 1,
                'name' => (string) ($port['name'] ?? ''),
                'is_sfp' => !empty($port['is_sfp']),
                'default_triggerid' => (string) ($port['default_triggerid'] ?? '')
            ];
        }
        $recommended_items = $this->findRecommendedMetadataItems($hostid);

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'triggers' => $result,
                'ports' => $ports,
                'layout' => $discovery['layout'] ?? null,
                'recommended_items' => $recommended_items
            ])
        ]));
    }

    private function sanitizeItemPattern(string $value, string $fallback): string {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        return substr($value, 0, 255);
    }

    private function findRecommendedMetadataItems(string $hostid): array {
        if ($hostid === '') {
            return [];
        }

        $rows = API::Item()->get([
            'output' => ['itemid', 'name', 'key_'],
            'hostids' => [$hostid]
        ]);

        if (!is_array($rows) || $rows === []) {
            return [];
        }

        $fields = [
            'switch_brand_itemids' => [
                'name_terms' => ['vendor', 'brand', 'manufacturer'],
                'key_terms' => ['vendor', 'brand', 'manufacturer'],
                'avoid_terms' => ['interface', 'port', 'cpu', 'memory', 'disk']
            ],
            'switch_model_itemids' => [
                'name_terms' => ['model', 'product name', 'product', 'hardware model'],
                'key_terms' => ['model', 'product'],
                'avoid_terms' => ['interface', 'port', 'cpu', 'memory', 'disk']
            ],
            'switch_role_itemids' => [
                'name_terms' => ['role', 'device role', 'system role', 'site role', 'function'],
                'key_terms' => ['role', 'function'],
                'avoid_terms' => ['interface', 'port', 'cpu', 'memory', 'disk']
            ]
        ];

        $result = [];

        foreach ($fields as $field => $config) {
            $best = null;
            $best_score = 0;

            foreach ($rows as $row) {
                $score = $this->scoreMetadataItem($row, $config);
                if ($score <= $best_score) {
                    continue;
                }

                $best_score = $score;
                $best = $row;
            }

            if ($best !== null) {
                $result[$field] = [
                    'id' => (string) ($best['itemid'] ?? ''),
                    'name' => trim((string) ($best['name'] ?? ''))
                ];
            }
        }

        return $result;
    }

    private function scoreMetadataItem(array $row, array $config): int {
        $name = strtolower(trim((string) ($row['name'] ?? '')));
        $key = strtolower(trim((string) ($row['key_'] ?? '')));
        if ($name === '' && $key === '') {
            return 0;
        }

        $score = 0;

        foreach ($config['avoid_terms'] as $term) {
            if (($name !== '' && strpos($name, $term) !== false) || ($key !== '' && strpos($key, $term) !== false)) {
                $score -= 25;
            }
        }

        foreach ($config['name_terms'] as $term) {
            if ($name !== '' && strpos($name, $term) !== false) {
                $score += 30;
            }
        }

        foreach ($config['key_terms'] as $term) {
            if ($key !== '' && strpos($key, $term) !== false) {
                $score += 24;
            }
        }

        if (strpos($key, 'inventory') !== false) {
            $score += 8;
        }

        return max(0, $score);
    }
}
