<?php

namespace TN\TN_CMS\Component\LandingPage\Admin\EditLandingPage;

use TN\TN_CMS\Component\TagEditor\TagEditor;
use TN\TN_CMS\Model\LandingPage;
use TN\TN_Core\Attribute\Components\FromQuery;
use TN\TN_Core\Attribute\Components\HTMLComponent\FullWidth;
use TN\TN_Core\Attribute\Components\HTMLComponent\RequiresTinyMCE;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Component\Title\Title;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Campaign\Campaign;
use \TN\TN_Core\Attribute\Components\Route;

#[Page('Edit Landing Page', '', 'Edit a landing page', false)]
#[Route('TN_CMS:LandingPage:adminEditLandingPage')]
#[RequiresTinyMCE]
#[FullWidth]
class EditLandingPage extends HTMLComponent
{
    public function getPageTitleComponent(array $options): ?Title
    {
        return null;
    }

    public ?LandingPage $landingPage;
    public TagEditor $tagEditor;
    public array $stateOptions = [];
    public array $campaignOptions = [];
    #[FromQuery] public ?int $landingpageid = null;
    public function prepare(): void
    {
        if ($this->landingpageid) {
            $this->landingPage = LandingPage::readFromId($this->landingpageid);
            if (!$this->landingPage) {
                throw new ResourceNotFoundException('landing page');
            }
        } else {
            $this->landingPage = LandingPage::getInstance();
            $this->landingPage->publishedTs = Time::getNow();
            $this->landingPage->numTags = 0;
        }

        $this->stateOptions[] = [
            'value' => LandingPage::STATE_DRAFT,
            'label' => 'Draft'
        ];
        $this->stateOptions[] = [
            'value' => LandingPage::STATE_PUBLISHED,
            'label' => 'Published'
        ];

        $this->campaignOptions = Campaign::readAll();
        $campaignKeys = [];
        foreach ($this->campaignOptions as $campaign) {
            $campaignKeys[] = $campaign->key;
        }
        array_multisort($campaignKeys, SORT_ASC, $this->campaignOptions);

        $this->tagEditor = new TagEditor($this->landingPage);
        $this->tagEditor->prepare();
    }

    public function editProperties(array $data): void
    {
        // data is in effect the post array.
        // add validations to each property and filter down the $data array
        $edits = [];

        foreach ($data as $property => $value) {
            switch ($property) {
                case 'tags':
                    // Tags are handled after content save once we have a real landing page ID.
                    break;
                case 'title':
                    $edits['title'] = $value;
                    break;
                case 'state':
                    $edits['state'] = $value;
                    break;
                case 'urlStub':
                    if ($this->landingPage->state !== LandingPage::STATE_PUBLISHED) {
                        $edits['urlStub'] = $value;
                    }
                    break;
                case 'convertKitTag':
                    $edits['convertKitTag'] = htmlentities($value);
                    break;
                case 'content':
                    $edits['content'] = $value;
                    break;
                case 'campaignId':
                    $edits['campaignId'] = (int)$value;
                    break;
                case 'allowRemovedNavigation':
                    $edits['allowRemovedNavigation'] = (bool)$value;
                    break;
                case 'thumbnailSrc':
                    $edits['thumbnailSrc'] = $value;
                    break;
                default:
                    break;
            }
        }

        if (!empty($edits)) {
            $this->landingPage->update($edits);
        }

        if (isset($data['tags'])) {
            // New landing pages may receive a tags-only autosave before first save.
            // Skip tag persistence until a real landing page ID exists.
            $landingPageId = isset($this->landingPage->id) ? (int)$this->landingPage->id : 0;
            if ($landingPageId <= 0) {
                return;
            }

            $tagData = is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags'];
            if (!is_array($tagData)) {
                $tagData = [];
            }

            $this->tagEditor->contentId = $landingPageId;
            $this->tagEditor->updateTags($tagData);
        }
    }
}
