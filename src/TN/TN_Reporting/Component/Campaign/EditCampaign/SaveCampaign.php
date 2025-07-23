<?php

namespace TN\TN_Reporting\Component\Campaign\EditCampaign;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Campaign\Campaign;

class SaveCampaign extends JSON
{
    #[FromPost] public ?int $id = null;
    public ?Campaign $campaign;
    #[FromPost] public string $useBaseUrl = '';

    public function prepare(): void
    {
        $this->campaign = $this->id ? Campaign::readFromId($this->id) : Campaign::getInstance();
        $useBaseUrl = ($_POST['useBaseUrl'] ?? false) === '1';

        try {
            $this->campaign->update([
                'key' => strtolower((string)($_POST['key'] ?? '')),
                'funnelKey' => (string)($_POST['funnelKey'] ?? ''),
                'funnelEntryStage' => (int)($_POST['funnelEntryStage'] ?? 0),
                'voucherCodeId' => (int)($_POST['voucherCodeId'] ?? 0),
                'notes' => (string)($_POST['notes'] ?? ''),
                'useBaseUrl' => $useBaseUrl
            ]);
            $this->data = [
                'result' => 'success',
                'siteMessageId' => $this->campaign->id,
                'url' => $this->campaign->getUrl()
            ];
        } catch (ValidationException $e) {
            $this->data = [
                'result' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
}
