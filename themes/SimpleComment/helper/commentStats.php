<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class commentStats extends AbstractHelper
{
    /**
     * Get stats for comments
     *
     * @param string    $nb        Nombre de comment
     * @return array
     */
    public function __invoke($nb=0)
    {
        $view = $this->getView();
        return $view->api()->search('comments',['limit' => $nb
        ])->getContent();

    }
}
