<?php

namespace TN\TN_CMS\Component\LandingPage\Admin\EditLandingPage;

use TN\TN_Core\Component\Renderer\JSON\JSON;

class EditLandingPageProperties extends JSON {
    public function prepare(): void {
        $editLandingPage = new EditLandingPage();
        $editLandingPage->prepare();
        $editLandingPage->editProperties($_POST);
        $this->data = [
            'landingPageId' => $editLandingPage->landingPage->editId
        ];
    }
}