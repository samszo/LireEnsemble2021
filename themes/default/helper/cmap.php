<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class cmap extends AbstractHelper
{
    var $api;
    //pour éviter de faire plusieurs fois le traitement
    var $proLinks;
    //prendre default 'collection map' dans collection
    var $default_collection = 152;
    //vocabulary: Tibor & BnF
    var $default_vocabulary_id = 7;
    //default carte afficher
    var $default_carte;

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
        $this->proLinks = array();

        // creer new carte
        if (isset($_POST['name_carte'])) {
            $this->add_carte();
        }

        // prendre liste de cartes: "Collection"
        $query_items_set = [
            'item_set_id'=>$this->default_collection, // collection cmap
        ];
        $items_set = $this->api->search('items',$query_items_set,['limit'=>0])->getContent();
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

        $params = isset($_POST['cartes']) ? $_POST['cartes'] : $this->default_carte; // default carte fin

        // prendre donnee de carte: "Contenu"
        $query = [
            'id'=>$params,
        ];
        $items = $this->api->search('items',$query,['limit'=>0])->getContent();

        foreach ($items as $i) {
            $result_carte[] = $this->getCarteInfo($i);
        }

        $arr_entites = array();
        $arr_id_rs = array();
        foreach ($result_carte[0]['nodes'] as $entites) {
            $arr_entites[$entites['idResource']]['x'] = $entites['x'];
            $arr_entites[$entites['idResource']]['y'] = $entites['y'];
            $arr_entites[$entites['idResource']]['id_entite'] = $entites['id'];
            $arr_id_rs[] = $entites['idResource'];
        }

        $query_rs = [
            'id' => $arr_id_rs,
        ];
        $resource_templates = $this->api->search('resource_templates',$query_rs,['limit'=>0])->getContent();

        $tables = array();
        $links = array();
        foreach ($resource_templates as $key=>$rt) {
            //récupère les propriétés
            $pros = $rt->resourceTemplateProperties();
            $cols = array();
            foreach ($pros as $pro) {
                $p = $pro->property();
                $str_title = strlen($p->label()) > 17 ? substr($p->label(), 0, 17) . "..." : $p->label();
                $cols[] = [
                    'itemName'=>ucfirst($str_title)
                    ,'id'=>$p->id()
                    ,'links'=>$this->getPropertyLinks($p)                
                ];
            }
            $str_pro = strlen($rt->label()) > 17 ? substr($rt->label(), 0, 17) . "..." : $rt->label();
            if (!isset($arr_entites[$rt->id()]['x']) || !isset($arr_entites[$rt->id()]['y'])) {
                $tables[] = [
                    'tableName'=>ucfirst($str_pro),
                    'id'=>$rt->id(),
                    'id_entite'=>$arr_entites[$rt->id()]['id_entite'],
                    'cols'=>$cols
                ];
            } else {
                $tables[] = [
                    'tableName' => ucfirst($str_pro),
                    'id' => $rt->id(),
                    'x' => isset($arr_entites[$rt->id()]['x']) ? $arr_entites[$rt->id()]['x'] : 0,
                    'y' => isset($arr_entites[$rt->id()]['y']) ? $arr_entites[$rt->id()]['y'] : 0,
                    'id_entite' => $arr_entites[$rt->id()]['id_entite'],
                    'cols' => $cols
                ];
            }
        }

        $keyTemp = [];
        foreach ($tables as $key=>$t) {
            $keyTemp[$t['id']]['key'] = $key;
            $keyTemp[$t['id']]['name'] = $t['tableName'];
        }
        //consruction de la table des liens
        foreach ($tables as $key=>$t) {
            foreach ($t['cols'] as $i => $c) {
                foreach ($c['links'] as $l) {
                    $links[]=[
                        "source"=>isset($keyTemp[$t['id']]['key']) ? $keyTemp[$t['id']]['key'] : 0,
                        "target"=>isset($keyTemp[$l['id']]['key']) ? $keyTemp[$l['id']]['key'] : 0,
                        "relation"=>'line1',
                        "sourceIndex"=>$i+1,
                        "targetIndex"=>1,
                        "value"=>1,
                        "nb"=>$l['nb'],
                    ];
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
            'vocabulary_id' => $this->default_vocabulary_id, // collection cmap
        ];
        $data_classes = $this->api->search('resource_classes',$query_classes,['limit'=>0])->getContent();
        foreach ($data_classes as $classes) {
            $chk_classes[$classes->id()] = $classes->label();
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
        if(isset($this->proLinks[$p->id()]))
	    return $this->proLinks[$p->id()];
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

                    }
                }
            }
        }
        $this->proLinks[$p->id()]=$rt;

        return $rt;
      
    }

    function getCarteInfo($oItem){
        $title = $oItem->value('dcterms:title')->asHtml();
        $desc = $oItem->value('dcterms:description')->asHtml();
        $result = [
            'title'=>$title
            ,'desc'=>$desc
        ];
        $geos = $oItem->value('geom:geometry', ['all' => true]);
        foreach ($geos as $geo) {
            $result = $this->getGeoInfo($geo->valueResource(),$result);
        }

        return $result;
    }

    function getGeoInfo($oItem, $result){
        $rc = $oItem->displayResourceClassLabel() ;

        $id_res = 0;

        $resource_templates = $this->api->search('resource_templates')->getContent();
        foreach ($resource_templates as $key=>$rt) {
            $pros = $rt->resourceClass();

            if ($pros != null) {
                if ($pros->id() == $oItem->resourceClass()->id()) {
                    $id_res = $rt->id();
                }
            }
        }

        $result['nodes'][] = [
            'label'=>$oItem->value('dcterms:title')->asHtml()
            ,'id'=>$oItem->id()
            ,'idResource'=>$id_res
            ,'x'=>(float)$oItem->value('geom:coordX')->__toString()
            ,'y'=>(float)$oItem->value('geom:coordY')->__toString()
        ];

        return $result;
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
        $params['chk_class'] = [];
        foreach ($_POST['chk_class'] as $str_class) {
            $arr_class = explode(":", $str_class);
            $params['chk_class'][$arr_class[0]] = $arr_class[1];
        }

        $this->getView()->EntityRelationFactory($action, $params);
    }

}
