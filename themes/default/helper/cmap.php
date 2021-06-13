<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class cmap extends AbstractHelper
{
    var $api;
    //pour éviter de faire plusieurs fois le traitement
    var $proLinks;

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

        //récupère les position de tables
        // donnees statique
        $pos_tables = array();
        $pos_tables[2] = [-742.8473310025732, -440.32583144916237];
        $pos_tables[3] = [-225.95295047790114, -261.6651130637969];
        $pos_tables[5] = [-226.924073097036, 250.90025991447555];
        $pos_tables[6] = [9.717141624595797, -86.74840754174306];
        $pos_tables[9] = [10.943338065105536, -441.441942222486];
        $pos_tables[11] = [10.517273774070873, 207.69672687153417];
        $pos_tables[12] = [-480.71836904759607, -267.20274842740326];
        $pos_tables[13] = [-480.8270277220481, -441.37898145724506];
        $pos_tables[14] = [-479.97858262889633, -89.37682267024684];
        $pos_tables[15] = [-480.1404756608856, 190.02576281364605];
        $pos_tables[16] = [-229.81651328637383, 464.56802904937774];
        $pos_tables[17] = [-224.7882680573309, -441.92302572062897];
	    $pos_tables[4] = [];
	    $pos_tables[7] = [];
	    $pos_tables[10] = [];
        $pos_tables[18] = [];
        $pos_tables[19] = [];

        $pos_tables[0] = [];
        $pos_tables[1] = [];
        $pos_tables[8] = [];

        //récuopère les resource template
        $resource_templates = $this->api->search('resource_templates',['limit' => 'all'
        ])->getContent();

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
            $tables[] = [
                'tableName'=>ucfirst($str_pro),
                'id'=>$rt->id(),
                'x'=>isset($pos_tables[$key][0]) ? $pos_tables[$key][0] : 0,
                'y'=>isset($pos_tables[$key][1]) ? $pos_tables[$key][1] : 0,
                'cols'=>$cols
            ];
        }
        //consruction de la table des liens
        foreach ($tables as $t) {
            foreach ($t['cols'] as $i => $c) {
                foreach ($c['links'] as $l) {
                    $links[]=[                    
                        "source"=>$t['id'],
                        "target"=>$l['id'],
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
            'links' => $links
            ];
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
}
