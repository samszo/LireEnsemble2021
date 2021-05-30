<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class cmap extends AbstractHelper
{
    /**
     * Get resosurce templates
     * http://omeka-s-cmap.local/api/resource_templates?pretty_print=1
     * http://omeka-s-cmap.local/api/properties?id=1&pretty_print=1
     * @param
     * @return array
     */

    public function __invoke()
    {
        $view = $this->getView();
        $resource_templates = $view->api()->search('resource_templates')->getContent();

        $arr = array();
        foreach ($resource_templates as $obj) {
            $arr[] = $obj->label();
            //print "<br> 123= ".$obj->resource_template_property();

/*
            if (count($obj->resource_template_property()) > 0) {
                foreach ($obj->resource_template_property() as $property) {
                    $pro = $view->api()->search('properties', ['id' => $property->property()->id()
                    ])->getContent();
                    $arr[$obj->label()][$property->property()->id())] = $pro->label();
                }
            }
*/
        }


        //return json_encode($resource_templates);
        return ($arr);
    }
}
