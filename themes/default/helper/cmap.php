<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class cmap extends AbstractHelper
{
    var $api;
    //pour éviter de faire plusieurs fois le traitement
    var $proLinks;
    //prendre default 'Entiteé relation' dans collection
    var $default_collection = 152;
    //vocabulary: Tibor & BnF
    var $default_vocabulary_id = 7;
    //default carte afficher
    var $default_carte;
    var $arr_entites;
    var $arr_id_rs;
    var $resource_templates;
    var $keyTemp;

    /**
     * Get resosurce templates
     * http://omeka-s-cmap.local/api/resource_templates?pretty_print=1
     * http://omeka-s-cmap.local/api/properties?id=1&pretty_print=1
     * @param
     * @return array
     */

    public function __invoke($type)
    {
        $this->api = $this->getView()->api();

        $_SESSION['pro_value'] = [];

        // creer new carte
        if (isset($_POST['name_carte'])) {
            $this->add_carte();
        }

        // prendre liste de cartes: "Collection"
        $lst_item_set = $this->list_cartes();

        $params = isset($_POST['cartes']) ? $_POST['cartes'] : $this->default_carte; // default carte fin

        // prendre donnee de carte: "Contenu"
        $query = [
            'id' => $params,
        ];
        $items = $this->api->search('items', $query, ['limit' => 0])->getContent();

        $result_carte = array();
        $this->resource_templates = array();
        if (count($items) > 0) {
            foreach ($items as $i) {
                $result_carte[] = $this->getCarteInfo($i);
            }
        }

        $tables = array();
        $links = array();
        $this->keyTemp = array();

        if (count($result_carte) > 0) {
            foreach ($this->resource_templates as $key=>$rt) {
                //récupère les propriétés
                $pros = $rt->resourceTemplateProperties();
                $cols = array();

                foreach ($pros as $pro) {
                    $p = $pro->property();

                    // somme de pro
                    $arr_v_p = $this->getView()->EntityRelationFactory('getProItem', $p);

                    $str_title = strlen($p->label()) > 17 ? substr($p->label(), 0, 17) . "..." : $p->label();
                    $cols[] = [
                        'itemName' => ucfirst($str_title)
                        , 'id' => $p->id()
                        , 'id_rt' => $rt->id()
                        , 'links' => $this->getPropertyLinks($p)
                        , 'nbItemPro' => count($arr_v_p)
                    ];

                }
                $str_pro = strlen($rt->label()) > 17 ? substr($rt->label(), 0, 17) . "..." : $rt->label();
                $tables[] = [
                    'tableName' => ucfirst($str_pro),
                    'id' => $rt->id(),
                    'x' => isset($this->arr_entites[$rt->id()]['x']) ? $this->arr_entites[$rt->id()]['x'] : 0,
                    'y' => isset($this->arr_entites[$rt->id()]['y']) ? $this->arr_entites[$rt->id()]['y'] : 0,
                    'id_entite' => isset($this->arr_entites[$rt->id()]['id_entite']) ? $this->arr_entites[$rt->id()]['id_entite'] : 0,
                    'nbItem' => $rt->itemCount(),
                    'cols' => $cols,
                ];

                $this->keyTemp[$rt->id()]['key'] = $key;
            }

            //consruction de la table des liens
            foreach ($tables as $t) {
                foreach ($t['cols'] as $i => $c) {
                    foreach ($c['links'] as $l) {
                        $links[] = [
                            "source" => isset($this->keyTemp[$t['id']]['key']) ? $this->keyTemp[$t['id']]['key'] : 0,
                            "target" => isset($this->keyTemp[$l['id']]['key']) ? $this->keyTemp[$l['id']]['key'] : 0,
                            "relation" => 'line1',
                            "sourceIndex" => $i + 1,
                            "targetIndex" => 1,
                            "value" => 1,
                            "nb" => $l['nb'],
                        ];
                    }
                }
            }
        }

        return [
            'tables' => $tables,
            'links' => $links,
            'lst_item_set' => $lst_item_set,
            'sel_carte' => $params,
            'chk_classes' => $this->getListClasses(),
            ];
    }

    /**
     * Get classes de vocabulary 7 (Tibor & BnF)
     * http://omeka-s-cmap.local/api/resource_classes?pretty_print=1&vocabulary_id=7
     * @param
     * @return array
     */

    function getListClasses() {
        // prendre liste de class: "Vocabulary = 7"
        $query_classes = [
            'vocabulary_id' => $this->default_vocabulary_id, // Entite relation
        ];
        $data_classes = $this->api->search('resource_classes',$query_classes,['limit'=>0])->getContent();
        foreach ($data_classes as $key=>$classes) {
            $chk_classes[$key]['label'] = $classes->label();
            $chk_classes[$key]['term'] = $classes->term();
        }
        asort($chk_classes);
        return $chk_classes;
    }


    /**
     * Get link between property
     * @param Omeka\Api\Representation\PropertyRepresentation $p
     * @return array
     */

    function getPropertyLinks($p)
    {
        if(isset($this->proLinks[$p->id()])) {
            return $this->proLinks[$p->id()];
        }
        $rt = array();

        //recherche les ressources associés à cette propriété
        $param = array();
        $param['property'][0]['property']= $p->id()."";
        $param['property'][0]['type']='ex';
        $items = $this->api->search('items', $param)->getContent();
        foreach ($items as $item) {
            //récupère les valeurs qui sont des ressources
            $values = $item->value($p->term(),['all'=>true,'type'=>'resource']);

            foreach ($values as $v) {
                //récupère le ressource template associé à la ressource
                if($v->valueResource()->resourceTemplate()){
                    if(isset($rt[$v->valueResource()->resourceTemplate()->id()]))
                        $rt[$v->valueResource()->resourceTemplate()->id()]['nb'] ++;
                    else{
                        $rt[$v->valueResource()->resourceTemplate()->id()]=[
                            'id'=>$v->valueResource()->resourceTemplate()->id()
                            ,'label'=>$v->valueResource()->resourceTemplate()->label()
                            ,'nb'=>1
                        ];
                        $rt[$v->valueResource()->resourceTemplate()->id()]['nb'];
                    }
                }
            }
        }
        $this->proLinks[$p->id()]=$rt;

        return $rt;
    }

    function getCarteInfo($oItem){
        $title = $oItem->value('dcterms:title')->asHtml();
        $desc = $oItem->value('dcterms:description') !== null ? $oItem->value('dcterms:description')->asHtml() : '';
        $result = [
            'title'=>$title
            ,'desc'=>$desc
        ];
        $geos = $oItem->value('geom:geometry', ['all' => true]);
        foreach ($geos as $geo) {
            $this->getGeoInfo($geo->valueResource());
        }

        return $result;
    }

    function getGeoInfo($oItem){
        //$rc = $oItem->displayResourceClassLabel() ;
        $id_res = 0;

        $resource_templates = $this->api->search('resource_templates')->getContent();
        foreach ($resource_templates as $rt) {
            $pros = $rt->resourceClass();
            if ($pros != null) {
                if ($pros->term() == $oItem->value('schema:structuralClass')->asHtml()) {
                    $id_res = $rt->id();
                    $this->resource_templates[] = $rt;
                }
            }
        }

        $this->arr_entites[$id_res]['x'] = (float)$oItem->value('geom:coordX')->__toString();
        $this->arr_entites[$id_res]['y'] = (float)$oItem->value('geom:coordY')->__toString();
        $this->arr_entites[$id_res]['id_entite'] = $oItem->id();
        $this->arr_id_rs[] = $id_res;
    }

    /**
     * creer new carte
     * @param
     * @return array
     */

    function add_carte() {
        $params['name_carte'] = $_POST['name_carte'];
        $params['default_collection'] = $this->default_collection;
        $action = 'addPosition';
        $params['chk_class'] = array();
        $arr_pos_sel = explode(",", $_POST['pos_sel']);

        foreach ($_POST['chk_class'] as $key=>$label_class) {
            $params['chk_class'][$key]['label'] = $label_class;
            $params['chk_class'][$key]['term'] = $_POST['term_class'][$arr_pos_sel[$key]];
        }

        $this->getView()->EntityRelationFactory($action, $params);
    }

    /**
     * prendre liste de cartes
     * @param
     * @return array
     */

    function list_cartes() {
        $lst_item_set = [];
        $query_items_set = [
            'item_set_id'=>$this->default_collection, // Entite relation
        ];
        $items_set = $this->api->search('items',$query_items_set,['limit'=>0])->getContent();
        if (count($items_set) > 0) {
            foreach ($items_set as $i_s) {
                // couper nom si long
                $str_nom = $i_s->title();
                if (strlen($str_nom) > 30) {
                    $str_nom = substr($str_nom, 0, 30) . '...';
                }
                $lst_item_set[$i_s->id()] = $str_nom;
                $this->default_carte = $i_s->id();
            }
            asort($lst_item_set);
        }
        return $lst_item_set;
    }

}
