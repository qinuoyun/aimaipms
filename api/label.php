<?php

class label {
	//获取标签
	public function getlabel(){
		$label_db = D('labelling');
		$labellist = $label_db->get();
		J(0,'获取成功',$labellist);
	}

	//添加标签
	public function addlabel(){

		$name = $_POST['name'];
		$is_show = $_POST['is_show']?$_POST['is_show']:1;
		$sort = $_POST['sort_order']?$_POST['sort_order']:0;

		if(empty($name)){
			J(30000,'缺少参数');
		}
		$data['name'] = $name;
		$data['is_show'] = $is_show;
		$data['sort_order'] = $sort;
		$label_db = D('labelling');
		$check = $label_db->where('name',$name)->first();
		!empty($check)?J(30001,'标签名不能重复'):'';
		$result = $label_db->insertGetId($data);
		$result?J(0,'添加成功',['lab_id'=>$result]):J(30001,'添加失败');
	}

	//修改标签
	public function editlabel(){
		$lab_id = $_POST['lab_id'];
		$name = $_POST['name'];
		$is_show = $_POST['is_show']?$_POST['is_show']:1;
		$sort = $_POST['sort_order']?$_POST['sort_order']:0;

		if(empty($lab_id) || empty($name)){
			J(30000,'缺少参数');
		}

		$data['name'] = $name;
		$data['is_show'] = $is_show;
		$data['sort_order'] = $sort;
		$label_db = D('labelling');
		$check = $label_db->where('name',$name)->first();
		!empty($check) && $check['lab_id'] != $lab_id?J(30001,'标签名不能重复'):'';
		$result = $label_db->where('lab_id',$lab_id)->update($data);
		$result?J(0,'修改成功'):J(30001,'修改失败');
	}

	// 删除标签
	public function dellabel(){
		$lab_id = $_POST['lab_id'];
		empty($lab_id)?J(30000,'缺少参数'):'';
		$lab_id = is_array($lab_id)?$lab_id:[$lab_id];
		
		$label_db = D('labelling');
		$goods_label_db = D('goods_labelling');

		$cant_id = [];
		for ($i=0; $i < count($lab_id); $i++) {
			$result = null;
			$result = $goods_label_db->where('lab_id',$lab_id[$i])->get();
			if (empty($result)) {
				$label_db->where('lab_id',$lab_id[$i])->delete();
				array_push($cant_id,$lab_id[$i]);
			}
		}

		!empty($cant_id)?J(0,'删除成功',$cant_id):J(30001,'删除失败');
		
	}

	//是否显示 1显示 2不显示
	public function changeshow(){
		$lab_id = $_POST['lab_id'];
		$show = $_POST['show'];
		empty($show) || empty($lab_id)?J(30000,'缺少参数'):'';

		$show = $show == 1?2:1;

		$result = D('labelling')->where('lab_id',$lab_id)->update(['is_show'=>$show]);
		$result?J(0,'更改成功'):J(30001,'更改失败');
	}
}