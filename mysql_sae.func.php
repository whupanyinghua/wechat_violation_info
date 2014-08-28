<?php
/**
 */
	require_once("configure.php");
    /*配置数据库参数*/
	//$dbname = getenv('SAE_MYSQL_DB');
     
	// 连主库
	$link=mysql_connect(SAE_MYSQL_HOST_M.':'.SAE_MYSQL_PORT,SAE_MYSQL_USER,SAE_MYSQL_PASS);

	// 连从库
	// $link=mysql_connect(SAE_MYSQL_HOST_S.':'.SAE_MYSQL_PORT,SAE_MYSQL_USER,SAE_MYSQL_PASS);

	if($link)
	{
    mysql_select_db(SAE_MYSQL_DB,$link);
    //your code goes here
    }

    /**
    * 接下来就可以使用其它标准php mysql函数操作进行数据库操作
    */
	//创建一个数据库表
	function _create_table($sql){
        mysql_query($sql) or die('创建表失败，错误信息：'.mysql_error());
    	return "创建表成功";
    }

	//插入数据
	function _insert_data($sql){
      	if(!mysql_query($sql)){
       		return 0;    //插入数据失败
    	}else{
          	if(mysql_affected_rows()>0){
              	return 1;    //插入成功
          	}else{
              	return 2;    //没有行受到影响
          	}
    	}
	}
	//删除数据
	function _delete_data($sql){
      	if(!mysql_query($sql)){
        	return 0;    //删除失败
      	}else{
          	if(mysql_affected_rows()>0){
              	return 1;    //删除成功
          	}else{
              	return 2;    //没有行受到影响
          	}
    	}
	}

	//修改数据
	function _update_data($sql){
      	if(!mysql_query($sql)){
        	return 0;    //更新数据失败
    	}else{
          	if(mysql_affected_rows()>0){
              	return 1;    //更新成功;
          	}else{
              	return 2;    //没有行受到影响
          	}
        }
	}

	//检索数据
	function _select_data($sql){
    	$ret = mysql_query($sql) or die('SQL语句有错误，错误信息：'.mysql_error());
    	return $ret;
	}

	//删除表
	function _drop_table($sql){
    	mysql_query($sql) or die('删除表失败，错误信息：'.mysql_error());
    	return "删除表成功";
	}
?>