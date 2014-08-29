<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    	<title>违章信息查询</title>
    </head>
    <body>
<?php
if(isset($_GET['plateNumber']) && isset($_GET['engineNumber'])){
    $plateNumber = $_GET['plateNumber'];
    $engineNumber = $_GET['engineNumber'];
    $uri = "http://so.jtjc.cn/pl";
    // 参数数组
    $data = array (
            'Fzjg' => 'N',
            'Webform' => 'jtjc.cn',
            'WebSite' => 'Index',
            'd' => '02',
            't' => '湘',
            'p' => $plateNumber,
            'cjh' => $engineNumber,
            'btnG' => '违法查询'
    );
     
    $ch = curl_init ();
    // print_r($ch);
    curl_setopt ( $ch, CURLOPT_URL, $uri );
    curl_setopt ( $ch, CURLOPT_POST, 1 );
    curl_setopt ( $ch, CURLOPT_HEADER, 0 );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
    $return = curl_exec ( $ch );
    curl_close ( $ch );
     
    preg_match_all("/<li class='address'><div class='item'><b>(.*?)<\/b><span>(.*?)(<\/span>)*<\/div><\/li>/", $return, $title);
     
    //var_dump($title);
    //echo $return; 
    if(count($title)==4){
        $textArray = $title[1];
        $valueArray = $title[2];
        //var_dump($textArray);
        //echo "<br /><br />";
        //var_dump($valueArray);
        //echo "<br /><br />";
        $textCount = count($textArray);
        $valueCount = count($valueArray);
        $curCount = ($textCount>$valueCount)?$valueCount:$textCount;
        if($curCount<=0){
            return "恭喜，您没有任何违章信息。";
        }
        //echo "count:".$curCount." ".$textCount." ".$valueCount;
        $resultStrArr = "";
        for($i=0;$i<$curCount;$i++){
            $resultStrArr[$i/10] .= ($textArray[$i].$valueArray[$i]." <br />");
        }
        $resultStrArrCount = count($resultStrArr);
        //var_dump($resultStrArr[0]);
        echo "车牌：湘";
        echo $plateNumber;
        echo "违章信息如下：<br />";
            
        for($i=0;$i<$resultStrArrCount;$i++){
            echo "<p>".($i+1).".</p>";
            echo $resultStrArr[$i];
        }
        //$resultStr = $resultStrArr[0];
        //echo $resultStr;
    }else{
        echo "获取信息失败";
    }
	
}else{
	echo "没有违章信息";
}




?>
</body>
</html>