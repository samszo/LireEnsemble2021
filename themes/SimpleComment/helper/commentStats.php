<?php
namespace OmekaTheme\Helper;

use Laminas\View\Helper\AbstractHelper;

class commentStats extends AbstractHelper
{
    /**
     * Get stats for comments
     * http://omeka-s.local/api/comments?pretty_print=1
     * http://omeka-s.local/api/items?id=390&pretty_print=1
     * @param string    $nb        Nombre de comment
     * @return array
     */
    public function __invoke($nb='all')
    {
        $view = $this->getView();
        $stats = $view->api()->search('comments',['limit' => $nb
        ])->getContent();

        //-- qui comment plus
        $arr_qui_comment_plus = array();
        $arr_id_items_qui = array();
        $arr_href_qui = array();

        //-- items sont plus comment
        $arr_item_comment_plus = array();
        $arr_id_items_item = array();

        //-- comment ont plus reply
        $arr_reply_comment_plus = array();
        $arr_id_items_reply = array();

        foreach ($stats as $c) {
            //-- qui comment plus
            $arr_qui_comment_plus[] = $c->name();
            $arr_id_items_qui[$c->name()][$c->resource()->id()]['path'] = $c->path();
            $item = $view->api()->search('items',['id' => $c->resource()->id()
            ])->getContent();
            foreach ($item as $it) {
                $arr_id_items_qui[$c->name()][$c->resource()->id()]['title'] = $it->title();
                $arr_id_items_item[$c->resource()->id()]['title'] = $it->title();
            }
            //---- prendre ahref de user
            $page_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
            if ($c->owner() != null) {
                if (strlen($c->name() > 20)) {
                    $str_nom = substr($c->name(), 1, 20);
                } else {
                    $str_nom = $c->name();
                }
                $arr_href_qui[$c->name()] = "<a href='".$page_url."/admin/user/".$c->owner()->id()."'>".$str_nom."</a>";
            } else if ($c->website() != null) {
                $arr_href_qui[$c->name()] = "<a href='".$c->website()."'>".$str_nom."</a>";
            } else {
                $arr_href_qui[$c->name()] = $str_nom;
            }

            //-- items sont plus comment
            $arr_item_comment_plus[] = $c->resource()->id();
            $arr_id_items_item[$c->resource()->id()]['path'] = $c->path();
            $arr_id_items_item[$c->resource()->id()]['id'] = $c->resource()->id();

            //-- comment ont plus reply
            if (count($c->children()) > 0) {
                foreach ($c->children() as $child) {
                    $arr_reply_comment_plus[] = $c->body();
                }
                $arr_id_items_reply[$c->resource()->id()]['path'] = $c->path();
                $arr_id_items_reply[$c->resource()->id()]['comment'] = $c->body();
                foreach ($item as $it) {
                    $arr_id_items_reply[$c->resource()->id()]['title'] = $it->title();
                }
            }
        }

        $arr_general = array("qui", "item", "reply");
        foreach ($arr_general as $key=>$value) {
            $str = "_".$value."_comment_plus";
            ${"counts" . $str} = array_count_values(${"arr" . $str});
            arsort(${"counts" . $str});
            ${"sum" . $str} = array_sum(${"counts" . $str});
            ${"value" . $str} = max(${"counts" . $str});
            ${"nom" . $str} = array_search(${"value" . $str}, ${"counts" . $str});
            ${"rate" . $str} = round((${"value" . $str} * 100) / ${"sum" . $str}, 1);
        }

        $arr_stats_return = [
            'nom_qui_comment_plus' => $nom_qui_comment_plus,
            'rate_qui_comment_plus' => $rate_qui_comment_plus,
            'nom_item_comment_plus' => $nom_item_comment_plus,
            'rate_item_comment_plus' => $rate_item_comment_plus,
            'nom_reply_comment_plus' => $nom_reply_comment_plus,
            'rate_reply_comment_plus' => $rate_reply_comment_plus,
            'sum_qui_comment_plus' => $sum_qui_comment_plus,
            'sum_item_comment_plus' => $sum_item_comment_plus,
            'sum_reply_comment_plus' => $sum_reply_comment_plus,
            'value_qui_comment_plus' => $value_qui_comment_plus,
            'value_item_comment_plus' => $value_item_comment_plus,
            'value_reply_comment_plus' => $value_reply_comment_plus,
            'arr_id_items_qui' => $arr_id_items_qui,
            'arr_id_items_item' => $arr_id_items_item,
            'arr_id_items_reply' => $arr_id_items_reply,
            'arr_href_qui' => $arr_href_qui,
            'counts_item_comment_plus' => $counts_item_comment_plus
        ];

        return $arr_stats_return;

    }
}
