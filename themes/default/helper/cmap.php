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

    public function __invoke()
    {
        $this->api = $this->getView()->api();
        $this->proLinks = array();

        //récupère les position de tables
        // donnees statique
        $pos_tables = array();
        $pos_tables[2] = [-64.16794744643653, -225.66279862581356];
        $pos_tables[3] = [-853.7752918523779, -221.43871535547365];
        $pos_tables[4] = [504.9955746323891, 112.90570095022161];
        $pos_tables[5] = [-391.5806170609788, 387.24958414810476];
        $pos_tables[6] = [-836.3334819011574, 670.4776303122255];
        $pos_tables[7] = [500.65283330777197, -158.35447566593007];
        $pos_tables[9] = [-136.22391078760984, 561.8809044553493];
        $pos_tables[10] = [491.2299114038808, -376.7836216386181];
        $pos_tables[11] = [-736.9150421537685, 308.2457719129614];
        $pos_tables[12] = [-623.1250459442194, -601.0068076484094];
        $pos_tables[13] = [-937.6315636530862, -931.1520523310833];
        $pos_tables[14] = [-1140.6560395670926, -98.18721250575173];
        $pos_tables[15] = [-1148.8199831869213, -630.7843866444268];
        $pos_tables[16] = [-409.849366259168, -23.05756417260503];
        $pos_tables[17] = [-1223.2192326115742, 388.24270557018133];
        $pos_tables[18] = [-194.07061998655354, -194.19490386961058];
        $pos_tables[19] = [512.265693670412, 475.1210393414757];

        $pos_tables[0] = [-64.16794744643653, -225.66279862581356];
        $pos_tables[1] = [-64.16794744643653, -225.66279862581356];
        $pos_tables[8] = [-64.16794744643653, -225.66279862581356];

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
                //'x'=>$pos_tables[$key][0],
                //'y'=>$pos_tables[$key][1],
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
