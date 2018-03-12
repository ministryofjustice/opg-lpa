<?php

namespace ZfcTwig\View;

use Zend\Mvc\Plugin\FlashMessenger;
use Zend\ServiceManager\Factory\InvokableFactory;
use Zend\View\Helper;
use Zend\View\HelperPluginManager as ZendHelperPluginManager;

class HelperPluginManager extends ZendHelperPluginManager
{
    /**
     * Default aliases
     *
     * @var string[]
     */
    protected $aliases = [
        'flashmessenger' => FlashMessenger\FlashMessenger::class,
        'declarevars' => Helper\DeclareVars::class,
        'htmlflash' => Helper\HtmlFlash::class,
        'htmllist' => Helper\HtmlList::class,
        'htmlobject' => Helper\HtmlObject::class,
        'htmlpage' => Helper\HtmlPage::class,
        'htmlquicktime' => Helper\HtmlQuicktime::class,
        'layout' => Helper\Layout::class,
        'renderchildmodel' => Helper\RenderChildModel::class,
    ];

    /**
     * Default factories
     *
     * @var string[]
     */
    protected $factories = [
        FlashMessenger\FlashMessenger::class => InvokableFactory::class,
        Helper\DeclareVars::class => InvokableFactory::class,
        Helper\HtmlFlash::class => InvokableFactory::class,
        Helper\HtmlList::class => InvokableFactory::class,
        Helper\HtmlObject::class => InvokableFactory::class,
        Helper\HtmlPage::class => InvokableFactory::class,
        Helper\HtmlQuicktime::class => InvokableFactory::class,
        Helper\Layout::class => InvokableFactory::class,
        Helper\RenderChildModel::class => InvokableFactory::class,
    ];
}
