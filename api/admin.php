<?php

class admin {

	public function login(){
		$post = $_POST;
		empty($post['name']) || empty($post['pwd']) || empty($post['time']) || empty($post['ip'])?J(30000,'缺少参数'):'';
		$admin = D('admin')->where('name',$post['name'])->first();

		empty($admin)?J(30001,'用户不存在'):'';
		md5($post['pwd']) != $admin['pwd']?J(30002,'密码不正确'):'';
		
		//根据登录时间向上次登录推送消息退出
		$this->pull($admin['last_time']);
		$result = D('admin')->where('name',$post['name'])->update(['last_ip'=>$post['ip'],'last_time'=>$post['time']]);
		if($post['ip'] != $admin['last_ip']){
			J(1,'账号于'.date('Y-m-d H:i:s'.ceil($admin['last_time']/1000).'在'.$admin['last_ip'].'登录!'),$admin);
		}else{
			J(0,'登录成功',$admin);
		}
	}

	public function pull($last_time){
		//消息推送
		$push_api_url = "http://pms.aimai.com:2121/";
		$post_data = array(
		   "type" => "publish",
		   "content" => "你的账号在其他地点登录了",
		   "to" => $last_time, 
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
		curl_exec ( $ch );
		curl_close ( $ch );
	}
}