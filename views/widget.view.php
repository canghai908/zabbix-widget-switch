<?php declare(strict_types = 1);

$css = implode('', [
    '.swx-widget{position:relative;overflow:hidden;padding:calc(18px * var(--swx-scale));border-radius:24px;color:var(--swx-text);',
    'background:radial-gradient(circle at top left,var(--swx-glow),transparent 38%),linear-gradient(145deg,var(--swx-bg-top),var(--swx-bg-bottom));',
    'border:1px solid var(--swx-border);box-shadow:0 22px 44px var(--swx-shadow),inset 0 1px 0 rgba(255,255,255,.06);}',
    '.swx-widget:before{content:"";position:absolute;inset:-40% auto auto -10%;width:280px;height:280px;background:radial-gradient(circle,var(--swx-accent-soft),transparent 68%);opacity:.7;pointer-events:none;}',
    '.swx-widget:after{content:"";position:absolute;inset:0;background:linear-gradient(transparent 0 97%,var(--swx-grid-line) 97% 100%),linear-gradient(90deg,transparent 0 97%,var(--swx-grid-line) 97% 100%);background-size:100% 18px,18px 100%;opacity:.16;pointer-events:none;}',
    '.swx-widget[data-theme="light"]{--swx-bg-top:#f8fbfd;--swx-bg-bottom:#eef4f7;--swx-border:rgba(117,141,161,.28);--swx-text:#24313a;--swx-muted:#708491;--swx-accent:#4796c4;--swx-accent-soft:rgba(71,150,196,.18);--swx-glow:rgba(71,150,196,.12);--swx-chip:rgba(255,255,255,.88);--swx-panel:#ffffff;--swx-panel-border:rgba(117,141,161,.20);--swx-socket-top:#347d9d;--swx-socket-bottom:#245f79;--swx-socket-core:#102c39;--swx-sfp-top:rgba(128,154,170,.40);--swx-sfp-bottom:rgba(102,125,141,.28);--swx-gauge-track:linear-gradient(180deg,rgba(255,255,255,.38),rgba(231,239,244,.26));--swx-gauge-border:rgba(255,255,255,.82);--swx-shadow:rgba(36,49,58,.14);--swx-grid-line:rgba(71,150,196,.06);--swx-surface:rgba(255,255,255,.72);--swx-surface-soft:rgba(255,255,255,.62);--swx-port-border:rgba(117,141,161,.18);}',
    '.swx-widget[data-theme="dark"]{--swx-bg-top:#20262d;--swx-bg-bottom:#171c22;--swx-border:rgba(121,136,150,.30);--swx-text:#f2f2f2;--swx-muted:#a7b7c3;--swx-accent:#6bb6ff;--swx-accent-soft:rgba(107,182,255,.18);--swx-glow:rgba(71,150,196,.16);--swx-chip:rgba(34,43,51,.92);--swx-panel:#20262d;--swx-panel-border:rgba(121,136,150,.22);--swx-socket-top:#1d5a73;--swx-socket-bottom:#133b4b;--swx-socket-core:#0d1520;--swx-sfp-top:rgba(255,255,255,.18);--swx-sfp-bottom:rgba(255,255,255,.08);--swx-gauge-track:linear-gradient(180deg,rgba(26,58,72,.34),rgba(14,30,38,.20));--swx-gauge-border:rgba(226,232,240,.55);--swx-shadow:rgba(2,8,23,.24);--swx-grid-line:rgba(255,255,255,.04);--swx-surface:rgba(255,255,255,.04);--swx-surface-soft:rgba(255,255,255,.03);--swx-port-border:rgba(255,255,255,.08);}',
    '.swx-header{position:relative;display:flex;justify-content:space-between;gap:calc(14px * var(--swx-scale));z-index:1;margin-bottom:calc(16px * var(--swx-scale));}',
    '.swx-titlewrap{min-width:0;}',
    '.swx-kicker{font-size:calc(11px * var(--swx-scale));letter-spacing:.22em;text-transform:uppercase;color:var(--swx-accent);font-weight:700;margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-title{font-size:calc(22px * var(--swx-scale));font-weight:800;line-height:1.1;color:var(--swx-text);margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-subtitle{font-size:calc(12px * var(--swx-scale));color:var(--swx-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}',
    '.swx-chiprow{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:calc(6px * var(--swx-scale));}',
    '.swx-chip{display:inline-flex;align-items:center;gap:5px;padding:calc(5px * var(--swx-scale)) calc(8px * var(--swx-scale));border-radius:999px;background:var(--swx-chip);border:1px solid var(--swx-panel-border);font-size:calc(10px * var(--swx-scale));color:var(--swx-text);}',
    '.swx-chip-dot{display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--dot-color,var(--swx-accent));box-shadow:0 0 10px var(--dot-color,var(--swx-accent));}',
    '.swx-metrics{position:relative;z-index:1;display:grid;grid-template-columns:repeat(auto-fit,minmax(132px,1fr));gap:calc(10px * var(--swx-scale));margin-bottom:calc(16px * var(--swx-scale));}',
    '.swx-metric{padding:calc(12px * var(--swx-scale));border-radius:18px;background:var(--swx-surface);border:1px solid var(--swx-panel-border);backdrop-filter:blur(14px);}',
    '.swx-metric-label{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);text-transform:uppercase;letter-spacing:.14em;margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-metric-value{font-size:calc(20px * var(--swx-scale));font-weight:800;color:var(--swx-text);line-height:1;}',
    '.swx-metric-meta{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);margin-top:calc(6px * var(--swx-scale));}',
    '.swx-face{position:relative;z-index:1;display:flex;gap:calc(16px * var(--swx-scale));align-items:stretch;}',
    '.swx-panel{flex:1 1 auto;min-width:0;padding:calc(16px * var(--swx-scale));border-radius:22px;background:linear-gradient(180deg,var(--swx-surface),var(--swx-surface-soft));border:1px solid var(--swx-panel-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.05);}',
    '.swx-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:calc(14px * var(--swx-scale));}',
    '.swx-panel-title{font-size:calc(12px * var(--swx-scale));letter-spacing:.18em;text-transform:uppercase;color:var(--swx-muted);font-weight:700;}',
    '.swx-panel-badge{padding:calc(5px * var(--swx-scale)) calc(10px * var(--swx-scale));border-radius:999px;border:1px solid var(--swx-panel-border);font-size:calc(11px * var(--swx-scale));color:var(--swx-text);}',
    '.swx-grid{display:grid;grid-template-columns:repeat(var(--swx-columns),minmax(0,1fr));gap:calc(10px * var(--swx-scale));}',
    '.swx-uplink{width:min(220px,32%);display:flex;flex-direction:column;gap:calc(10px * var(--swx-scale));}',
    '.swx-port{position:relative;display:grid;align-content:start;text-decoration:none;color:inherit;padding:calc(10px * var(--swx-scale));border-radius:18px;background:linear-gradient(180deg,var(--swx-surface),var(--swx-surface-soft));border:1px solid var(--swx-port-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.05),0 12px 22px var(--swx-shadow);transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;}',
    '.swx-port:hover{transform:translateY(-2px);border-color:var(--swx-panel-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 18px 28px var(--swx-shadow);}',
    '.swx-port:before{content:"";position:absolute;inset:1px;border-radius:17px;background:linear-gradient(160deg,rgba(255,255,255,.04),transparent 42%);pointer-events:none;}',
    '.swx-port-head{min-width:0;margin-bottom:calc(8px * var(--swx-scale));padding-right:calc(18px * var(--swx-scale));}',
    '.swx-port-socket{position:relative;height:calc(34px * var(--swx-scale));border-radius:12px;background:linear-gradient(180deg,var(--swx-socket-top),var(--swx-socket-bottom));border:1px solid var(--swx-port-border);overflow:hidden;margin-bottom:calc(4px * var(--swx-scale));}',
    '.swx-port-socket:before{content:"";position:absolute;left:12%;right:12%;top:22%;height:50%;background:var(--swx-socket-core);border-radius:10px;box-shadow:inset 0 1px 0 rgba(255,255,255,.04);z-index:1;}',
    '.swx-port-socket:after{display:none;}',
    '.swx-port.swx-port-sfp .swx-port-socket{background:linear-gradient(180deg,var(--swx-sfp-top),var(--swx-sfp-bottom));}',
    '.swx-port.swx-port-sfp .swx-port-socket:before{left:10%;right:10%;top:18%;height:52%;border-radius:10px;}',
    '.swx-port-socket.swx-port-socket-overlay{display:flex;align-items:center;justify-content:center;padding:0;background:transparent;border-color:transparent;box-shadow:none;}',
    '.swx-port-socket.swx-port-socket-overlay:before{display:none;}',
    '.swx-port-led{position:absolute;right:calc(10px * var(--swx-scale));top:calc(10px * var(--swx-scale));width:10px;height:10px;border-radius:50%;background:var(--port-color);box-shadow:0 0 0 1px rgba(255,255,255,.18),0 0 14px var(--port-color),0 0 24px rgba(255,255,255,.06);}',
    '.swx-port-heat{position:absolute;left:14%;right:14%;top:26%;height:40%;border-radius:999px;overflow:hidden;z-index:3;}',
    '.swx-port-gauge{display:block;width:100%;height:100%;min-width:0;filter:saturate(2.35) brightness(1.28) contrast(1.18) drop-shadow(0 0 calc(4px * var(--swx-scale)) var(--util-color));opacity:1;}',
    '.swx-port.swx-port-sfp .swx-port-heat{left:12%;right:12%;top:24%;height:44%;}',
    '.swx-port-gauge::part(bar){top:0;left:0;border:1px solid var(--swx-gauge-border);border-radius:999px;background:transparent;box-shadow:none;}',
    '.swx-port-gauge::part(track),.swx-port-gauge::part(empty),.swx-port-gauge::part(container){background:transparent;box-shadow:none;}',
    '.swx-port-socket.swx-port-socket-overlay .swx-port-heat{position:relative;left:auto;right:auto;top:auto;width:100%;height:calc(18px * var(--swx-scale));padding:calc(2px * var(--swx-scale)) calc(3px * var(--swx-scale));transform:none;border-radius:12px;background:var(--swx-surface);border:1px solid var(--swx-port-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.05);}',
    '.swx-port.swx-port-sfp .swx-port-socket.swx-port-socket-overlay .swx-port-heat{width:100%;height:calc(18px * var(--swx-scale));padding:calc(2px * var(--swx-scale)) calc(3px * var(--swx-scale));transform:none;}',
    '.swx-port-name{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;font-size:calc(12px * var(--swx-scale));font-weight:700;line-height:1.3;color:var(--swx-text);word-break:break-word;}',
    '.swx-port-meta{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;font-size:calc(10px * var(--swx-scale));line-height:1.3;color:var(--swx-muted);min-height:calc(26px * var(--swx-scale));}',
    '.swx-port-telemetry{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:calc(6px * var(--swx-scale));margin-top:calc(3px * var(--swx-scale));}',
    '.swx-port-util{grid-column:1 / -1;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:calc(5px * var(--swx-scale)) calc(8px * var(--swx-scale));border-radius:12px;background:var(--swx-surface);border:1px solid var(--swx-port-border);font-size:calc(10px * var(--swx-scale));font-weight:700;color:var(--swx-text);}',
    '.swx-port-util-label{font-size:calc(10px * var(--swx-scale));letter-spacing:.08em;text-transform:uppercase;color:var(--swx-muted);}',
    '.swx-port-util-value{font-size:calc(11px * var(--swx-scale));color:var(--swx-text);}',
    '.swx-port-traffic{min-width:0;padding:calc(5px * var(--swx-scale)) calc(8px * var(--swx-scale));border-radius:12px;background:var(--swx-surface-soft);border:1px solid var(--swx-port-border);}',
    '.swx-port-traffic-label{display:block;font-size:calc(9px * var(--swx-scale));letter-spacing:.08em;text-transform:uppercase;color:var(--swx-muted);margin-bottom:2px;}',
    '.swx-port-traffic-value{display:block;font-size:calc(11px * var(--swx-scale));font-weight:700;color:var(--swx-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
    '.swx-empty{position:relative;z-index:1;padding:calc(26px * var(--swx-scale));border-radius:20px;background:rgba(255,255,255,.03);border:1px dashed var(--swx-panel-border);text-align:center;color:var(--swx-muted);font-size:calc(13px * var(--swx-scale));}',
    '.swx-denied{position:relative;z-index:1;padding:calc(18px * var(--swx-scale));border-radius:18px;background:rgba(127,29,29,.28);border:1px solid rgba(248,113,113,.38);color:#ffe4e6;}',
    '@media (max-width:1100px){.swx-face{flex-direction:column;}.swx-uplink{width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));}}',
    '@media (max-width:760px){.swx-header{flex-direction:column;}.swx-chiprow{justify-content:flex-start;}.swx-grid{grid-template-columns:repeat(auto-fit,minmax(120px,1fr));}}'
]);

$scale = max(0.84, min(1.0, ((int) ($data['panel_scale'] ?? 92)) / 100));
$layout = $data['layout'] ?? ['row_count' => 2, 'ports_per_row' => 12, 'sfp_ports' => 0, 'total_ports' => 24];
$summary = $data['summary'] ?? [];
$host = $data['host'] ?? [];
$theme = (string) ($data['visual_theme'] ?? 'light');
$port_card_label_mode = (int) ($data['port_card_label_mode'] ?? 0);
$show_overlay = !empty($data['utilization_overlay_enabled']);
$format_rate = static function(float $bps): string {
    if ($bps <= 0) {
        return '0 bps';
    }

    $units = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
    $value = $bps;
    $unit_index = 0;
    while ($value >= 1000 && $unit_index < count($units) - 1) {
        $value /= 1000;
        $unit_index++;
    }

    $precision = $value >= 100 ? 0 : ($value >= 10 ? 1 : 2);
    return number_format($value, $precision).$units[$unit_index];
};
$tr = static function(string $english, ?string $chinese = null, ...$args) use ($data): string {
    $mode = (int) ($data['card_language_mode'] ?? 0);
    $user_lang = strtolower((string) ($data['user_lang'] ?? 'en_US'));
    $text = $english;

    if ($mode === 1) {
        $text = $chinese ?? $english;
    }
    elseif ($mode === 2) {
        $text = $english;
    }
    else {
        $translated = _($english);
        if ($translated !== $english) {
            $text = $translated;
        }
        elseif (substr($user_lang, 0, 2) === 'zh' && $chinese !== null) {
            $text = $chinese;
        }
    }

    return $args !== [] ? vsprintf($text, $args) : $text;
};

$widget = (new CDiv())
    ->addClass('swx-widget')
    ->setAttribute('data-theme', $theme)
    ->setAttribute('style', '--swx-scale: '.$scale.'; --swx-columns: '.max(1, (int) ($layout['ports_per_row'] ?? 12)).';');

if (!empty($data['access_denied'])) {
    $widget->addItem((new CDiv($tr('Access denied: no permissions for selected host.', '无权限访问所选主机。')))->addClass('swx-denied'));

    (new CWidgetView($data))
        ->addItem(new CTag('style', true, $css))
        ->addItem($widget)
        ->show();

    return;
}

$host_name = trim((string) ($host['name'] ?? ''));
$host_endpoint = trim((string) ($host['ip'] ?? '')) !== '' ? (string) $host['ip'] : (string) ($host['dns'] ?? '');
$header = (new CDiv())->addClass('swx-header');
$title_wrap = (new CDiv())->addClass('swx-titlewrap');
$title_wrap
    ->addItem((new CDiv($host_name !== '' ? $host_name : (string) $data['name']))->addClass('swx-title'));

$subtitle_parts = array_filter([
    (string) ($data['switch_brand'] ?? ''),
    (string) ($data['switch_model'] ?? ''),
    (string) ($data['switch_role'] ?? ''),
    $host_endpoint !== '' ? $host_endpoint : ''
], static fn(string $value): bool => trim($value) !== '');
$title_wrap->addItem((new CDiv($subtitle_parts !== [] ? implode(' / ', $subtitle_parts) : $tr('Select a host to bind switch health.', '请选择主机以绑定交换机状态。')))->addClass('swx-subtitle'));
$header->addItem($title_wrap);

$chip_row = (new CDiv())->addClass('swx-chiprow');
$chip_row
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.(!empty($summary['up_ports']) ? '#34D399' : '#94A3B8').';'))
            ->addItem($tr('Up ports %d', 'Up端口 %d', (int) ($summary['up_ports'] ?? 0)))
    )
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.(!empty($summary['down_ports']) ? '#F59E0B' : '#94A3B8').';'))
            ->addItem($tr('Down ports %d', 'Down端口 %d', (int) ($summary['down_ports'] ?? 0)))
    )
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.(!empty($summary['problem_ports']) ? '#FB7185' : '#34D399').';'))
            ->addItem($tr('Alert ports %d', '异常端口 %d', (int) ($summary['problem_ports'] ?? 0)))
    );
$header->addItem($chip_row);
$widget->addItem($header);

$metrics = (new CDiv())->addClass('swx-metrics');
$metric_cards = [
    [
        'label' => $tr('Configured', '已配置'),
        'value' => sprintf('%d / %d', (int) ($summary['configured_ports'] ?? 0), (int) ($summary['total_ports'] ?? 0)),
        'meta' => $tr('ports bound to triggers', '已绑定触发器的端口')
    ],
    [
        'label' => $tr('Up', '在线端口'),
        'value' => (string) ((int) ($summary['healthy_ports'] ?? 0)),
        'meta' => $tr('ports currently up', '当前处于在线状态的端口')
    ],
    [
        'label' => $tr('Layout', '布局'),
        'value' => sprintf('%d x %d', (int) ($layout['row_count'] ?? 0), (int) ($layout['ports_per_row'] ?? 0)),
        'meta' => $tr('auto layout', '自动布局')
    ],
    [
        'label' => $tr('Peak Util', '峰值利用率'),
        'value' => sprintf('%s%%', number_format((float) ($summary['peak_utilization'] ?? 0), 1)),
        'meta' => $show_overlay
            ? $tr('highest live port load', '当前最高端口负载')
            : $tr('telemetry overlay disabled', '遥测覆盖已关闭')
    ],
    [
        'label' => $tr('Hot Ports', '高负载端口'),
        'value' => (string) ((int) ($summary['hot_ports'] ?? 0)),
        'meta' => $tr('ports above 70% utilization', '利用率超过 70% 的端口')
    ],
    [
        'label' => $tr('Updated', '更新时间'),
        'value' => (string) ($summary['last_updated'] ?? date('H:i')),
        'meta' => $host_endpoint !== '' ? $host_endpoint : $tr('waiting for host bind', '等待绑定主机')
    ]
];
foreach ($metric_cards as $metric) {
    $card = (new CDiv())->addClass('swx-metric');
    $card
        ->addItem((new CDiv($metric['label']))->addClass('swx-metric-label'))
        ->addItem((new CDiv($metric['value']))->addClass('swx-metric-value'))
        ->addItem((new CDiv($metric['meta']))->addClass('swx-metric-meta'));
    $metrics->addItem($card);
}
$widget->addItem($metrics);

$ports = $data['ports'] ?? [];
if ($ports === []) {
    $widget->addItem((new CDiv($tr(
        'Select a host and map port triggers to render the switch front panel.',
        '请选择主机并映射端口触发器以显示交换机面板。'
    )))->addClass('swx-empty'));

    (new CWidgetView($data))
        ->addItem(new CTag('style', true, $css))
        ->addItem($widget)
        ->show();

    return;
}

$utp_ports = [];
$sfp_ports = [];
foreach ($ports as $port) {
    if (!empty($port['is_sfp'])) {
        $sfp_ports[] = $port;
    }
    else {
        $utp_ports[] = $port;
    }
}

$face = (new CDiv())->addClass('swx-face');
$panel = (new CDiv())->addClass('swx-panel');
$panel_head = (new CDiv())->addClass('swx-panel-head');
$panel_head->addItem((new CDiv($tr('Ports', '端口面板')))->addClass('swx-panel-title'));
$panel->addItem($panel_head);

$grid = (new CDiv())->addClass('swx-grid');
$make_port = static function(array $port) use ($port_card_label_mode, $show_overlay, $format_rate, $tr) {
    $state = $tr('Idle', '空闲');
    if (!empty($port['status_label'])) {
        $state = (string) $port['status_label'];
    }
    elseif (!empty($port['has_trigger'])) {
        $state = !empty($port['is_problem'])
            ? $tr('Alert', '告警')
            : $tr('Online', '在线');
    }

    $port_name = trim((string) ($port['name'] ?? ''));
    $port_description = trim((string) ($port['description'] ?? ''));
    $display_name = $port_card_label_mode === 1 && $port_description !== ''
        ? $port_description
        : $port_name;
    if ($display_name === '' || preg_match('/^(?:GE|SFP)\s+\d+$/i', $display_name) === 1) {
        foreach (['traffic_in_token', 'traffic_out_token', 'speed_token'] as $token_key) {
            $token = trim((string) ($port[$token_key] ?? ''));
            if ($token !== '') {
                $display_name = $token;
                break;
            }
        }
    }
    if ($display_name === '') {
        $display_name = (string) ($port['port_code'] ?? '');
    }

    $tooltip_parts = [
        $display_name,
        $tr(
            'Port description: %s',
            '端口描述：%s',
            $port_description !== '' ? $port_description : $tr('not set', '未设置')
        ),
        $tr('State: %s', '状态：%s', $state)
    ];
    if ($port_name !== '' && $port_name !== $display_name) {
        $tooltip_parts[] = $tr('Port name: %s', '端口名称：%s', $port_name);
    }
    if ($port_description !== '' && $port_description !== $display_name) {
        $tooltip_parts[] = $tr('Port description: %s', '端口描述：%s', $port_description);
    }
    if ($show_overlay && isset($port['utilization_percent']) && $port['utilization_percent'] !== null) {
        $tooltip_parts[] = $tr('Utilization: %s%%', '利用率：%s%%', number_format((float) $port['utilization_percent'], 1));
        $tooltip_parts[] = $tr('In: %s', '入流量：%s', $format_rate((float) ($port['traffic_in_bps'] ?? 0.0)));
        $tooltip_parts[] = $tr('Out: %s', '出流量：%s', $format_rate((float) ($port['traffic_out_bps'] ?? 0.0)));
    }
    if (!empty($port['trigger_name'])) {
        $tooltip_parts[] = $tr('Trigger: %s', '触发器：%s', (string) $port['trigger_name']);
    }
    elseif (!empty($port['triggerid'])) {
        $tooltip_parts[] = $tr('Trigger ID: %s', '触发器 ID：%s', (string) $port['triggerid']);
    }
    else {
        $tooltip_parts[] = $tr('Trigger: not configured', '未配置触发器');
    }

    $card = !empty($port['url'])
        ? (new CTag('a', true, ''))
        : new CDiv();

    if (!empty($port['url'])) {
        $card->setAttribute('href', (string) $port['url']);
    }

    $card
        ->addClass('swx-port')
        ->setAttribute(
            'style',
            '--port-color: '.(string) ($port['active_color'] ?? '#64748B').'; --util-color: '.(string) ($port['utilization_color'] ?? '#64748B').';'
        )
        ->setAttribute('title', implode("\n", $tooltip_parts));

    if (!empty($port['is_sfp'])) {
        $card->addClass('swx-port-sfp');
    }

    $head = (new CDiv())->addClass('swx-port-head');
    $head->addItem((new CDiv($display_name))->addClass('swx-port-name'));

    $socket = (new CDiv())->addClass('swx-port-socket');
    if ($show_overlay) {
        $socket->addClass('swx-port-socket-overlay');
        $utilization = isset($port['utilization_percent']) && $port['utilization_percent'] !== null
            ? max(0.0, min(100.0, (float) $port['utilization_percent']))
            : 0.0;
        $heat = (new CDiv())->addClass('swx-port-heat');
        $heat->addItem(
            (new CBarGauge())
                ->addClass('swx-port-gauge')
                ->setValue($utilization)
                ->setAttribute('fill', (string) ($port['utilization_color'] ?? '#64748B'))
                ->setAttribute('min', 0)
                ->setAttribute('max', 100)
        );
        $socket->addItem($heat);
    }

    $card
        ->addItem($head)
        ->addItem((new CDiv())->addClass('swx-port-led'))
        ->addItem($socket);

    if ($show_overlay) {
        $telemetry = (new CDiv())->addClass('swx-port-telemetry');
        if (isset($port['utilization_percent']) && $port['utilization_percent'] !== null) {
            $telemetry->addItem(
                (new CDiv())
                    ->addClass('swx-port-util')
                    ->addItem((new CDiv($tr('Util', '利用率')))->addClass('swx-port-util-label'))
                    ->addItem((new CDiv(number_format((float) $port['utilization_percent'], 1).'%'))->addClass('swx-port-util-value'))
            );
        }

        foreach ([
            ['label' => $tr('In', '入'), 'value' => $format_rate((float) ($port['traffic_in_bps'] ?? 0.0))],
            ['label' => $tr('Out', '出'), 'value' => $format_rate((float) ($port['traffic_out_bps'] ?? 0.0))]
        ] as $traffic_metric) {
            $telemetry->addItem(
                (new CDiv())
                    ->addClass('swx-port-traffic')
                    ->addItem((new CDiv($traffic_metric['label']))->addClass('swx-port-traffic-label'))
                    ->addItem((new CDiv($traffic_metric['value']))->addClass('swx-port-traffic-value'))
            );
        }

        $card->addItem($telemetry);
    }

    return $card;
};

foreach ($utp_ports as $port) {
    $grid->addItem($make_port($port));
}
$panel->addItem($grid);
$face->addItem($panel);

if ($sfp_ports !== []) {
    $uplink = (new CDiv())->addClass('swx-uplink');
    foreach ($sfp_ports as $port) {
        $uplink->addItem($make_port($port));
    }
    $face->addItem($uplink);
}

$widget->addItem($face);

(new CWidgetView($data))
    ->addItem(new CTag('style', true, $css))
    ->addItem($widget)
    ->show();
