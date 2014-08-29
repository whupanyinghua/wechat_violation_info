<?php
require_once './mysql_sae.func.php';
//判断是否已经绑定
		$select_sql = "SELECT * from users WHERE from_user='oupH_t4D_7hyZx6uNBSTJeSEYH04'";
		$res = _select_data($select_sql);
		//仅仅获取第一行数据，如果需要获取所有行，应该循环执行mysql_fetch_array语句
		$rows = mysql_fetch_array($res, MYSQL_ASSOC);
		
        var_dump($rows);
var_dump(count($rows));
var_dump(is_array($bindingInfoArr));
if(is_array($bindingInfoArr) && count($bindingInfoArr)>0){
	echo "ttt";
}else{
	echo "sss";
}
?>