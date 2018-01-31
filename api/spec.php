<?php

class spec {
	//获取分类
	public function getcate(){
		$parent_id = $_POST['parent_id'];
		$cate_db = D('goods_category');
		if(empty($parent_id)){
			$catelist = $cate_db->where('parent_id',0)->field(['id','name'])->get();
		}else{
			$catelist = $cate_db->where('parent_id',$parent_id)->field(['id','name'])->get();
		}
		
		J(0,'获取成功',$catelist);
	}


	// 获取规格名
	public function getspec(){
		$cat_id1 = $_POST['cat_id1'];
		$cat_id2 = $_POST['cat_id2'];

		empty($cat_id1)?J(30000,'缺少参数'):'';

		$spec_db = D('spec');
		if(empty($cat_id2)){
			$speclist = $spec_db->where('cat_id1',$cat_id1)->get();
		}else{
			$speclist = $spec_db->where(['cat_id1'=>$cat_id1,'cat_id2'=>0])->orwhere(['cat_id1'=>$cat_id1,'cat_id2'=>$cat_id2])->get();
		}
		J(0,'获取成功',$speclist);
	}

	//获取规格值
	public function getspecitem(){
		$spec_id = $_POST['spec_id'];

		empty($spec_id)?J(30000,'缺少参数'):'';

		$item_db = D('spec_item');

		$itemlist = $item_db->where('spec_id',$spec_id)->get();

		J(0,'获取成功',$itemlist);
	}

	//添加规格名
	public function addspec(){
		$name = $_POST['name'];
		$order = $_POST['order']?$_POST['order']:0;
		$cat_id1 = $_POST['cat_id1'];
		$cat_id2 = $_POST['cat_id2']?$_POST['cat_id2']:0;

		empty($name) || empty($cat_id1)?J(30000,'缺少参数'):'';

		//判断该分类下是否已有该规格名
		$check = D('spec')->where(['cat_id1'=>$cat_id1,'name'=>$name])->get();
		!empty($check)?J(30001,'统一分类下规格名不可重复'):'';

		$data['name'] = $name;
		$data['order'] = $order;
		$data['cat_id1'] = $cat_id1;
		$data['cat_id2'] = $cat_id2;

		$result = D('spec')->insertGetId($data);
		$result?J(0,'添加成功',$result):J(30001,'添加失败');
	}

	//修改规格名
	public function editspec(){
		$id = $_POST['id'];
		$name = $_POST['name'];
		$order = $_POST['order']?$_POST['order']:0;

		empty($id) || empty($name)?J(30000,'缺少参数'):'';

		$data['name'] = $name;
		$data['order'] = $order;

		$result = D('spec')->where('id',$id)->update($data);
		$result?J(0,'修改成功'):J(30001,'修改失败');
	}

	//删除规格名
	public function delspec(){
		$id = $_POST['id'];
		empty($id)?J(30000,'缺少参数'):'';

		$item = D('spec_item')->where('spec_id',$id)->first();
		if (empty($item)) {
			D('spec')->where('id',$id)->delete();
			J(0,'删除成功');
		}else{
			J(30001,'该规格名下有规格项,不能删除');
		}
	}

	//添加规格值
	public function additem(){
		$spec_id = $_POST['spec_id'];
		$item = $_POST['item'];

		empty($spec_id) || empty($item)?J(0,'缺少参数'):'';

		$data['spec_id'] = $spec_id;
		$data['item'] = $item;
		$data['store_id'] = 7;

		$check = D('spec_item')->where(['spec_id'=>$spec_id,'item'=>$item])->first();
		!empty($check)?J(30001,'同一规格名下规格值不可重复'):'';
		$result = D('spec_item')->insertGetId($data);
		$result?J(0,'添加成功',$result):J(30001,'添加失败');
	}


	//删除规格值
	public function delitem(){
		$id = $_POST['id'];
		empty($id)?J(30000,'缺少参数'):'';

		$item = D('spec_goods_price')->get();
		$arr = [];
		for ($i=0; $i < count($item); $i++) { 
			$arr = array_merge($arr,explode('_',$item[$i]['key']));
		}
		
		if(in_array($id, $arr)){
			J(30001,'此规格含有商品不能删除');
		}else{
			D('spec_item')->where('id',$id)->delete();
			J(0,'删除成功');
		}
	}
}