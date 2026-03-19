(function() {
    const SELECTOR_INTERVAL = 1200;
    let currentHostId = '';
    let currentDiscoveryKey = '';
    let currentTriggers = [];
    let currentPorts = [];
    let currentLayout = null;
    let currentRecommendedItems = {};

    function findField(name) {
        return document.querySelector(`[name="fields[${name}]"]`)
            || document.querySelector(`[name="fields[${name}][]"]`)
            || document.querySelector(`[name="${name}"]`)
            || document.getElementById(`${name}_ms`)
            || document.getElementById(name);
    }

    function closestFieldRow(field) {
        if (!field) {
            return null;
        }

        return field.closest('.form_row')
            || field.closest('.fields-group')
            || field.closest('li')
            || field.closest('tr')
            || field.parentElement;
    }

    function ensureInlineMetadataStyles() {
        if (document.getElementById('switchpanel-inline-metadata-style')) {
            return;
        }

        const style = document.createElement('style');
        style.id = 'switchpanel-inline-metadata-style';
        style.textContent = `
            .switchpanel-meta-group.fields-group {
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            .switchpanel-meta-group .switchpanel-meta-source {
                flex: 0 0 132px;
                min-width: 132px;
            }
            .switchpanel-meta-group .switchpanel-meta-manual,
            .switchpanel-meta-group .switchpanel-meta-item {
                flex: 1 1 270px;
                min-width: 270px;
            }
            .switchpanel-meta-group .switchpanel-meta-source select,
            .switchpanel-meta-group .switchpanel-meta-manual input[type="text"],
            .switchpanel-meta-group .switchpanel-meta-item .multiselect-control {
                max-width: 100%;
            }
        `;
        document.head.appendChild(style);
    }

    function getFieldContainer(fieldName) {
        const field = findField(fieldName);
        if (!field) {
            return null;
        }

        return field.closest('.switchpanel-inline-control')
            || field.closest('.form-field')
            || closestFieldRow(field);
    }

    function setFieldVisible(fieldName, visible) {
        const container = getFieldContainer(fieldName);
        if (container) {
            container.style.display = visible ? '' : 'none';
        }
    }

    function setRowVisible(fieldName, visible) {
        const row = closestFieldRow(findField(fieldName));
        if (row) {
            row.style.display = visible ? '' : 'none';
        }
    }

    function readInt(name, fallback) {
        const field = findField(name);
        const value = field ? String(field.value || '').trim() : '';
        return /^\d+$/.test(value) ? parseInt(value, 10) : fallback;
    }

    function extractHostId(value) {
        const text = String(value || '').trim();
        if (/^\d+$/.test(text)) {
            return text;
        }
        const idMatch = text.match(/"id"\s*:\s*"(\d+)"/);
        if (idMatch) {
            return idMatch[1];
        }
        const arrayMatch = text.match(/\[\s*"(\d+)"\s*\]/);
        if (arrayMatch) {
            return arrayMatch[1];
        }
        return '';
    }

    function getHostId() {
        const selectors = [
            'input[name="fields[hostids][]"]',
            'input[name^="fields[hostids]["]',
            'input[name="hostids[]"]',
            '[id^="hostids"] input[type="hidden"]',
            '[data-name="hostids"] input[type="hidden"]'
        ];

        for (const selector of selectors) {
            for (const input of document.querySelectorAll(selector)) {
                const hostId = extractHostId(input.value);
                if (hostId !== '') {
                    return hostId;
                }
            }
        }

        const token = document.querySelector('#hostids_ms [data-id], [data-name="hostids"] [data-id]');
        return token && token.dataset && token.dataset.id ? extractHostId(token.dataset.id) : '';
    }

    function getPortTotal() {
        if (currentPorts.length > 0) {
            return currentPorts.length;
        }

        if (currentLayout && Number.isInteger(currentLayout.total_ports) && currentLayout.total_ports > 0) {
            return currentLayout.total_ports;
        }

        const rows = Math.max(1, readInt('row_count', 2));
        const perRow = Math.max(1, readInt('ports_per_row', 12));
        return Math.min(96, rows * perRow);
    }

    function getPortFieldsets() {
        return Array.from(document.querySelectorAll('.switchpanel-port-fieldset'));
    }

    function applyPortVisibility() {
        const total = getPortTotal();
        const discoveredSfp = currentPorts.filter((port) => !!port.is_sfp).length;
        const sfpCount = discoveredSfp > 0
            ? discoveredSfp
            : currentLayout && Number.isInteger(currentLayout.sfp_ports)
                ? currentLayout.sfp_ports
                : 0;
        const sfpStart = total - sfpCount + 1;

        for (const fieldset of getPortFieldsets()) {
            const index = parseInt(fieldset.dataset.portIndex || '0', 10);
            const visible = index > 0 && index <= total;
            fieldset.style.display = visible ? '' : 'none';

            const nameField = findField(`port${index}_name`);
            if (nameField) {
                const portInfo = currentPorts[index - 1] || null;
                if (portInfo && portInfo.name) {
                    nameField.placeholder = portInfo.name;
                }
                else {
                    const isSfp = index >= sfpStart;
                    nameField.placeholder = isSfp
                        ? `SFP ${String(index - sfpStart + 1).padStart(2, '0')}`
                        : `GE ${String(index).padStart(2, '0')}`;
                }
            }
        }
    }

    function getTriggerInputs() {
        const matches = [];
        for (const input of document.querySelectorAll('input')) {
            const token = `${input.name || ''} ${input.id || ''}`;
            if (/port\d+_triggerid/.test(token)) {
                matches.push(input);
            }
        }
        return matches;
    }

    function setSelectOptions(select, triggers, hostId, selectedValue) {
        const selected = String(selectedValue || '');
        const options = [{value: '', label: hostId === '' ? 'Select host first' : 'Select trigger'}];
        for (const trigger of triggers) {
            options.push({value: String(trigger.id), label: String(trigger.name)});
        }

        select.innerHTML = '';
        for (const option of options) {
            const node = document.createElement('option');
            node.value = option.value;
            node.textContent = option.label;
            select.appendChild(node);
        }

        select.value = options.some((option) => option.value === selected) ? selected : '';
    }

    function ensureTriggerSelect(input) {
        if (input.dataset.switchpanelSelectBound === '1') {
            return input._switchpanelSelect;
        }

        input.type = 'hidden';
        const select = document.createElement('select');
        select.className = 'z-select';
        select.style.maxWidth = '100%';
        select.style.width = '100%';
        select.dataset.initialValue = String(input.value || '');
        input.insertAdjacentElement('afterend', select);

        select.addEventListener('change', () => {
            input.value = select.value;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        });

        input.dataset.switchpanelSelectBound = '1';
        input._switchpanelSelect = select;
        return select;
    }

    function applyTriggerOptions() {
        for (const input of getTriggerInputs()) {
            const select = ensureTriggerSelect(input);
            const selectedValue = String(input.value || select.dataset.initialValue || '');
            setSelectOptions(select, currentTriggers, currentHostId, selectedValue);
        }
    }

    function normalizeColor(value, fallback) {
        const text = String(value || '').trim();
        if (/^#[0-9a-fA-F]{6}$/.test(text)) {
            return text.toUpperCase();
        }
        if (/^[0-9a-fA-F]{6}$/.test(text)) {
            return `#${text.toUpperCase()}`;
        }
        return fallback;
    }

    function applyColorInputs() {
        for (const input of document.querySelectorAll('input')) {
            const token = `${input.name || ''} ${input.id || ''}`;
            if (!/port\d+_(default_color|ok_color|problem_color)/.test(token)) {
                continue;
            }

            const fallback = token.includes('_ok_color')
                ? '#34D399'
                : token.includes('_problem_color')
                    ? '#FB7185'
                    : '#64748B';

            input.type = 'color';
            input.value = normalizeColor(input.value, fallback);
            input.style.width = '64px';
            input.style.padding = '2px';
        }
    }

    function parseTriggerPayload(text) {
        const payload = JSON.parse(text);
        if (Array.isArray(payload.triggers)) {
            return {
                triggers: payload.triggers,
                ports: Array.isArray(payload.ports) ? payload.ports : [],
                layout: payload.layout || null,
                recommended_items: payload.recommended_items || {}
            };
        }
        if (payload.main_block) {
            const nested = JSON.parse(payload.main_block);
            if (Array.isArray(nested.triggers)) {
                return {
                    triggers: nested.triggers,
                    ports: Array.isArray(nested.ports) ? nested.ports : [],
                    layout: nested.layout || null,
                    recommended_items: nested.recommended_items || {}
                };
            }
        }
        return {triggers: [], ports: [], layout: null, recommended_items: {}};
    }

    function readPattern(name) {
        const field = findField(name);
        return field ? String(field.value || '').trim() : '';
    }

    function readSelectInt(name, fallback) {
        const field = findField(name);
        const value = field ? String(field.value || '').trim() : '';
        return /^\d+$/.test(value) ? parseInt(value, 10) : fallback;
    }

    function syncTextSource(prefix) {
        const source = readSelectInt(`${prefix}_source`, 0);
        const useItem = source === 1;
        setFieldVisible(prefix, !useItem);
        setFieldVisible(`${prefix}_itemids`, useItem);
    }

    function syncMetadataSourceFields() {
        syncTextSource('switch_brand');
        syncTextSource('switch_model');
        syncTextSource('switch_role');
    }

    function updateItemPopupHost(prefix, hostId) {
        const multiselect = document.getElementById(`${prefix}_itemids_ms`);
        if (!multiselect || typeof jQuery === 'undefined') {
            return;
        }

        const widget = jQuery(multiselect).data('multiSelect');
        if (!widget || !widget.options || !widget.options.popup || !widget.options.popup.parameters) {
            return;
        }

        widget.options.popup.parameters.hostid = hostId || 0;
        widget.options.popup.parameters.hide_host_filter = hostId !== '' ? 1 : 0;
    }

    function getMultiSelectData(fieldName) {
        const multiselect = document.getElementById(`${fieldName}_ms`);
        if (!multiselect || typeof jQuery === 'undefined') {
            return [];
        }

        const $multiselect = jQuery(multiselect);
        return typeof $multiselect.multiSelect === 'function'
            ? $multiselect.multiSelect('getData')
            : [];
    }

    function setRecommendedItem(fieldName, item) {
        if (!item || !item.id || typeof jQuery === 'undefined') {
            return;
        }

        const multiselect = document.getElementById(`${fieldName}_ms`);
        if (!multiselect) {
            return;
        }

        const $multiselect = jQuery(multiselect);
        if (typeof $multiselect.multiSelect !== 'function') {
            return;
        }

        const existing = $multiselect.multiSelect('getData');
        if (Array.isArray(existing) && existing.length > 0) {
            return;
        }

        $multiselect.multiSelect('addData', [{
            id: String(item.id),
            name: String(item.name || item.id)
        }]);
    }

    function applyRecommendedMetadataItems() {
        const mapping = [
            ['switch_brand', 'switch_brand_itemids'],
            ['switch_model', 'switch_model_itemids'],
            ['switch_role', 'switch_role_itemids']
        ];

        for (const [prefix, fieldName] of mapping) {
            if (readSelectInt(`${prefix}_source`, 0) !== 1) {
                continue;
            }

            if (getMultiSelectData(fieldName).length > 0) {
                continue;
            }

            setRecommendedItem(fieldName, currentRecommendedItems[fieldName] || null);
        }
    }

    function getDiscoveryKey(hostId) {
        return [
            hostId,
            readInt('row_count', 2),
            readPattern('traffic_in_item_pattern'),
            readPattern('traffic_out_item_pattern'),
            readPattern('speed_item_pattern'),
            readPattern('status_item_pattern')
        ].join('|');
    }

    function writeValue(name, value) {
        const field = findField(name);
        if (!field) {
            return;
        }

        field.value = String(value);
        field.dispatchEvent(new Event('change', {bubbles: true}));
    }

    function applyDiscoveredTriggerDefaults() {
        for (const input of getTriggerInputs()) {
            const match = `${input.name || ''} ${input.id || ''}`.match(/port(\d+)_triggerid/);
            if (!match) {
                continue;
            }

            const portIndex = parseInt(match[1], 10);
            const portInfo = currentPorts[portIndex - 1] || null;
            input.value = portInfo && portInfo.default_triggerid
                ? String(portInfo.default_triggerid)
                : '';

            if (input._switchpanelSelect) {
                input._switchpanelSelect.dataset.initialValue = input.value;
            }
        }
    }

    function fetchTriggers(hostId) {
        const url = new URL('zabbix.php', window.location.origin);
        url.searchParams.set('action', 'widget.switchpanel.triggers');
        url.searchParams.set('output', 'ajax');
        url.searchParams.set('hostid', hostId);
        url.searchParams.set('row_count', readInt('row_count', 2));
        url.searchParams.set('traffic_in_item_pattern', readPattern('traffic_in_item_pattern'));
        url.searchParams.set('traffic_out_item_pattern', readPattern('traffic_out_item_pattern'));
        url.searchParams.set('speed_item_pattern', readPattern('speed_item_pattern'));
        url.searchParams.set('status_item_pattern', readPattern('status_item_pattern'));

        return fetch(url.toString(), {
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then((response) => response.text())
            .then((text) => {
                try {
                    return parseTriggerPayload(text);
                }
                catch (error) {
                    const embedded = text.match(/\{"triggers":.*\}/);
                    if (embedded) {
                        return parseTriggerPayload(embedded[0]);
                    }
                    return {triggers: []};
                }
            });
    }

    function refresh() {
        setRowVisible('ports_per_row', false);
        applyPortVisibility();
        applyColorInputs();

        const hostId = getHostId();
        const discoveryKey = hostId === '' ? '' : getDiscoveryKey(hostId);
        ensureInlineMetadataStyles();
        syncMetadataSourceFields();
        updateItemPopupHost('switch_brand', hostId);
        updateItemPopupHost('switch_model', hostId);
        updateItemPopupHost('switch_role', hostId);

        if (hostId === currentHostId && discoveryKey === currentDiscoveryKey) {
            applyTriggerOptions();
            return;
        }

        currentHostId = hostId;
        currentDiscoveryKey = discoveryKey;
        if (hostId === '') {
            currentTriggers = [];
            currentPorts = [];
            currentLayout = null;
            currentRecommendedItems = {};
            applyTriggerOptions();
            return;
        }

        fetchTriggers(hostId)
            .then((payload) => {
                currentTriggers = Array.isArray(payload.triggers) ? payload.triggers : [];
                currentPorts = Array.isArray(payload.ports) ? payload.ports : [];
                currentLayout = payload.layout || null;
                if (currentLayout && Number.isInteger(currentLayout.ports_per_row)) {
                    writeValue('ports_per_row', currentLayout.ports_per_row);
                }
                currentRecommendedItems = payload.recommended_items || {};
                applyRecommendedMetadataItems();
                applyDiscoveredTriggerDefaults();
                applyPortVisibility();
                applyTriggerOptions();
            })
            .catch(() => {
                currentTriggers = [];
                currentPorts = [];
                currentLayout = null;
                currentRecommendedItems = {};
                applyTriggerOptions();
            });
    }

    window.switch_panel_widget_form = {
        init() {
            refresh();
            if (window.switch_panel_widget_form._timer) {
                clearInterval(window.switch_panel_widget_form._timer);
            }
            window.switch_panel_widget_form._timer = setInterval(refresh, SELECTOR_INTERVAL);
        }
    };
})();
