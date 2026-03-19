<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Includes;

use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectItem;
use Zabbix\Widgets\Fields\CWidgetFieldSelect;
use Zabbix\Widgets\Fields\CWidgetFieldTextBox;

class WidgetForm extends CWidgetForm {
    private const SOURCE_MANUAL = 0;
    private const SOURCE_ITEM = 1;
    private const CARD_LANGUAGE_AUTO = 0;
    private const CARD_LANGUAGE_ZH_CN = 1;
    private const CARD_LANGUAGE_EN_US = 2;
    private const THEME_FOLLOW_ZABBIX = 0;
    private const THEME_LIGHT = 1;
    private const THEME_DARK = 2;
    private const DEFAULT_ROW_COUNT = 2;
    private const DEFAULT_PORTS_PER_ROW = 12;
    private const DEFAULT_TRAFFIC_IN_PATTERN = 'net.if.in[*]';
    private const DEFAULT_TRAFFIC_OUT_PATTERN = 'net.if.out[*]';
    private const DEFAULT_SPEED_PATTERN = 'net.if.speed[*]';
    private const DEFAULT_STATUS_PATTERN = 'net.if.status[*]';
    private const DEFAULT_PORT_INDEX_START = 1;
    private const MAX_ROW_COUNT = 6;
    private const MAX_PORTS_PER_ROW = 24;
    private const MAX_TOTAL_PORTS = 96;

    public function addFields(): self {
        $this->addField(
            (new CWidgetFieldMultiSelectHost('hostids', _('Host')))
                ->setMultiple(false)
        );

        $this->addField(
            (new CWidgetFieldSelect('switch_brand_source', _('Brand source'), [
                self::SOURCE_MANUAL => _('Manual text'),
                self::SOURCE_ITEM => _('Item value')
            ]))->setDefault(self::SOURCE_MANUAL)
        );
        $this->addField(
            (new CWidgetFieldTextBox('switch_brand', _('Brand')))
                ->setDefault('EDGECORE')
        );
        $this->addField(
            (new CWidgetFieldMultiSelectItem('switch_brand_itemids', _('Brand item')))
                ->setMultiple(false)
        );
        $this->addField(
            (new CWidgetFieldSelect('switch_model_source', _('Model source'), [
                self::SOURCE_MANUAL => _('Manual text'),
                self::SOURCE_ITEM => _('Item value')
            ]))->setDefault(self::SOURCE_MANUAL)
        );
        $this->addField(
            (new CWidgetFieldTextBox('switch_model', _('Model')))
                ->setDefault('S5850-48T4Q')
        );
        $this->addField(
            (new CWidgetFieldMultiSelectItem('switch_model_itemids', _('Model item')))
                ->setMultiple(false)
        );
        $this->addField(
            (new CWidgetFieldTextBox('switch_role', _('Role label')))
                ->setDefault('Campus Aggregation')
        );
        $this->addField(
            (new CWidgetFieldSelect('switch_role_source', _('Role source'), [
                self::SOURCE_MANUAL => _('Manual text'),
                self::SOURCE_ITEM => _('Item value')
            ]))->setDefault(self::SOURCE_MANUAL)
        );
        $this->addField(
            (new CWidgetFieldMultiSelectItem('switch_role_itemids', _('Role item')))
                ->setMultiple(false)
        );

        $this->addField(
            (new CWidgetFieldTextBox('row_count', _('Rows')))
                ->setDefault((string) self::DEFAULT_ROW_COUNT)
        );
        $this->addField(
            (new CWidgetFieldTextBox('traffic_in_item_pattern', _('Traffic in item pattern')))
                ->setDefault(self::DEFAULT_TRAFFIC_IN_PATTERN)
        );
        $this->addField(
            (new CWidgetFieldTextBox('traffic_out_item_pattern', _('Traffic out item pattern')))
                ->setDefault(self::DEFAULT_TRAFFIC_OUT_PATTERN)
        );
        $this->addField(
            (new CWidgetFieldTextBox('speed_item_pattern', _('Speed item pattern')))
                ->setDefault(self::DEFAULT_SPEED_PATTERN)
        );
        $this->addField(
            (new CWidgetFieldTextBox('status_item_pattern', _('Status item pattern')))
                ->setDefault(self::DEFAULT_STATUS_PATTERN)
        );
        $this->addField(
            (new CWidgetFieldTextBox('port_index_start', _('Port index start')))
                ->setDefault((string) self::DEFAULT_PORT_INDEX_START)
        );

        $this->addField(
            (new CWidgetFieldSelect('visual_theme', _('Theme'), [
                self::THEME_FOLLOW_ZABBIX => _('Follow Zabbix'),
                self::THEME_LIGHT => _('Light'),
                self::THEME_DARK => _('Dark')
            ]))->setDefault(self::THEME_FOLLOW_ZABBIX)
        );
        $this->addField(
            (new CWidgetFieldSelect('card_language', _('Card language'), [
                self::CARD_LANGUAGE_AUTO => _('Follow Zabbix'),
                self::CARD_LANGUAGE_ZH_CN => _('Chinese'),
                self::CARD_LANGUAGE_EN_US => _('English')
            ]))->setDefault(self::CARD_LANGUAGE_AUTO)
        );
        $this->addField(
            (new CWidgetFieldSelect('panel_scale', _('Panel size'), [
                100 => _('Large'),
                92 => _('Regular'),
                84 => _('Compact')
            ]))->setDefault(92)
        );

        for ($i = 1; $i <= self::MAX_TOTAL_PORTS; $i++) {
            $this->addField(
                (new CWidgetFieldTextBox('port'.$i.'_name', sprintf(_('Port %d label'), $i)))
                    ->setDefault('')
            );
            $this->addField(
                (new CWidgetFieldTextBox('port'.$i.'_triggerid', sprintf(_('Port %d trigger'), $i)))
                    ->setDefault('')
            );
            $this->addField(
                (new CWidgetFieldTextBox('port'.$i.'_default_color', sprintf(_('Port %d idle color'), $i)))
                    ->setDefault('#64748B')
            );
            $this->addField(
                (new CWidgetFieldTextBox('port'.$i.'_ok_color', sprintf(_('Port %d ok color'), $i)))
                    ->setDefault('#34D399')
            );
            $this->addField(
                (new CWidgetFieldTextBox('port'.$i.'_problem_color', sprintf(_('Port %d problem color'), $i)))
                    ->setDefault('#FB7185')
            );
        }

        return $this;
    }

    public static function normalizeLayoutValue(int $value, int $min, int $max): int {
        return max($min, min($max, $value));
    }
}
