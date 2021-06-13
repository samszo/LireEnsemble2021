<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class ajaxPos extends AbstractHelper
{
    /**
     * Ajax
     * 
     * @param
     * @return array
     */

    public function __invoke()
    {
        //print'<pre>';print_r($_POST['itemSet']);print'</pre>';
        $arr_data = isset($_POST['itemSet']) & is_array($_POST['itemSet']) > 0 ? $_POST['itemSet'] : null;
        foreach ($arr_data as $key=>$value) {
            if (isset($value) && is_array($value)) {
                //print '<br>>>>...value = '.$value[0];
            }
        }
        $form_data['success'] = true;
        $form_data['posted'] = 'Data Was Posted Successfully';
        return json_encode($form_data);
    }
}
