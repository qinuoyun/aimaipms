<?php

class comment {
    //获取评论
    public function getcomment() {
        $page      = empty($_POST['page']) ? 1 : $_POST['page'];
        $starttime = $_POST['starttime'];
        $endtime   = $_POST['endtime'];
        empty($starttime) || empty($endtime) ? J(30000, '缺少参数') : '';

        //分页
        $count = D('comment')->whereBetween('add_time', [$starttime, $endtime])->andwhere('deleted', 1)->count();
        $n     = 10;
        $num   = ceil($count / $n);
        $m     = ($page - 1) * $n;

        $sql         = "SELECT g.goods_name,c.comment_id,c.user_id,c.content,c.img FROM ub_comment c,ub_goods g WHERE c.goods_id=g.goods_id AND c.deleted=1 AND c.add_time>$starttime AND c.add_time<$endtime ORDER BY c.add_time DESC LIMIT $m,$n";
        $commentlist = sql::query($sql);

        for ($i = 0; $i < count($commentlist); $i++) {
            if (!empty($commentlist[$i]['img'])) {
                $commentlist[$i]['img'] = unserialize($commentlist[$i]['img']);
                for ($j = 0; $j < count($commentlist[$i]['img']); $j++) {
                    $commentlist[$i]['img'][$j] = str_replace("/public", "http://mall.dayezhichuang.com/public", $commentlist[$i]['img'][$j]);
                }
            }

        }

        J(0, '获取成功', ['num' => $count, 'commentlist' => $commentlist]);
    }

    //删除评论
    public function delcomment() {
        $comment_id = $_POST['comment_id'];
        empty($comment_id) ? J(30000, '缺少参数') : '';
        $comment_id = is_array($comment_id) ? $comment_id : [$comment_id];

        $result = D('comment')->whereIn('comment_id', $comment_id)->update(['deleted' => 2]);

        $result ? J(0, '删除成功') : J(30001, '删除失败');
    }
}