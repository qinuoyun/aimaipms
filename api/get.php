<?php

class get {
    public function index() {
        $url = "http://aimai-goods-images.oss-cn-hangzhou.aliyuncs.com/goods/img/2017/12-13/15131270665a307c9a76d55.jpg?x-oss-process=image/resize,m_pad,h_50,w_50";
    }
    //获取商品
    public function getGoods() {
        //分类
        if (!empty($_POST['cat_id'])) {
            $cat_id = " AND (g.cat_id1=" . $_POST['cat_id'] . " OR  g.cat_id2=" . $_POST['cat_id'] . ")";
        } else {
            $cat_id = "";
        }
        //标签
        if (!empty($_POST['lab_id'])) {
            $goods_id = D('goods_labelling')->where('lab_id', $_POST['lab_id'])->lists('goods_id');
            $label    = empty($goods_id) ? "" : " AND g.goods_id in(" . $goods_id . ")";
        } else {
            $label = "";
        }
        //商品名模糊查询
        $name = empty($_POST['name']) ? "" : " AND g.goods_name LIKE '%" . $_POST['name'] . "%'";
        //推荐
        $is_recommend = empty($_POST['is_recommend']) ? "" : " AND g.is_recommend=" . $_POST['is_recommend'];
        //热销
        $is_hot = empty($_POST['is_hot']) ? "" : " AND g.is_hot=" . $_POST['is_hot'];
        //精品
        $is_new = empty($_POST['is_new']) ? "" : " AND g.is_new=" . $_POST['is_new'];

        //查询条件拼接
        $where = $cat_id . $label . $name . $is_recommend . $is_new . $is_hot;
        $sql   = "SELECT g.goods_name,g.goods_id,g.goods_name,g.original_img,g.store_count,g.goods_sn,g.shop_price,g.is_on_sale,g.is_hot,g.is_new,g.is_recommend,g.sort,c.name FROM ub_goods g,ub_goods_category c WHERE c.id=g.cat_id1 AND g.deleted=1" . $where . " ORDER BY g.goods_id DESC";
        $page  = empty($_POST['page']) ? 1 : $_POST['page'];
        //分页
        $count      = count(sql::query($sql));
        $n          = 10;
        $num        = ceil($count / $n);
        $m          = ($page - 1) * $n;
        $sql        = $sql . " LIMIT $m,$n";
        $goods_list = sql::query($sql);

        for ($i = 0; $i < count($goods_list); $i++) {
            $goods_list[$i]['original_img'] = str_replace("/public", "http://mall.dayezhichuang.com/public", $goods_list[$i]['original_img']);
        }
        P($goods_list);exit;
        J(0, '获取成功', ['num' => $count, 'goods_list' => $goods_list]);
    }

    //获取分类
    public function getCate($type = 1, $cat_id = 0) {
        $cate_db = D('goods_category');
        switch ($type) {
        case '1': //获取所有父标签和其子标签
            $catelist = $cate_db->where('parent_id', 0)->field(['id', 'name', 'parent_id_path', 'sort_order', 'is_show'])->get();
            for ($i = 0; $i < count($catelist); $i++) {
                $children                 = $cate_db->where('parent_id', $catelist[$i]['id'])->field(['id', 'name', 'parent_id_path', 'sort_order', 'is_show'])->get();
                $catelist[$i]['children'] = $children;
            }
            break;

        case '2': //根据父级id获取下级分类
            $catelist = $cate_db->where('parent_id', $cat_id)->field(['id', 'name', 'parent_id_path', 'sort_order', 'is_show'])->get();
            break;

        default:

            break;
        }
        P($catelist);
        J(0, '获取成功', $catelist);
    }

    //商品详情
    public function goodsInfo($goods_id = 3) {
        $goods                  = D('goods')->where('goods_id', $goods_id)->field('goods_id,cat_id1,cat_id2,goods_sn,goods_name,store_count,weight,shop_price,cost_price,keywords,is_free_shipping,goods_remark,goods_content,original_img,sort,prom_type,suppliers_id')->get();
        $goods                  = $goods[0];
        $goods['goods_content'] = $goods['goods_content'];
        $goods['images']        = D('goods_images')->where('goods_id', $goods_id)->get();

        //获取标签信息
        $lab_id = D('goods_labelling')->where('goods_id', $goods_id)->lists('lab_id');
        if (empty($lab_id)) {
            $goods['label'] = [];
        } else {
            $goods['label'] = D('labelling')->whereIn('lab_id', $lab_id)->get();
        }

        //获取规格信息
        $spec_goods_list = D('spec_goods_price')->where('goods_id', $goods_id)->get();
        if (empty($spec_goods_list)) {
            $goods['spec_item']  = [];
            $goods['spec_price'] = [];
        } else {
            $spec_price = []; //规格商品对应价格
            $items_id   = [];
            foreach ($spec_goods_list as $v) {
                $spec_price[$v['key']] = ['price' => $v['price'], 'store_count' => $v['store_count']];
                $items_id              = array_merge($items_id, explode('_', $v['key']));
            }

            $items_id  = array_unique($items_id);
            $item_list = D('spec_item')
                ->join('spec', 'spec.id', '=', 'spec_item.spec_id')
                ->whereIn('ub_spec_item.id', $items_id)
                ->field('ub_spec_item.id,ub_spec_item.item,ub_spec.name')
                ->get();
            $spec_item = []; //商品规格
            foreach ($item_list as $val) {
                if (array_key_exists($val['name'], $spec_item)) {
                    $spec_item[$val['name']] = array_merge($spec_item[$val['name']], [['key' => $val['id'], 'item' => $val['item']]]);
                } else {
                    $spec_item[$val['name']] = [['key' => $val['id'], 'item' => $val['item']]];
                }

            }

            $goods['spec_item']  = $spec_item;
            $goods['spec_price'] = $spec_price;
        }

        P($goods);
    }

    //添加购物车调用商品信息
    public function cartGoods() {
        $key      = $_POST['key'];
        $goods_id = $_POST['goods_id'];

        if (empty($key)) {
            $goods = D('goods')->where('goods_id', $goods_id)->field(['goods_id', 'goods_sn', 'goods_name', 'market_price', 'cost_price'])->first();
        } else {
            $goods = D('goods')
                ->join('spec_goods_price', 'spec_goods_price.goods_id', '=', 'goods.goods_id')
                ->where(['ub_goods.goods_id' => $goods_id, 'ub_spec_goods_price.key' => $key])
                ->field(['ub_goods.goods_id', 'ub_goods.goods_sn', 'ub_goods.goods_name', 'ub_spec_goods_price.price AS market_price', 'ub_spec_goods_price.cost_price', 'ub_spec_goods_price.key AS spec_key', 'ub_spec_goods_price.key_name AS spec_key_name'])
                ->first();
        }
        return json_encode($goods);
    }
}