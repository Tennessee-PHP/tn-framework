<?php

namespace TN\TN_Advert\Component\Admin\SaveAdvert;

use LitEmoji\LitEmoji;
use TN\TN_Advert\Model\Advert;
use TN\TN_Advert\Model\AdvertSpot;
use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Request\Request;

class SaveAdvert extends JSON
{
    public string|int $id;
    public ?Advert $advertModel;
    #[FromPost] public string $advert;
    #[FromPost] public string $title;
    #[FromPost] public int $weight;
    #[FromPost] public int $displayFrequency;
    #[FromPost] public string $startTs;
    #[FromPost] public string $endTs;
    #[FromPost] public string $audience;
    #[FromPost] public bool $enabled;

    /**
     * @throws \TN\TN_Core\Error\ValidationException
     */
    public function prepare(): void
    {
        if ($this->id === 'new') {
            $this->advertModel = Advert::getInstance();
        } else {
            $this->advertModel = Advert::readFromId((int)$this->id);
            if (!($this->advertModel instanceof Advert)) {
                throw new \TN\TN_Core\Error\ValidationException('Advert not found');
            }
        }

        $this->advert = preg_replace('/&amp;#([A-Za-z0-9]+);/i', '&#$1;', $this->advert);
        $this->advert = LitEmoji::encodeHtml($this->advert);
        $this->advert = strip_tags($this->advert, '<div><br><a><img><b><p><h1><h2><h3><h4><h5><h6><i><em><hr><span><script><ins>');

        $update = [
            'title' => $this->title,
            'advert' => $this->advert,
            'weight' => $this->weight ?? 1,
            'displayFrequency' => $this->displayFrequency ?? 1,
            'startTs' => strtotime($this->startTs),
            'endTs' => strtotime($this->endTs),
            'audience' => $this->audience,
            'enabled' => $this->enabled
        ];

        // build locations
        $request = HTTPRequest::get();
        foreach (Advert::getAdvertSpotOptions() as $locationKey => $location) {
            $update[$locationKey] = (bool)$request->getPost($locationKey);
        }

        $this->advertModel->update($update);

        $advertInstances = AdvertSpot::getInstances();
        $advertSpotKeys = [];
        foreach ($advertInstances as $instance) {
            $spotKey = $instance->key;
            if ((int)$request->getPost($spotKey) === 1) {
                $advertSpotKeys[] = $spotKey;
            }
        }

        $this->advertModel->setAdvertPlacements($advertSpotKeys);

        $this->data = [
            'result' => 'success',
            'advertId' => $this->advertModel->id,
            'message' => 'The advert was saved successfully'
        ];
    }
}