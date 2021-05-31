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
        $resource_templates = $view->api()->search('resource_templates',['limit' => 'all'
        ])->getContent();

        $data = json_encode($resource_templates);
        $json = json_decode($data, true);

        $arr_return = array();
        $arr_name_table = array();
        $i = 0;
        foreach ($json as $obj) {

            $arr_name_table[$i] = $obj['o:label'];

            if (count($obj['o:resource_template_property']) > 0) {
                $arr_pro_table['pro'.$i] = array();
                foreach ($obj['o:resource_template_property'] as $property) {
                    $pro = $view->api()->search('properties', ['id' => $property['o:property']['o:id']
                    ])->getContent();
                    $arr_pro_table['pro'.$i][] = ucfirst($pro[0]->label());
                }
            }
            $i++;
        }

        $arr_return = [
            'arr_name_table' => $arr_name_table,
            'arr_pro_table' => $arr_pro_table
        ];

        return ($arr_return);
    }
}
