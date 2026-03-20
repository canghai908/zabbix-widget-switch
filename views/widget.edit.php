<?php declare(strict_types = 1);

$form = new CWidgetFormView($data);
$selected_hostid = getSelectedHostId($data['fields']);

$port_count = 0;
foreach (array_keys($data['fields']) as $field_name) {
    if (preg_match('/^port(\d+)_name$/', $field_name, $matches) === 1) {
        $port_count = max($port_count, (int) $matches[1]);
    }
}

$form->addField(new CWidgetFieldMultiSelectHostView($data['fields']['hostids']));
$form->addFieldsGroup(getMetadataFieldsGroupView($data['fields'], 'switch_brand', _('Brand'), $selected_hostid));
$form->addFieldsGroup(getMetadataFieldsGroupView($data['fields'], 'switch_model', _('Model'), $selected_hostid));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['switch_role']));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['row_count']));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['traffic_in_item_pattern']));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['traffic_out_item_pattern']));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['speed_item_pattern']));
$form->addField(new CWidgetFieldTextBoxView($data['fields']['status_item_pattern']));
$form->addField(new CWidgetFieldSelectView($data['fields']['visual_theme']));
$form->addField(new CWidgetFieldSelectView($data['fields']['card_language']));
$form->addField(new CWidgetFieldSelectView($data['fields']['port_card_label_mode']));
$form->addField(new CWidgetFieldSelectView($data['fields']['panel_scale']));

for ($i = 1; $i <= $port_count; $i++) {
    $fieldset = (new CWidgetFormFieldsetCollapsibleView(sprintf(_('Port %d'), $i)))
        ->addClass('switchpanel-port-fieldset')
        ->setAttribute('data-port-index', (string) $i);

    $fieldset
        ->addField(new CWidgetFieldTextBoxView($data['fields']['port'.$i.'_name']))
        ->addField(new CWidgetFieldTextBoxView($data['fields']['port'.$i.'_triggerid']))
        ->addField(new CWidgetFieldTextBoxView($data['fields']['port'.$i.'_default_color']))
        ->addField(new CWidgetFieldTextBoxView($data['fields']['port'.$i.'_ok_color']))
        ->addField(new CWidgetFieldTextBoxView($data['fields']['port'.$i.'_problem_color']));

    $form->addFieldset($fieldset);
}

$widget_edit_js = file_get_contents(__DIR__.'/../assets/js/widget.edit.js');
if ($widget_edit_js !== false) {
    $form->addJavaScript($widget_edit_js);
}
$form->addJavaScript('window.switch_panel_widget_form.init();');

$form->show();

function getMetadataFieldsGroupView(array $fields, string $prefix, string $label, int $selected_hostid): CWidgetFieldsGroupView {
    return (new CWidgetFieldsGroupView($label))
        ->addField(
            (new CWidgetFieldSelectView($fields[$prefix.'_source']))
                ->removeLabel()
                ->addClass('switchpanel-inline-control switchpanel-meta-source')
        )
        ->addField(
            (new CWidgetFieldTextBoxView($fields[$prefix]))
                ->removeLabel()
                ->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
                ->addClass('switchpanel-inline-control switchpanel-meta-manual')
        )
        ->addField(
            (new CWidgetFieldMultiSelectItemView($fields[$prefix.'_itemids']))
                ->removeLabel()
                ->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH)
                ->setPopupParameter('hostid', $selected_hostid)
                ->setPopupParameter('hide_host_filter', $selected_hostid > 0)
                ->addClass('switchpanel-inline-control switchpanel-meta-item')
        )
        ->addRowClass('switchpanel-meta-group');
}

function getSelectedHostId(array $fields): int {
    if (!array_key_exists('hostids', $fields) || !method_exists($fields['hostids'], 'getValue')) {
        return 0;
    }

    $value = $fields['hostids']->getValue();
    if (is_array($value)) {
        $first = reset($value);
        return $first !== false && ctype_digit((string) $first) ? (int) $first : 0;
    }

    return ctype_digit((string) $value) ? (int) $value : 0;
}
