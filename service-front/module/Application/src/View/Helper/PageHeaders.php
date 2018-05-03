<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageHeaders extends AbstractHelper
{
    public function __invoke()
    {
        $ourView = $this->view->viewModel()->getCurrent()->getChildren()[0];
            
        if ($ourView->pageTitle) {
            echo '<header class="page-header group"><hgroup>';

            echo '<h1>' . $ourView->pageTitle . '</h1>';
            
            echo '</hgroup></header>';

            ?>
            <div class="article-container group">
            <article role="article" class="group">
            <div class="inner">
            <?php
        }
    }
}
