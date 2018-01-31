<?php
namespace server\controllers;
use server\models\oss;

class goods59da8bd04473ac6711d74cd91dbe903d {

    //获取商品
    public function getgoods() {
        $deleted      = empty($_POST['deleted']) ? " AND g.deleted=1" : " AND g.deleted=" . $_POST['deleted'];
        $name         = empty($_POST['name']) ? "" : " AND g.goods_name LIKE '%" . $_POST['name'] . "%'";
        $is_recommend = empty($_POST['is_recommend']) ? "" : " AND g.is_recommend=" . $_POST['is_recommend'];
        $is_hot       = empty($_POST['is_hot']) ? "" : " AND g.is_hot=" . $_POST['is_hot'];
        $is_new       = empty($_POST['is_new']) ? "" : " AND g.is_new=" . $_POST['is_new'];

        $where = $deleted . $name . $is_recommend . $is_new . $is_hot;
        $sql   = "SELECT g.goods_name,g.goods_id,g.goods_name,g.original_img,g.store_count,g.goods_sn,g.shop_price,g.is_on_sale,g.is_hot,g.is_new,g.is_recommend,g.sort,c.name FROM ub_goods g,ub_goods_category c WHERE c.id=g.cat_id1" . $where . " ORDER BY g.goods_id DESC";
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
        J(0, '获取成功', ['num' => $count, 'goods_list' => $goods_list]);
    }

    // 获取规格
    public function getspec() {
        $cat_id1 = $_POST['cat_id1'];
        $cat_id2 = $_POST['cat_id2'];

        empty($cat_id1) ? J(30000, '缺少参数') : '';

        $spec_db = D('spec');
        if (empty($cat_id2)) {
            $speclist = $spec_db->where('cat_id1', $cat_id1)->get();
        } else {
            $speclist = $spec_db->where(['cat_id1' => $cat_id1, 'cat_id2' => 0])->orwhere(['cat_id1' => $cat_id1, 'cat_id2' => $cat_id2])->get();
        }
        for ($i = 0; $i < count($speclist); $i++) {
            $speclist[$i]['children'] = D('spec_item')->where('spec_id', $speclist[$i]['id'])->get();
        }
        J(0, '获取成功', $speclist);
    }

    // 添加商品
    public function addgoods() {
        $post = $_POST;

        $admin_id                 = $post['admin_id'];
        $data['admin_id']         = $post['admin_id'];
        $data['cat_id1']          = $post['cat_id1'];
        $data['cat_id2']          = $post['cat_id2'];
        $data['suppliers_id']     = $post['suppliers_id'];
        $data['goods_sn']         = $post['goods_sn'];
        $data['goods_name']       = $post['goods_name'];
        $data['store_count']      = $post['store_count'];
        $data['weight']           = $post['weight'];
        $data['shop_price']       = $post['shop_price'];
        $data['cost_price']       = $post['cost_price'];
        $data['keywords']         = $post['keywords'];
        $data['goods_remark']     = $post['goods_remark'];
        $data['is_free_shipping'] = $post['is_free_shipping'];
        $data['on_time']          = time();
        $data['sort']             = $post['sort'];
        $data['prom_type']        = $post['prom_type'];
        $data['store_id']         = 7;

        if (!empty($post['label'])) {
            $prep_label_db = D('prep_goods_labelling');
            $prep_label_db->where('admin_id', $data['admin_id'])->delete();
            for ($k = 0; $k < count($post['label']); $k++) {
                $prep_label_db->insert(['admin_id' => $data['admin_id'], 'lab_id' => $post['label'][$k]]);
            }

        }

        // 判断是正式提交还是预提交
        if (empty($post['giveway']) || $post['giveway'] == 2) {
            $this->addprep($data) ? J(0, '预保存成功') : J(30001, '预保存失败');
        } else {
            empty($data['cat_id1']) || empty($data['suppliers_id']) || empty($data['goods_sn']) || empty($data['goods_name']) || empty($data['shop_price']) || empty($data['cost_price']) ? J(30000, '缺少参数') : '';
            if ($this->addprep($data)) {
                //从预存表获取数据插入正式表
                $goods = D('prep_goods')->where('admin_id', $admin_id)->first();
                array_shift($goods);
                $imgs   = D('prep_goods_images')->where('admin_id', $admin_id)->field(['image_url', 'img_sort'])->get();
                $labels = D('prep_goods_labelling')->where('admin_id', $admin_id)->field(['lab_id'])->get();

                $goods_id = D('goods')->insertGetId($goods);
                if (empty($goods_id)) {
                    J(30001, '商品上传失败');
                } else {
                    $imgs_db = D('goods_images');
                    for ($i = 0; $i < count($imgs); $i++) {
                        $imgs[$i]['goods_id'] = $goods_id;
                        $imgs_db->insert($imgs[$i]);
                    }

                    $label_db = D('goods_labelling');
                    for ($j = 0; $j < count($labels); $j++) {
                        $labels[$j]['goods_id'] = $goods_id;
                        $label_db->insert($labels[$j]);
                    }

                    //上传成功后删除预存表内容
                    D('prep_goods')->where('admin_id', $admin_id)->delete();
                    D('prep_goods_images')->where('admin_id', $admin_id)->delete();
                    D('prep_goods_labelling')->where('admin_id', $admin_id)->delete();
                    J(0, '上传成功', $goods_id);
                }

            }
        }
    }

    //添加图片
    public function addimages() {
        empty($_POST['admin_id']) && empty($_POST['goods_id']) ? J(30000, '缺少参数') : '';
        $url = '';

        if (empty($_POST['goods_id'])) {
            $goods = D('prep_goods')->where('admin_id', $_POST['admin_id'])->first();
        } else {
            $goods = D('goods')->where('goods_id', $_POST['goods_id'])->first();
        }
        if (!empty($_FILES['original_img'])) {
            !empty($goods['original_img']) ? J(30001, '请先删除原图') : '';
            $url                  = $this->addimg($_FILES['original_img']);
            $data['original_img'] = $url;
        }

        if (!empty($_FILES['goods_content'])) {
            $content               = $this->shear_img($_FILES['goods_content']);
            $data['goods_content'] = htmlspecialchars($content);
        }

        //没有商品id则为预保存
        if (empty($_POST['goods_id'])) {
            $data['admin_id'] = $_POST['admin_id'];
            if (!empty($_FILES['images'])) {
                $prep_img_db = D('prep_goods_images');
                //判断是否已经超过5张图
                $num = $prep_img_db->where('admin_id', $data['admin_id'])->get();
                count($num) >= 5 ? J(30001, '上传失败,轮播图不能超过5张') : '';

                $url = $this->addimg($_FILES['images']);
                if ($url) {
                    $data2              = [];
                    $data2['admin_id']  = $data['admin_id'];
                    $data2['image_url'] = $url;
                    $prep_img_db->insert($data2);
                    J(0, '图片上传成功', $url);
                } else {
                    J(30001, '上传失败');
                }
            }
            if (!empty($data['goods_content']) || !empty($data['original_img'])) {
                //如果为详情图则删除原来的图片
                if (!empty($data['goods_content'])) {

                    $goods_content = htmlspecialchars_decode($goods['goods_content']);
                    preg_match_all("/src=\"(.*)\"/U", $goods_content, $arr1); //$arr1[1]为详情图
                    $this->delimg($arr1[1]);
                    $url = $content;
                }
                $this->addprep($data) ? J(0, '图片上传成功', $url) : '';
            } else {
                J(30001, '上传失败');
            }
        }

        //没有管理员id则不是预保存
        if (empty($_POST['admin_id'])) {
            $data['goods_id'] = $_POST['goods_id'];
            if (!empty($_FILES['images'])) {
                $img_db = D('goods_images');
                //判断是否已经超过5张图
                $num = $img_db->where('goods_id', $data['goods_id'])->get();
                count($num) >= 5 ? J(30001, '上传失败,轮播图不能超过5张') : '';

                $url = $this->addimg($_FILES['images']);
                if ($url) {
                    $data2              = [];
                    $data2['goods_id']  = $data['goods_id'];
                    $data2['image_url'] = $url;
                    $img_db->insert($data2);
                    J(0, '图片上传成功', $url);
                } else {
                    J(30001, '上传失败');
                }
            }

            if (!empty($data['goods_content']) || !empty($data['original_img'])) {
                //如果为详情图则删除原来的图片
                if (!empty($data['goods_content'])) {

                    $goods_content = htmlspecialchars_decode($goods['goods_content']);
                    preg_match_all("/src=\"(.*)\"/U", $goods_content, $arr1); //$arr1[1]为详情图
                    $this->delimg($arr1[1]);
                    $url = $content;
                }
                D('goods')->where('goods_id', $data['goods_id'])->update($data) ? J(0, '图片上传成功', $url) : '';
            } else {
                J(30001, '上传失败');
            }

        }

    }

    //预添加或预修改
    public function addprep($data = null) {
        empty($data['admin_id']) ? J(30000, '缺少参数') : '';
        $prep_goods = D('prep_goods');
        $checkprep  = $prep_goods->where('admin_id', $data['admin_id'])->first();
        if (empty($checkprep)) {
            $result = $prep_goods->insert($data);
        } else {
            $result = $prep_goods->where('admin_id', $data['admin_id'])->update($data);
        }

        return $result;
    }

    //获取商品详情
    public function goodsinfo() {
        $post     = $_POST;
        $goods_id = $post['goods_id'];
        $admin_id = $post['admin_id'];
        empty($goods_id) && empty($admin_id) ? J(30000, '缺少参数') : '';

        if (empty($admin_id)) {
            $good                  = D('goods')->where('goods_id', $goods_id)->field('goods_id,cat_id1,cat_id2,goods_sn,goods_name,store_count,weight,shop_price,cost_price,keywords,is_free_shipping,goods_remark,goods_content,original_img,sort,prom_type,suppliers_id')->get();
            $good                  = $good[0];
            $good['goods_content'] = htmlspecialchars_decode($good['goods_content']);
            $good['images']        = D('goods_images')->where('goods_id', $goods_id)->get();
            $lab_id                = D('goods_labelling')->where('goods_id', $goods_id)->lists('lab_id');

            if (empty($lab_id)) {
                $good['label'] = [];
            } else {
                $good['label'] = D('labelling')->whereIn('lab_id', $lab_id)->get();
            }

        } else {
            $good = D('prep_goods')->where('admin_id', $admin_id)->field('admin_id,cat_id1,cat_id2,goods_sn,goods_name,store_count,weight,shop_price,cost_price,keywords,is_free_shipping,goods_remark,goods_content,original_img,sort,prom_type,suppliers_id')->get();
            $good = $good[0];
            if (!empty($good)) {
                $good['goods_content'] = htmlspecialchars_decode($good['goods_content']);
            }

            $images = D('prep_goods_images')->where('admin_id', $admin_id)->get();
            if (!empty($images)) {
                $good['images'] = $images;
            }

            $lab_id = D('prep_goods_labelling')->where('admin_id', $admin_id)->lists('lab_id');
            if (!empty($lab_id)) {
                $good['label'] = D('labelling')->whereIn('lab_id', $lab_id)->get();
            }

        }

        $good = json_decode(str_replace("\/public", "http:\/\/mall.dayezhichuang.com\/public", json_encode($good)), true);
        J(0, '获取成功', $good);
    }

    // 修改商品
    public function editgoods() {

        $post     = $_POST;
        $goods_id = $post['goods_id'];
        empty($goods_id) || empty($post['cat_id1']) || empty($post['suppliers_id']) || empty($post['goods_sn']) || empty($post['goods_name']) || empty($post['shop_price']) || empty($post['cost_price']) ? J(30000, '缺少参数') : '';

        $data['cat_id1']          = $post['cat_id1'];
        $data['cat_id2']          = $post['cat_id2'];
        $data['suppliers_id']     = $post['suppliers_id'];
        $data['goods_sn']         = $post['goods_sn'];
        $data['goods_name']       = $post['goods_name'];
        $data['store_count']      = $post['store_count'];
        $data['weight']           = $post['weight'];
        $data['shop_price']       = $post['shop_price'];
        $data['cost_price']       = $post['cost_price'];
        $data['keywords']         = $post['keywords'];
        $data['goods_remark']     = $post['goods_remark'];
        $data['is_free_shipping'] = $post['is_free_shipping'];
        $data['sort']             = $post['sort'];
        $data['prom_type']        = $post['prom_type'];
        $data['store_id']         = 7;

        if (!empty($post['label'])) {
            $label_db = D('goods_labelling');
            $label_db->where('goods_id', $goods_id)->delete();
            for ($k = 0; $k < count($post['label']); $k++) {
                $label_db->insert(['goods_id' => $goods_id, 'lab_id' => $post['label'][$k]]);
            }

        }

        $result = D('goods')->where('goods_id', $goods_id)->update($data);
        $result ? J(0, '修改成功') : J(30001, '修改失败');

    }

    //修改显示
    public function editstatus() {
        $goods_id = $_POST['goods_id'];
        $type     = $_POST['type'];
        $status   = $_POST['status'];
        empty($goods_id) || empty($type) || empty($status) ? J(30000, '缺少参数') : '';
        $goods_id = is_array($goods_id) ? $goods_id : [$goods_id];

        $status = $status == 1 ? 2 : 1;
        $result = D('goods')->whereIn('goods_id', $goods_id)->update([$type => $status]);
        $result ? J(0, '修改成功') : J(30001, '修改失败');

    }

    //修改排序
    public function editsort() {
        $goods_id = $_POST['goods_id'];
        $sort     = $_POST['sort'];
        empty($goods_id) || empty($sort) ? J(30000, '缺少参数') : '';

        $result = D('goods')->where('goods_id', $goods_id)->update(['sort' => $sort]);

        $result ? J(0, '修改成功') : J(30001, '修改失败');
    }

    public function getspecgoods() {
        $goods_id = $_POST['goods_id'];
        empty($goods_id) ? J(30000, '缺少参数') : '';
        $spec_goods = D('spec_goods_price')->where('goods_id', $goods_id)->get();

        $item_db         = D('spec_item');
        $spec_goods_list = [];
        for ($i = 0; $i < count($spec_goods); $i++) {
            $keyarr = explode('_', $spec_goods[$i]['key']);
            for ($j = 0; $j < count($keyarr); $j++) {
                $item                                  = $item_db->where('id', $keyarr[$j])->first();
                $spec_goods_list[$i][$item['spec_id']] = $item['item'];
            }
            $spec_goods_list[$i]['item_id']     = $spec_goods[$i]['item_id'];
            $spec_goods_list[$i]['price']       = $spec_goods[$i]['price'];
            $spec_goods_list[$i]['cost_price']  = $spec_goods[$i]['cost_price'];
            $spec_goods_list[$i]['store_count'] = $spec_goods[$i]['store_count'];

        }
        J(0, '获取成功', $spec_goods_list);

    }

    //添加规格商品
    public function savespecgoods() {
        $post = $_POST;
        if (empty($post['item_id'])) {
            empty($post['goods_id']) || empty($post['key']) || empty($post['price']) || empty($post['cost_price']) || empty($post['store_count']) ? J(30000, '缺少参数') : '';
            $data['goods_id']    = $post['goods_id'];
            $data['key']         = implode("_", $post['key']); //规格值id
            $data['price']       = $post['price'];
            $data['cost_price']  = $post['cost_price'];
            $data['store_count'] = $post['store_count'];
            $data['store_id']    = 7;

            //规格组合
            $items = D('spec_item')->whereIn('id', $post['key'])->get();
            for ($i = 0; $i < count($items); $i++) {
                $name = D('spec')->where('id', $items[$i]['spec_id'])->pluck('name');
                $key_name .= $name . ':' . $items[$i]['item'] . ' ';
            }
            $data['key_name'] = $key_name;

            $result = D('spec_goods_price')->insertGetId($data);
            $result ? J(0, '添加成功', $result) : J(30001, '添加失败');
        } else {
            empty($post['price']) || empty($post['cost_price']) || empty($post['store_count']) ? J(30000, '缺少参数') : '';
            $data['price']       = $post['price'];
            $data['cost_price']  = $post['cost_price'];
            $data['store_count'] = $post['store_count'];

            $result = D('spec_goods_price')->where('item_id', $post['item_id'])->update($data);
            $result ? J(0, '修改成功') : J(30001, '修改失败');
        }

    }

    //删除规格商品
    public function delspecgoods() {
        $post = $_POST;
        empty($post['item_id']) ? J(30000, '缺少参数') : '';
        $result = D('spec_goods_price')->where('item_id', $post['item_id'])->delete();
        $result ? J(0, '删除成功') : J(30001, '删除失败');

    }

    //商品封面轮播图删除
    public function delimages() {
        $post     = $_POST;
        $admin_id = $post['admin_id'];
        $goods_id = $post['goods_id'];
        $type     = $post['type'];
        $url      = $post['url'];

        // 判断是正式提交还是预提交
        if (empty($post['giveway']) || $post['giveway'] == 2) {
            empty($admin_id) || empty($url) || empty($type) ? J(30000, '缺少参数') : '';
            if ($type == 'images') {
                $result = D('prep_goods_images')->where(['admin_id' => $admin_id, 'image_url' => $url])->delete();
            } elseif ($type == 'original_img') {
                $result = D('prep_goods')->where(['admin_id' => $admin_id])->update(['original_img' => '']);
            }

            if ($result) {
                $this->delimg($url);
                J(0, '删除成功');
            } else {
                J(30001, '删除失败');
            }
        } else {
            empty($goods_id) || empty($url) || empty($type) ? J(30000, '缺少参数') : '';
            if ($type == 'images') {
                $result = D('goods_images')->where(['goods_id' => $goods_id, 'image_url' => $url])->delete();
            } elseif ($type == 'original_img') {
                $result = D('goods')->where(['goods_id' => $goods_id])->update(['original_img' => '']);
            }

            if ($result) {
                $this->delimg($url);
                J(0, '删除成功');
            } else {
                J(30001, '删除失败');
            }
        }

    }

    //初步删除商品,加入回收站
    public function recycle() {
        $goods_id = $_POST['goods_id'];
        $deleted  = $_POST['deleted'];
        empty($goods_id) || empty($deleted) ? J(30000, '缺少参数') : '';
        $goods_id = is_array($goods_id) ? $goods_id : [$goods_id];

        $result = D('goods')->whereIn('goods_id', $goods_id)->update(['deleted' => $deleted]);

        $result ? J(0, '操作成功') : J(30001, '操作失败');

    }

    //彻底删除商品
    public function delgoods() {

        $goods_id = $_POST['goods_id'];
        empty($goods_id) ? J(30000, '缺少参数') : '';
        $goods_id = is_array($goods_id) ? $goods_id : [$goods_id];

        $goods_db = D('goods');
        $img_db   = D('goods_images');
        $err_arr  = [];
        $err      = 0;
        for ($j = 0; $j < count($goods_id); $j++) {

            $goods         = $goods_db->where('goods_id', $goods_id[$j])->first();
            $goods_content = htmlspecialchars_decode($goods['goods_content']);
            preg_match_all("/src=\"(.*)\"/U", $goods_content, $arr1); //$arr1[1]为详情图
            $arr2   = [$goods['original_img']]; //封面图
            $arr3   = $img_db->where('goods_id', $goods_id[$j])->lists('image_url'); //商品轮播图
            $arr    = array_merge($arr1[1], $arr2, $arr3); //商品所有图片地址
            $result = $goods_db->where('goods_id', $goods_id[$j])->delete();
            if ($result) {
                $img_db->where('goods_id', $goods_id[$j])->delete();
                D('goods_labelling')->where('goods_id', $goods_id[$j])->delete();
                D('spec_goods_price')->where('goods_id', $goods_id[$j])->delete();
                $this->delimg($arr);
            } else {
                //将删除失败的商品id保存起来
                $err_arr[$err] = $goods_id[$j];
                $err++;
            }

        }

        empty($err_arr) ? J(0, '删除成功') : J(30001, '删除失败', $err_arr);

    }

    //清空预保存
    public function delprep() {
        $admin_id = $_POST['admin_id'];
        empty($admin_id) ? J(30000, '缺少参数') : '';
        $goods         = D('prep_goods')->where('admin_id', $admin_id)->first();
        $goods_content = htmlspecialchars_decode($goods['goods_content']);
        preg_match_all("/src=\"(.*)\"/U", $goods_content, $arr1); //$arr1[1]为详情图
        $arr2   = [$goods['original_img']]; //封面图
        $arr3   = D('prep_goods_images')->where('admin_id', $admin_id)->lists('image_url'); //商品轮播图
        $arr    = array_merge($arr1[1], $arr2, $arr3); //商品所有图片地址
        $result = D('prep_goods')->where('admin_id', $admin_id)->delete();
        if ($result) {
            D('prep_goods_images')->where('admin_id', $admin_id)->delete();
            D('prep_goods_labelling')->where('admin_id', $admin_id)->delete();
            $this->delimg($arr);
            J(0, '删除成功');
        } else {
            J(30001, '删除失败');
        }
    }
    //获取所有商品图片
    public function getimg() {
        $page = empty($_POST['page']) ? 1 : $_POST['page'];
        $name = $_POST['name'];
        //分页
        $count = D('goods')->where('deleted', 1)->count();
        $n     = 10;
        $num   = ceil($count / $n);
        $m     = ($page - 1) * $n;
        if (empty($name)) {
            $goods = D('goods')->field('goods_id,goods_name,original_img,goods_content')->limit($m, $n)->get();
        } else {
            $goods = D('goods')->where('goods_name', 'like', "%" . $name . "%")->field('goods_id,goods_name,original_img,goods_content')->limit($m, $n)->get();
        }

        for ($i = 0; $i < count($goods); $i++) {
            $goods[$i]['images'] = D('goods_images')->where('goods_id', $goods[$i]['goods_id'])->get();
        }

        $goods = json_decode(str_replace("\/public", "http:\/\/mall.dayezhichuang.com\/public", json_encode($goods)), true);
        J(0, '获取成功', ['num' => $count, 'goods' => $goods]);

    }

    //添加图片到oss
    public function addimg($file) {
        $time     = time();
        $filepath = ROOT_PATH . "/Api/img/";
        $imgname  = $time . uniqid() . strrchr($file['name'], '.');
        $tmp      = $file['tmp_name'];
        if (move_uploaded_file($tmp, $filepath . $imgname)) {
            include_once ROOT_PATH . '/Api/oss.php';
            $url = \oss::uploadFile($filepath . $imgname, "goods/img/" . date('Y', $time) . "/" . date('m-d', $time) . "/" . $imgname);
            unlink($filepath . $imgname);
            if ($url) {
                return $url;
            } else {
                J(30010, '图片上传失败');
            }
        } else {
            J(30010, '图片上传失败');
        }
    }

    //从oss删除图片
    public function delimg($object) {
        include_once ROOT_PATH . '/Api/oss.php';
        $url = \oss::deleteObject($object);
    }

    //自动切图
    public function shear_img($file) {
        // 保存详情原图
        $time     = time();
        $filepath = ROOT_PATH . "/Api/img/";
        $imgname  = $time . uniqid() . strrchr($file['name'], '.');
        $tmp      = $file['tmp_name'];
        move_uploaded_file($tmp, $filepath . $imgname);

        list($w, $h) = getimagesize($filepath . $imgname);
        $new_h       = 500; //所截图高度
        $num         = ceil($h / $new_h); //总共截成多少张
        // 根据后缀选着创建
        $extension = strrchr($imgname, '.'); //后缀
        if ($extension == '.jpg' || $extension == '.jpeg') {
            $old_img = imagecreatefromjpeg($filepath . $imgname);
        } elseif ($extension == '.png') {
            $old_img = imagecreatefrompng($filepath . $imgname);
        } else {
            J(30005, '图片格式不符合要求');
        }

        $content_img = []; //详情图本地列表
        for ($i = 0; $i < $num; $i++) {
            //最后一张图高度不够时改变截图高度
            if ($i == ($num - 1)) {
                $copy_h = $h - $new_h * $i;
            } else {
                $copy_h = $new_h;
            }

            $new_img = imagecreatetruecolor($w, $copy_h);
            imagecopy($new_img, $old_img, 0, 0, 0, $new_h * $i, $w, $copy_h);
            $time     = time();
            $imgsname = $time . uniqid() . $extension;
            imagejpeg($new_img, $filepath . $imgsname);
            $content_img[$i] = $filepath . $imgsname;
            imagedestroy($new_img);
        }
        // 详情图上传oss
        $content = '';
        for ($j = 0; $j < count($content_img); $j++) {
            $url2 = '';
            $url2 = \oss::uploadFile($content_img[$j], "goods/img/" . date('Y', $time) . "/" . date('m-d', $time) . strrchr($content_img[$j], '/1'));
            unlink($content_img[$j]);
            if ($url2) {
                $content .= '<p><img src="' . $url2 . '"></p>';
            }
        }
        unlink($filepath . $imgname);
        return $content;

    }

}