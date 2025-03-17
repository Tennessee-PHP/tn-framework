<?php

namespace TN\TN_Core\Component\StyleGuide;

use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;

#[Page('Style Guide', 'This is the style guide for the site', false)]
#[Route('TN_Core:Components:styleGuide')]
class StyleGuide extends HTMLComponent
{
}