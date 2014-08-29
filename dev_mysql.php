<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    	<title>数据库测试</title>
    </head>
    <body>
<?php
require_once './mysql_sae.func.php';
//创建表

$create_sql = "CREATE TABLE IF NOT EXISTS `test_mysql` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` varchar(40) DEFAULT NULL,
  `account` varchar(40) DEFAULT NULL,
  `password` varchar(40) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `from_user` (`from_user`)
)";

echo _create_table($create_sql);

$create_sql = "CREATE TABLE IF NOT EXISTS `peccancyInfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_num` varchar(40) DEFAULT NULL,
  `engine_num` varchar(40) DEFAULT NULL,
  `office` varchar(256) DEFAULT NULL,
  `place` varchar(256) DEFAULT NULL,
  `actioncode` int(11) DEFAULT NULL,
  `point` int(11) DEFAULT NULL,
  `money` int(11) DEFAULT NULL,
  `action` varchar(256) DEFAULT NULL,
  `law` varchar(256) DEFAULT NULL,
  `occurtime` datetime DEFAULT NULL,
  `flag` varchar(256) DEFAULT NULL,
  `origin` varchar(256) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
   PRIMARY KEY (`id`)
)";

echo _create_table($create_sql);

$create_sql = "CREATE TABLE IF NOT EXISTS `peccancyBerif` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_num` varchar(40) DEFAULT NULL,
  `all_items` int(11) DEFAULT NULL,
  `all_point` int(11) DEFAULT NULL,
  `all_money` int(11) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
   PRIMARY KEY (`id`)
)";

echo _create_table($create_sql);


$create_sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` varchar(40) DEFAULT NULL,
  `plate_num` varchar(40) DEFAULT NULL,
  `engine_num` varchar(40) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)";

echo _create_table($create_sql);

$create_sql = "CREATE TABLE IF NOT EXISTS `addUser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` varchar(40) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)";

echo _create_table($create_sql);

$create_sql = "CREATE TABLE IF NOT EXISTS `searchUser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_user` varchar(40) DEFAULT NULL,
  `plate_num` varchar(40) DEFAULT NULL,
  `engine_num` varchar(40) DEFAULT NULL,
  `searchtimes` int(11) DEFAULT '0',
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
)";

echo _create_table($create_sql);

//插入数据
$insert_sql = "insert into test_mysql(from_user, account, password, update_time) values('David','860510', 'abcabc', '2013-09-29 17:14:28')";

$res = _insert_data($insert_sql);
if($res == 1){
    echo "插入成功";
}else{
    echo "插入失败";
}

//更新数据
$update_sql = "update test_mysql set account = 860512 where account = 860510";

$res = _update_data($update_sql);
if($res == 1){
    echo "更新成功";
}elseif($res == 0){
    echo "更新失败";
}elseif($res == 2){
    echo "没有行受到影响";
}

//删除数据
/*
$delete_sql = "delete from test_mysql where account = 860512";

$res = _delete_data($delete_sql);
if($res == 1){
    echo "删除成功";
}elseif($res == 0){
    echo "删除失败";
}elseif($res == 2){
    echo "没有该条记录";
}
*/
//检索数据
/*
$select_sql = "select * from test_mysql";

$result = _select_data($select_sql);

while($rows = mysql_fetch_array($result,MYSQL_ASSOC)){

    echo $rows[id]."--".$rows[from_user]."--".$rows[account]."--".$rows[password]."--".$rows[update_time];
    echo "<br />";

}

*/  

$plate_num = 'N9778B';

			$update_sql = "update peccancyBerif set all_items = 11 where plate_num = '$plate_num'";
			$res = _update_data($update_sql);
			if($res == 1){
    			echo "更新成功";
			}elseif($res == 0){
    			echo "更新失败";
			}elseif($res == 2){
  				  echo "没有行受到影响";
			}

/*
//删除表
$drop_sql = "drop table if exists test_mysql";

echo _drop_table($drop_sql);
*/
?>
        </body>
</html>