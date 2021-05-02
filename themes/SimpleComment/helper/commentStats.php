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
            $sum_all_comments = array_sum(${"counts" . $str});
            ${"value" . $str} = max(${"counts" . $str});
            ${"nom" . $str} = array_search(${"value" . $str}, ${"counts" . $str});
            ${"rate" . $str} = round((${"value" . $str} * 100) / $sum_all_comments, 1);
        }

        $arr_stats_return = [
            'nom_qui_comment_plus' => $nom_qui_comment_plus,
            'rate_qui_comment_plus' => $rate_qui_comment_plus,
            'nom_item_comment_plus' => $nom_item_comment_plus,
            'rate_item_comment_plus' => $rate_item_comment_plus,
            'nom_reply_comment_plus' => $nom_reply_comment_plus,
            'rate_reply_comment_plus' => $rate_reply_comment_plus,
            'arr_id_items_qui' => $arr_id_items_qui,
            'arr_id_items_item' => $arr_id_items_item,
            'arr_id_items_reply' => $arr_id_items_reply
        ];

        return $arr_stats_return;

    }
}
