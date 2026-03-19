(function() {
    const SELECTOR_INTERVAL = 1200;
    let currentHostId = '';
    let currentTriggers = [];

    function findField(name) {
        return document.querySelector(`[name="fields[${name}]"]`)
            || document.querySelector(`[name="${name}"]`)
            || document.getElementById(name);
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
        const rows = Math.max(1, readInt('row_count', 2));
        const perRow = Math.max(1, readInt('ports_per_row', 12));
        const sfp = Math.max(0, readInt('sfp_ports', 4));
        return Math.min(96, rows * perRow + sfp);
    }

    function getPortFieldsets() {
        return Array.from(document.querySelectorAll('.switchpanel-port-fieldset'));
    }

    function applyPortVisibility() {
        const total = getPortTotal();
        const sfpStart = total - Math.max(0, readInt('sfp_ports', 4)) + 1;

        for (const fieldset of getPortFieldsets()) {
            const index = parseInt(fieldset.dataset.portIndex || '0', 10);
            const visible = index > 0 && index <= total;
            fieldset.style.display = visible ? '' : 'none';

            const nameField = findField(`port${index}_name`);
            if (nameField) {
                const isSfp = index >= sfpStart;
                nameField.placeholder = isSfp
                    ? `SFP ${String(index - sfpStart + 1).padStart(2, '0')}`
                    : `GE ${String(index).padStart(2, '0')}`;
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
            return payload;
        }
        if (payload.main_block) {
            const nested = JSON.parse(payload.main_block);
            if (Array.isArray(nested.triggers)) {
                return nested;
            }
        }
        return {triggers: []};
    }

    function fetchTriggers(hostId) {
        const url = new URL('zabbix.php', window.location.origin);
        url.searchParams.set('action', 'widget.switchpanel.triggers');
        url.searchParams.set('output', 'ajax');
        url.searchParams.set('hostid', hostId);

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
        applyPortVisibility();
        applyColorInputs();

        const hostId = getHostId();
        if (hostId === currentHostId) {
            applyTriggerOptions();
            return;
        }

        currentHostId = hostId;
        if (hostId === '') {
            currentTriggers = [];
            applyTriggerOptions();
            return;
        }

        fetchTriggers(hostId)
            .then((payload) => {
                currentTriggers = Array.isArray(payload.triggers) ? payload.triggers : [];
                applyTriggerOptions();
            })
            .catch(() => {
                currentTriggers = [];
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
