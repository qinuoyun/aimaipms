<?php

class cate {
	//获取分类
	public function getcate(){
		$cate_db = D('goods_category');
		$catelist = $cate_db->where('parent_id',0)->field(['id','name','parent_id_path','sort_order','is_show'])->get();
		for ($i=0; $i < count($catelist); $i++) { 
			$children = $cate_db->where('parent_id',$catelist[$i]['id'])->field(['id','name','parent_id_path','sort_order','is_show'])->get();
			$catelist[$i]['children'] = $children;
		}
		J(0,'获取成功',$catelist);
	}

	//添加分类
	public function addcate(){
		$path = $_POST['parent_id_path']?$_POST['parent_id_path']:0;
		$name = $_POST['name'];
		$is_show = $_POST['is_show']?$_POST['is_show']:1;
		$sort = $_POST['sort']?$_POST['sort']:0;

		empty($name)?J(30000,'缺少参数'):'';

		$data['level'] = $path === 0?1:2;
		$path_copy = $path;
		$data['parent_id'] = $path_copy === 0?0:ltrim($path_copy,'0_');
		$data['name'] = $name;
		$data['mobile_name'] = $name;
		$data['is_show'] = $is_show;
		$data['sort_order'] = $sort;
		$cate_db = D('goods_category');
		$check = $cate_db->where('name',$name)->first();
		!empty($check)?J(30001,'分类名不能重复'):'';
		$id = $cate_db->insertGetId($data);
		$result = $cate_db->where('id',$id)->update(['parent_id_path'=>$path.'_'.$id]);
		$result?J(0,'添加成功',$id):J(30001,'添加失败');
	}

	//修改分类
	public function editcate(){
		$id = $_POST['id'];
		$name = $_POST['name'];
		$sort = $_POST['sort']?$_POST['sort']:0;
		empty($id) || empty($name)?J(30000,'缺少参数'):'';

		$data['name'] = $name;
		$data['mobile_name'] = $name;
		$data['sort_order'] = $sort;
		$cate_db = D('goods_category');
		$check = $cate_db->where('name',$name)->first();
		!empty($check) && $check['id'] != $id?J(30001,'分类名不能重复'):'';
		$result = $cate_db->where('id',$id)->update($data);
		$result?J(0,'修改成功'):J(30001,'修改失败');
	}

	//修改显示
	public function editshow(){
		$id = $_POST['id'];
		$is_show = $_POST['is_show'];
		empty($id) || empty($is_show)?J(30000,'缺少参数'):'';

		$is_show = $is_show==1?2:1;
		$result = D('goods_category')->where('id',$id)->update(['is_show'=>$is_show]);
		$result?J(0,'修改成功'):J(30001,'修改失败');

	}

	// 删除分类
	public function delcate(){
		$id = $_POST['id'];

		empty($id)?J(30000,'缺少参数'):'';

		$cate_db = D('goods_category');
		$goods_db = D('goods');

		$cate = $cate_db->where('id',$id)->first();
		if($cate['level'] == 1){
			$result = $goods_db->where('cat_id1',$id)->get();
		}else{
			$result = $goods_db->where('cat_id2',$id)->get();
		}

		if (empty($result)) {
			if($cate['level'] == 1){
				$cate_db->where('parent_id',$id)->delete();
			}
			$cate_db->where('id',$id)->delete()?J(0,'删除成功'):J(30001,'删除失败');
			
		}else{
			J(30001,'该分类下含有商品不能删除');
		}
	}

	//是否显示 1显示 2不显示
	public function changeshow(){
		$id = $_POST['id'];
		$show = $_POST['show'];
		empty($show) || empty($id)?J(30000,'缺少参数'):'';

		$show = $show == 1?2:1;

		$result = D('goods_category')->where('id',$id)->update(['is_show'=>$show]);
		$result?J(0,'更改成功'):J(30001,'更改失败');
	}
	
}