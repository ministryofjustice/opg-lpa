<?php
// @inheritdoc
//

namespace Application\View\Helper;

interface RendererInterface
{
    public function LoadTemplate(string $templateName);
}
