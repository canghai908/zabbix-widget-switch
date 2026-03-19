<?php declare(strict_types = 1);

namespace Modules\SwitchPanelWidget\Actions;

use API;
use CController;
use CControllerResponseData;

class WidgetTriggers extends CController {
    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return $this->validateInput([
            'hostid' => 'required|id'
        ]);
    }

    protected function checkPermissions(): bool {
        return $this->getUserType() >= USER_TYPE_ZABBIX_USER;
    }

    protected function doAction(): void {
        $hostid = (string) $this->getInput('hostid');

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

        $this->setResponse(new CControllerResponseData([
            'main_block' => json_encode([
                'triggers' => $result
            ])
        ]));
    }
}
