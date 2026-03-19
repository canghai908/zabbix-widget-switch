<?php declare(strict_types = 1);

$css = implode('', [
    '.swx-widget{position:relative;overflow:hidden;padding:calc(18px * var(--swx-scale));border-radius:24px;color:var(--swx-text);',
    'background:radial-gradient(circle at top left,var(--swx-glow),transparent 38%),linear-gradient(145deg,var(--swx-bg-top),var(--swx-bg-bottom));',
    'border:1px solid var(--swx-border);box-shadow:0 22px 44px rgba(15,23,42,.22),inset 0 1px 0 rgba(255,255,255,.06);}',
    '.swx-widget:before{content:"";position:absolute;inset:-40% auto auto -10%;width:280px;height:280px;background:radial-gradient(circle,var(--swx-accent-soft),transparent 68%);opacity:.7;pointer-events:none;}',
    '.swx-widget:after{content:"";position:absolute;inset:0;background:linear-gradient(transparent 0 97%,rgba(255,255,255,.05) 97% 100%),linear-gradient(90deg,transparent 0 97%,rgba(255,255,255,.04) 97% 100%);background-size:100% 18px,18px 100%;opacity:.16;pointer-events:none;}',
    '.swx-widget[data-theme="graphite"]{--swx-bg-top:#121a28;--swx-bg-bottom:#0a1019;--swx-border:rgba(148,163,184,.25);--swx-text:#e2ebf5;--swx-muted:#8ea0b5;--swx-accent:#38bdf8;--swx-accent-soft:rgba(56,189,248,.26);--swx-glow:rgba(15,118,110,.22);--swx-chip:#152132;--swx-panel:#0f1724;--swx-panel-border:rgba(148,163,184,.18);--swx-socket-top:#1f2937;--swx-socket-bottom:#0b1220;}',
    '.swx-widget[data-theme="aurora"]{--swx-bg-top:#0b1c24;--swx-bg-bottom:#071117;--swx-border:rgba(45,212,191,.28);--swx-text:#e7f7f6;--swx-muted:#8dd6cf;--swx-accent:#2dd4bf;--swx-accent-soft:rgba(45,212,191,.24);--swx-glow:rgba(59,130,246,.22);--swx-chip:#0d2226;--swx-panel:#09171b;--swx-panel-border:rgba(45,212,191,.18);--swx-socket-top:#134e4a;--swx-socket-bottom:#052e2b;}',
    '.swx-widget[data-theme="ember"]{--swx-bg-top:#221510;--swx-bg-bottom:#120a08;--swx-border:rgba(251,146,60,.24);--swx-text:#fff1ea;--swx-muted:#f7b690;--swx-accent:#fb923c;--swx-accent-soft:rgba(251,146,60,.24);--swx-glow:rgba(244,63,94,.18);--swx-chip:#261713;--swx-panel:#180e0b;--swx-panel-border:rgba(251,146,60,.18);--swx-socket-top:#7c2d12;--swx-socket-bottom:#431407;}',
    '.swx-header{position:relative;display:flex;justify-content:space-between;gap:calc(14px * var(--swx-scale));z-index:1;margin-bottom:calc(16px * var(--swx-scale));}',
    '.swx-titlewrap{min-width:0;}',
    '.swx-kicker{font-size:calc(11px * var(--swx-scale));letter-spacing:.22em;text-transform:uppercase;color:var(--swx-accent);font-weight:700;margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-title{font-size:calc(22px * var(--swx-scale));font-weight:800;line-height:1.1;color:var(--swx-text);margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-subtitle{font-size:calc(12px * var(--swx-scale));color:var(--swx-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}',
    '.swx-chiprow{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:calc(8px * var(--swx-scale));}',
    '.swx-chip{display:inline-flex;align-items:center;gap:6px;padding:calc(7px * var(--swx-scale)) calc(10px * var(--swx-scale));border-radius:999px;background:var(--swx-chip);border:1px solid var(--swx-panel-border);font-size:calc(11px * var(--swx-scale));color:var(--swx-text);}',
    '.swx-chip-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--dot-color,var(--swx-accent));box-shadow:0 0 12px var(--dot-color,var(--swx-accent));}',
    '.swx-metrics{position:relative;z-index:1;display:grid;grid-template-columns:repeat(auto-fit,minmax(132px,1fr));gap:calc(10px * var(--swx-scale));margin-bottom:calc(16px * var(--swx-scale));}',
    '.swx-metric{padding:calc(12px * var(--swx-scale));border-radius:18px;background:rgba(255,255,255,.03);border:1px solid var(--swx-panel-border);backdrop-filter:blur(14px);}',
    '.swx-metric-label{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);text-transform:uppercase;letter-spacing:.14em;margin-bottom:calc(6px * var(--swx-scale));}',
    '.swx-metric-value{font-size:calc(20px * var(--swx-scale));font-weight:800;color:var(--swx-text);line-height:1;}',
    '.swx-metric-meta{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);margin-top:calc(6px * var(--swx-scale));}',
    '.swx-face{position:relative;z-index:1;display:flex;gap:calc(16px * var(--swx-scale));align-items:stretch;}',
    '.swx-panel{flex:1 1 auto;min-width:0;padding:calc(16px * var(--swx-scale));border-radius:22px;background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.015));border:1px solid var(--swx-panel-border);box-shadow:inset 0 1px 0 rgba(255,255,255,.05);}',
    '.swx-panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:calc(14px * var(--swx-scale));}',
    '.swx-panel-title{font-size:calc(12px * var(--swx-scale));letter-spacing:.18em;text-transform:uppercase;color:var(--swx-muted);font-weight:700;}',
    '.swx-panel-badge{padding:calc(5px * var(--swx-scale)) calc(10px * var(--swx-scale));border-radius:999px;border:1px solid var(--swx-panel-border);font-size:calc(11px * var(--swx-scale));color:var(--swx-text);}',
    '.swx-grid{display:grid;grid-template-columns:repeat(var(--swx-columns),minmax(0,1fr));gap:calc(10px * var(--swx-scale));}',
    '.swx-uplink{width:min(220px,32%);display:flex;flex-direction:column;gap:calc(10px * var(--swx-scale));}',
    '.swx-port{position:relative;display:block;text-decoration:none;color:inherit;padding:calc(10px * var(--swx-scale));border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.04),rgba(255,255,255,.01));border:1px solid rgba(255,255,255,.08);box-shadow:inset 0 1px 0 rgba(255,255,255,.05),0 12px 22px rgba(2,8,23,.18);transition:transform .16s ease,border-color .16s ease,box-shadow .16s ease;}',
    '.swx-port:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.16);box-shadow:inset 0 1px 0 rgba(255,255,255,.06),0 18px 28px rgba(2,8,23,.26);}',
    '.swx-port:before{content:"";position:absolute;inset:1px;border-radius:17px;background:linear-gradient(160deg,rgba(255,255,255,.04),transparent 42%);pointer-events:none;}',
    '.swx-port-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:calc(10px * var(--swx-scale));}',
    '.swx-port-index{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);font-weight:700;letter-spacing:.14em;}',
    '.swx-port-state{font-size:calc(10px * var(--swx-scale));font-weight:700;text-transform:uppercase;letter-spacing:.14em;color:var(--swx-text);}',
    '.swx-port-socket{position:relative;height:calc(36px * var(--swx-scale));border-radius:12px;background:linear-gradient(180deg,var(--swx-socket-top),var(--swx-socket-bottom));border:1px solid rgba(255,255,255,.08);overflow:hidden;margin-bottom:calc(10px * var(--swx-scale));}',
    '.swx-port-socket:before{content:"";position:absolute;left:12%;right:12%;top:18%;height:34%;background:#020617;border-radius:8px 8px 3px 3px;box-shadow:inset 0 1px 0 rgba(255,255,255,.04);}',
    '.swx-port-socket:after{content:"";position:absolute;left:14%;right:14%;bottom:16%;height:16%;background:repeating-linear-gradient(90deg,rgba(226,232,240,.85) 0 3px,transparent 3px 6px);opacity:.8;}',
    '.swx-port.swx-port-sfp .swx-port-socket{background:linear-gradient(180deg,rgba(255,255,255,.18),rgba(255,255,255,.08));}',
    '.swx-port.swx-port-sfp .swx-port-socket:before{left:10%;right:10%;top:16%;height:44%;}',
    '.swx-port-led{position:absolute;right:calc(10px * var(--swx-scale));top:calc(10px * var(--swx-scale));width:10px;height:10px;border-radius:50%;background:var(--port-color);box-shadow:0 0 0 1px rgba(255,255,255,.18),0 0 14px var(--port-color),0 0 24px rgba(255,255,255,.06);}',
    '.swx-port-heat{position:absolute;left:6px;right:6px;bottom:6px;height:5px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;}',
    '.swx-port-heat-fill{display:block;height:100%;border-radius:999px;box-shadow:0 0 16px var(--util-color,rgba(255,255,255,.2));}',
    '.swx-port-name{font-size:calc(13px * var(--swx-scale));font-weight:700;color:var(--swx-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:calc(4px * var(--swx-scale));}',
    '.swx-port-meta{font-size:calc(11px * var(--swx-scale));color:var(--swx-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}',
    '.swx-port-telemetry{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:calc(8px * var(--swx-scale));}',
    '.swx-port-util{display:inline-flex;align-items:center;padding:calc(4px * var(--swx-scale)) calc(8px * var(--swx-scale));border-radius:999px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);font-size:calc(10px * var(--swx-scale));font-weight:700;color:var(--swx-text);}',
    '.swx-port-traffic{display:flex;flex-direction:column;align-items:flex-end;gap:2px;font-size:calc(10px * var(--swx-scale));color:var(--swx-muted);text-align:right;}',
    '.swx-empty{position:relative;z-index:1;padding:calc(26px * var(--swx-scale));border-radius:20px;background:rgba(255,255,255,.03);border:1px dashed var(--swx-panel-border);text-align:center;color:var(--swx-muted);font-size:calc(13px * var(--swx-scale));}',
    '.swx-denied{position:relative;z-index:1;padding:calc(18px * var(--swx-scale));border-radius:18px;background:rgba(127,29,29,.28);border:1px solid rgba(248,113,113,.38);color:#ffe4e6;}',
    '@media (max-width:1100px){.swx-face{flex-direction:column;}.swx-uplink{width:100%;display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));}}',
    '@media (max-width:760px){.swx-header{flex-direction:column;}.swx-chiprow{justify-content:flex-start;}.swx-grid{grid-template-columns:repeat(auto-fit,minmax(120px,1fr));}}'
]);

$scale = max(0.84, min(1.0, ((int) ($data['panel_scale'] ?? 92)) / 100));
$layout = $data['layout'] ?? ['row_count' => 2, 'ports_per_row' => 12, 'sfp_ports' => 0, 'total_ports' => 24];
$summary = $data['summary'] ?? [];
$host = $data['host'] ?? [];
$theme = (string) ($data['visual_theme'] ?? 'graphite');
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

$widget = (new CDiv())
    ->addClass('swx-widget')
    ->setAttribute('data-theme', $theme)
    ->setAttribute('style', '--swx-scale: '.$scale.'; --swx-columns: '.max(1, (int) ($layout['ports_per_row'] ?? 12)).';');

if (!empty($data['access_denied'])) {
    $widget->addItem((new CDiv(_('Access denied: no permissions for selected host.')))->addClass('swx-denied'));

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
    ->addItem((new CDiv(_('Network Switch Fabric')))->addClass('swx-kicker'))
    ->addItem((new CDiv($host_name !== '' ? $host_name : (string) $data['name']))->addClass('swx-title'));

$subtitle_parts = array_filter([
    (string) ($data['switch_brand'] ?? ''),
    (string) ($data['switch_model'] ?? ''),
    (string) ($data['switch_role'] ?? ''),
    $host_endpoint !== '' ? $host_endpoint : ''
], static fn(string $value): bool => trim($value) !== '');
$title_wrap->addItem((new CDiv($subtitle_parts !== [] ? implode(' / ', $subtitle_parts) : _('Select a host to bind switch health.')))->addClass('swx-subtitle'));
$header->addItem($title_wrap);

$chip_row = (new CDiv())->addClass('swx-chiprow');
$maintenance = !empty($summary['maintenance']);
$monitoring_enabled = !array_key_exists('monitoring_enabled', $summary) || !empty($summary['monitoring_enabled']);
$chip_row
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.($monitoring_enabled ? '#34D399' : '#94A3B8').';'))
            ->addItem($monitoring_enabled ? _('Monitoring on') : _('Monitoring off'))
    )
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.($maintenance ? '#F59E0B' : '#38BDF8').';'))
            ->addItem($maintenance ? _('Maintenance') : _('Live mode'))
    )
    ->addItem(
        (new CDiv())
            ->addClass('swx-chip')
            ->addItem((new CTag('span', true, ''))->addClass('swx-chip-dot')->setAttribute('style', '--dot-color: '.(!empty($summary['problem_ports']) ? '#FB7185' : '#34D399').';'))
            ->addItem(sprintf(_('Problem ports %d'), (int) ($summary['problem_ports'] ?? 0)))
    );
$header->addItem($chip_row);
$widget->addItem($header);

$metrics = (new CDiv())->addClass('swx-metrics');
$metric_cards = [
    [
        'label' => _('Configured'),
        'value' => sprintf('%d / %d', (int) ($summary['configured_ports'] ?? 0), (int) ($summary['total_ports'] ?? 0)),
        'meta' => _('ports bound to triggers')
    ],
    [
        'label' => _('Healthy'),
        'value' => (string) ((int) ($summary['healthy_ports'] ?? 0)),
        'meta' => _('ports currently in OK state')
    ],
    [
        'label' => _('Layout'),
        'value' => sprintf('%d x %d', (int) ($layout['row_count'] ?? 0), (int) ($layout['ports_per_row'] ?? 0)),
        'meta' => sprintf(_('SFP uplinks %d'), (int) ($layout['sfp_ports'] ?? 0))
    ],
    [
        'label' => _('Peak Util'),
        'value' => sprintf('%s%%', number_format((float) ($summary['peak_utilization'] ?? 0), 1)),
        'meta' => $show_overlay ? _('highest live port load') : _('telemetry overlay disabled')
    ],
    [
        'label' => _('Hot Ports'),
        'value' => (string) ((int) ($summary['hot_ports'] ?? 0)),
        'meta' => _('ports above 70% utilization')
    ],
    [
        'label' => _('Updated'),
        'value' => (string) ($summary['last_updated'] ?? date('H:i')),
        'meta' => $host_endpoint !== '' ? $host_endpoint : _('waiting for host bind')
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
    $widget->addItem((new CDiv(_('Select a host and map port triggers to render the switch front panel.')))->addClass('swx-empty'));

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
$panel_head
    ->addItem((new CDiv(_('Copper Matrix')))->addClass('swx-panel-title'))
    ->addItem((new CDiv($show_overlay
        ? sprintf(_('Telemetry on / %d ports'), (int) ($layout['total_ports'] ?? count($ports)))
        : sprintf(_('Telemetry off / %d ports'), (int) ($layout['total_ports'] ?? count($ports)))))->addClass('swx-panel-badge'));
$panel->addItem($panel_head);

$grid = (new CDiv())->addClass('swx-grid');
$make_port = static function(array $port) use ($show_overlay, $format_rate) {
    $state = _('Idle');
    if (!empty($port['has_trigger'])) {
        $state = !empty($port['is_problem']) ? _('Alert') : _('Online');
    }

    $tooltip_parts = [
        $port['name'],
        sprintf(_('Port code: %s'), (string) ($port['port_code'] ?? '')),
        sprintf(_('State: %s'), $state)
    ];
    if ($show_overlay && isset($port['utilization_percent']) && $port['utilization_percent'] !== null) {
        $tooltip_parts[] = sprintf(_('Utilization: %s%%'), number_format((float) $port['utilization_percent'], 1));
        $tooltip_parts[] = sprintf(_('In: %s'), $format_rate((float) ($port['traffic_in_bps'] ?? 0.0)));
        $tooltip_parts[] = sprintf(_('Out: %s'), $format_rate((float) ($port['traffic_out_bps'] ?? 0.0)));
    }
    if (!empty($port['trigger_name'])) {
        $tooltip_parts[] = sprintf(_('Trigger: %s'), (string) $port['trigger_name']);
    }
    elseif (!empty($port['triggerid'])) {
        $tooltip_parts[] = sprintf(_('Trigger ID: %s'), (string) $port['triggerid']);
    }
    else {
        $tooltip_parts[] = _('Trigger: not configured');
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
    $head
        ->addItem((new CDiv((string) ($port['port_code'] ?? '')))->addClass('swx-port-index'))
        ->addItem((new CDiv($state))->addClass('swx-port-state'));

    $socket = (new CDiv())->addClass('swx-port-socket');
    if ($show_overlay) {
        $utilization = isset($port['utilization_percent']) && $port['utilization_percent'] !== null
            ? max(0.0, min(100.0, (float) $port['utilization_percent']))
            : 0.0;
        $heat = (new CDiv())->addClass('swx-port-heat');
        $heat->addItem(
            (new CDiv())
                ->addClass('swx-port-heat-fill')
                ->setAttribute(
                    'style',
                    'width: '.number_format($utilization, 1, '.', '').'%; background: '.(string) ($port['utilization_color'] ?? '#64748B').';'
                )
        );
        $socket->addItem($heat);
    }

    $card
        ->addItem($head)
        ->addItem((new CDiv())->addClass('swx-port-led'))
        ->addItem($socket)
        ->addItem((new CDiv((string) ($port['name'] ?? '')))->addClass('swx-port-name'))
        ->addItem((new CDiv(!empty($port['trigger_name']) ? (string) $port['trigger_name'] : _('No trigger mapped')))->addClass('swx-port-meta'));

    if ($show_overlay) {
        $telemetry = (new CDiv())->addClass('swx-port-telemetry');
        $telemetry->addItem(
            (new CDiv(isset($port['utilization_percent']) && $port['utilization_percent'] !== null
                ? number_format((float) $port['utilization_percent'], 1).'% util'
                : 'n/a util'))->addClass('swx-port-util')
        );
        $traffic = (new CDiv())->addClass('swx-port-traffic');
        $traffic
            ->addItem(new CDiv('In '.$format_rate((float) ($port['traffic_in_bps'] ?? 0.0))))
            ->addItem(new CDiv('Out '.$format_rate((float) ($port['traffic_out_bps'] ?? 0.0))));
        $telemetry->addItem($traffic);
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
