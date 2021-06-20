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
        $this->api = $this->getView()->api();

        $arr_data = isset($_POST['itemSet']) & is_array($_POST['itemSet']) > 0 ? $_POST['itemSet'] : null;

        foreach ($arr_data as $key => $value) {
            if (is_array($value) && count($value) > 2) {
            // mise a jour x & y de id item
                $param = [
                    'geom:coordX'=>$value[1],
                    'geom:coordY'=>$value[2]
                ];
                $this->api->update('items', $value[0], $param, []
                    , ['isPartial'=>true, 'continueOnError' => true, 'collectionAction' => 'append']);
            }
        }

        $form_data['success'] = "Enregistrer les données avec succès";
        $form_data['error'] = "Une erreur s'est produite lors de l'enregistrement";
        return json_encode($form_data);
    }
}
