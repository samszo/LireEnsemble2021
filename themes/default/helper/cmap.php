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

        //récuopère les resource template
        $resource_templates = $this->api->search('resource_templates',['limit' => 'all'
        ])->getContent();

        $tables = array();
        $links = array();
        foreach ($resource_templates as $rt) {
            //récupère les propriétés
            $pros = $rt->resourceTemplateProperties();
            $cols = array();
            foreach ($pros as $pro) {
                $p = $pro->property();
                $cols[] = [
                    'itemName'=>$p->label()
                    ,'id'=>$p->id()
                    ,'links'=>$this->getPropertyLinks($p)                
                ];
            }
            $tables[] = ['tableName'=>$rt->label(),'id'=>$rt->id(),'cols'=>$cols];
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
        if(isset($this->proLinks[$p->id()]))return $this->proLinks[$p->id()];
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
