<?php


define("TOKEN","weixin");
require_once './mysql_sae.func.php';

$wechatObj = new wechatCallbackapiTest();
if(isset($_GET['echostr'])){
	$wechatObj->valid();
}else{
	$wechatObj->responseMsg2();
}

class wechatCallbackapiTest
{
	public function valid()
	{
		$echostr = $_GET['echostr'];
		if($this->checkSignature()){
			echo $echostr;
			exit;
		}
	}
	
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];
		
		$token = TOKEN;
		$tmpArr = array($token,$timestamp,$nonce);
		sort($tmpArr);
		$tmpStr = implode($tmpArr);
		$tmpStr = sha1($tmpStr);
		
		if($tmpStr == $signature){
			return true;
		}else{
			return false;
		}
		
	}
	
	public function responseMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		
		if(!empty($postStr)){
			$postObj = simplexml_load_string($postStr,"SimpleXMLElement",LIBXML_NOCDATA);
			$fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
			
			$textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
			if($keyword=="?" || $keyword=="？"){
				$msgType = "text";
				$contentStr = "小助功能请发送如下指令：\n1、查询天气信息：天气+地名，如天气+怀化\n2、查询违章信息：违章+车牌号+发动机后五位，如违章+N12345+12345\n3、绑定车辆信息：绑定+车牌号+发动机后五位，如绑定+N12345+12345\n4、解绑信息：解绑+车牌号，如解绑+N12345\n";
            	$contentStr .="注意：绑定车辆信息之后，小助会定期为您查询违章信息，如有新违章，小助会第一时间通知您。另外绑定了车辆信息的用户可以直接输入：违章，查询违章信息。";
				$resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,$msgType,$contentStr);
				echo $resultStr;
			}else{
				$msgType = "text";
				$contentStr = "暂时不支持[".$keyword."]指令，请输入？查询具体相关指令。";
				$resultStr = sprintf($textTpl,$fromUsername,$toUsername,$time,$msgType,$contentStr);
				echo $resultStr;
			}
		}else{
			echo "";
			exit;
		}
	}
	
	public function responseMsg2()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
	}
	
	//接收事件类型的输入
	private function receiveEvent($object)
    {
        $content = "";
        switch ($object->Event)
        {
            case "subscribe":
                $content = "欢迎关注“怀化路况”的公众帐号。\n小助每月会向您推送违章信息、用车知识和油价信息。\n具体功能请发送如下指令：\n1、查询天气信息：天气+地名，如天气+怀化\n2、查询违章信息：违章+车牌号+发动机后五位，如违章+N12345+12345\n3、绑定车辆信息：绑定+车牌号+发动机后五位，如绑定+N12345+12345\n4、解绑信息：解绑+车牌号，如解绑+N12345\n";
            	$content .="注意：绑定车辆信息之后，小助会定期为您查询违章信息，如有新违章，小助会第一时间通知您。另外绑定了车辆信息的用户可以直接输入：违章，查询违章信息。";
                break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }
	
	//接收Text类型的输入
	private function receiveText($object)
    {
        $resultStr = "";
        $keyword = trim($object->Content);
		if($keyword=="?" || $keyword=="？" || $keyword=="help"){
			$resultStr = $this->help($object);
            //return $helpResult;
		}else{
			$cmdArr = explode("+",$keyword);
			if(trim($cmdArr[0] == '违章')){
                if(count($cmdArr)==3){
					//假如输入的格式为：违章+aaaaaa+12345，则直接查找，无需从数据库进行查询
					$tvObj = new trafficViolationApi();
					$originDataStrzxc = $tvObj->getHHData($cmdArr[1], $cmdArr[2]);
					$originDataArr = $tvObj->prepareDatatmp($originDataStrzxc,$cmdArr[1], $cmdArr[2]);
				   
					//$contentStr = $cmdArr[0].$cmdArr[1];
					//if(strlen($contentStr)<=0){
					//	$contentStr = "没有查找到数据";
					//}
					//$resultStr = $this->transmitText($object, $originDataStrzxc);
					$resultStr = $this->transmitNew($object, $originDataArr);
					//return $resultStr;
				}else if(count($cmdArr)==1){
					//如果只输入:违章，则尝试从数据库加载车牌、发动机号码等信息
                    $flag = $this->getBindingNum($object);
                    if($flag == 1){
						$bindingInfoArr = $this->getBindingInfo($object);
						if(is_array($bindingInfoArr) && count($bindingInfoArr)>0){
							$plateNumber = $bindingInfoArr['plate_num'];
							$engineNumber = $bindingInfoArr['engine_num'];
							$tvObj = new trafficViolationApi();
							$originDataStrzxc = $tvObj->getHHData($plateNumber, $engineNumber);
							$originDataArr = $tvObj->prepareDatatmp($originDataStrzxc,$plateNumber, $engineNumber);
							$resultStr = $this->transmitNew($object, $originDataArr);
                        }else{
						//没有绑定信息
						$contentStr = "当前用户还没有绑定车牌，请先绑定信息，可输入“违章+aaaaaa+12345”指令将账号与车牌进行绑定";
                        $resultStr = $this->transmitText($object, $contentStr);
                        }
                    }elseif($flag > 1){
                        $contentStr = $this->autoBindingInfo($object);
                
                        $resultStr = $this->transmitNews($object, $contentStr);
                     
                    }else{
                        //没有绑定信息
						$contentStr = "当前用户还没有绑定车牌，请先绑定信息，可输入“违章+aaaaaa+12345”指令将账号与车牌进行绑定";
                        $resultStr = $this->transmitText($object, $contentStr);
                    }
				}else{
					//指令错误
					$contentStr = "指令有误，请重新输入，或者输入?查询系统支持的指令";
                    $resultStr = $this->transmitText($object, $contentStr);
				}
			}else if(trim($cmdArr[0] == '天气')){
				//进行天气查询
                $url = "http://apix.sinaapp.com/weather/?appkey=".$object->ToUserName."&city=".urlencode($cmdArr[1]); 
                $output = file_get_contents($url);
                $content = json_decode($output, true);
                if(is_array($content)!=1 || count($content)<=0){
                    $contentStr = "没有城市【".$keyword."】的天气信息，请确认该城市名字是否拼写正确，检查后请重新输入查询。\n或者输入问号?来查询系统可以提供的功能。";
                    $resultStr = $this->transmitText($object, $contentStr);
                }else{
                    $resultStr = $this->transmitNews($object, $content);
                }
                //return $resultStr;
            }else if(trim($cmdArr[0] == '绑定')){
                $contentStr = $this->binding($object, $cmdArr);
                    
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '解绑')){
                $contentStr = $this->unbinding($object, $cmdArr);
                
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '查询绑定')){
                $contentStr = $this->searchbinding($object);
                
                $resultStr = $this->transmitText($object, $contentStr);
                
            }else if(trim($cmdArr[0] == '测试')){
                    //测试多绑定用户违章查询
                //$contentStr = array();
                //$contentStr = $this->autoBindingInfo($object);
                
                //$resultStr = $this->transmitText($object, $contentStr);
                //$resultStr = $this->transmitNews($object, $contentStr);
                
                
            }else{
            	$contentStr = "暂时不支持[".$keyword."]指令。\n正确的指令是“请求+参数”，如违章查询输入：违章+A1234B+12345，天气查询：天气+怀化。";
				$resultStr = $this->transmitText($object, $contentStr);
                //return $resultStr;
            }
		}
        return $resultStr;
		
    }
	
	//简单的帮助函数，可提示用户的输入
	private function help($object)
	{
		$content = "小助功能请发送如下指令：\n1、查询天气信息：天气+地名，如天气+怀化\n2、查询违章信息：违章+车牌号+发动机后五位，如违章+N12345+12345\n3、绑定车辆信息：绑定+车牌号+发动机后五位，如绑定+N12345+12345\n4、解绑信息：解绑+车牌号，如解绑+N12345\n";
        $content .="注意：绑定车辆信息之后，小助会定期为您查询违章信息，如有新违章，小助会第一时间通知您。另外绑定了车辆信息的用户可以直接输入：违章，查询违章信息。";
		return $this->transmitText($object,$content);
	}
	
	//发送简单text类型的信息
	private function transmitText($object, $content)
    {
        $textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
	
	//发送图文消息(多条)
	private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>";
        $item_str = "";
        foreach ($newsArray as $item){
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>
					$item_str</Articles>
					</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($newsArray));
        return $result;
    }
	
	//发送图文消息(单条)
	private function transmitNew($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return;
        }
        $itemTpl = "<item>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					<PicUrl><![CDATA[%s]]></PicUrl>
					<Url><![CDATA[%s]]></Url>
					</item>";
        $item_str = "";
        
        $item_str = sprintf($itemTpl, $newsArray['Title'], $newsArray['Description'], $newsArray['PicUrl'], $newsArray['Url']);
        
        $newsTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[news]]></MsgType>
					<ArticleCount>%s</ArticleCount>
					<Articles>
					$item_str</Articles>
					</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), 1);
        return $result;
    }
    
    //判断账号是否绑定
    private function isBinding($object, $cmdArr)
    {
        //判断是否已经绑定
		$select_sql = "SELECT id from users WHERE plate_num='$cmdArr[1]' and from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
		$rows = mysql_fetch_array($res, MYSQL_ASSOC);
		if($rows[id] <> ''){
        	$user_flag = 'y';  
        }else{
            $user_flag = 'n';
        }
        return $user_flag;
    }
    
    //判断微信号绑定车牌号数
    private function getBindingNum($object)
    {
        //判断是否已经绑定
		$select_sql = "SELECT id from users WHERE from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
        $num = 0;
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
        }
        return $num;
    }
	
	//获取账号的绑定信息
	private function getBindingInfo($object)
	{
		//判断是否已经绑定
		$select_sql = "SELECT * from users WHERE from_user='$object->FromUserName'";
		$res = _select_data($select_sql);
		//仅仅获取第一行数据，如果需要获取所有行，应该循环执行mysql_fetch_array语句
		$rows = mysql_fetch_array($res, MYSQL_ASSOC);
		
        return $rows;
	}
    
    //获取绑定账户信息
    private function autoBindingInfo($object)
    {
        //
        $select_sql = "SELECT * from users WHERE from_user = '$object->FromUserName'";
		$res = _select_data($select_sql);
        //return $res;
        //$rows = mysql_fetch_array($res, MYSQL_ASSOC);
        $num = 1;
        //$arrayarray = array();
        $InfoArr = array();
        $InfoArr[1] = array (
			'Title'=>"车辆违章信息",
			'Description'=>"",
			'PicUrl'=>'',
            'Url'=>''
		);
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
            $plateNumber = $rows['plate_num'];
            $engineNumber = $rows['engine_num'];
            $listObj = new trafficViolationApi();
            //$originContent = $listObj->getHHData($plateNumber, $engineNumber);
            //$InfoArr[$num] = $listObj->prepareDatatmp($originContent, $plateNumber, $engineNumber);
            
            $valueArray = $listObj->getValueData($plateNumber, $engineNumber);
            
            $instoreflag = $listObj->checkDatatmp($valueArray, $plateNumber, $engineNumber);		//将违章信息存入数据库
            
            
            $flagInfo = $listObj->updateDatatmp($valueArray, $plateNumber);
            
            
            if($flagInfo >0){
                $InfoArr[$num] = $listObj->getMultieDatatmp($plateNumber,$engineNumber);
            }else{
                $InfoArr[$num] = "未查到相关信息。";
            }
        }
        
        

        return $InfoArr;
        //return $instoreflag;
    }
    
    //绑定用户
    private function binding($object, $cmdArr)
    {
        $user_flag = $this->isBinding($object, $cmdArr);
        $nowtime=date("Y-m-d G:i:s");
        //$fromuser = $object->FromUserName;
        if($user_flag <> 'y'){
            $insert_sql="INSERT INTO users(from_user, plate_num, engine_num, update_time) VALUES('$object->FromUserName','$cmdArr[1]','$cmdArr[2]','$nowtime')";
        	$res = _insert_data($insert_sql);
        	if($res == 1){
                $ret = "绑定成功";
        	}elseif($res == 0){
            	$ret = "绑定失败";
            }
        }else{
            $ret = "该用户已绑定";
        }
        return $ret;
    }
    
    //解绑用户
    private function unbinding($object, $cmdArr)
    {
        $user_flag = $this->isBinding($object, $cmdArr);
        if($user_flag<>'n'){
            $delete_sql = "delete from users where plate_num = '$cmdArr[1]'";
            $result = _delete_data($delete_sql);
			if($result == 1){
    			$ret = "该车牌已解除绑定";
			}elseif($result == 0){
                $ret = "解绑失败";
			}elseif($result == 2){
   				$ret = "没有该车牌绑定";
            }
        }else{
            $ret = "该用户未绑定";
        }
        return $ret;
    }
    
    //查询绑定情况
    private function searchbinding($object)
    {
        $select_sql = "SELECT * from users WHERE from_user = '$object->FromUserName'";
		$res = _select_data($select_sql);
        //return $res;
        //$rows = mysql_fetch_array($res, MYSQL_ASSOC);
        $num = 0;
        $ret ="您绑定的车牌有以下几个：\n";
        while($rows = mysql_fetch_array($res, MYSQL_ASSOC)){
            $num = $num + 1;
            $ret .= $num;
            $ret .= "、";
            $ret .= $rows['plate_num'];
            $ret .= "\n";
            //$ret = $num + ".  " + $rows['plate_num'] + '\n';
        }
        return $ret;
    }    
	
	//日志记录
	private function logger($log_content)
    {
    
    }
	
}

class trafficViolationApi
{
	public function getHHData($plateNumber,$engineNumber){
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
                $resultStrArr[$i/10] .= ($textArray[$i].$valueArray[$i]." \n");
			}
			//数据过多的话，只返回第一条，其他的放到详情页中
            /*foreach($resultStrArr as $curResultStr){
            	$resultStr .= $curResultStr."\n";
            }*/
            $resultStr = $resultStrArr[0];
			return $resultStr;
		}else{
			return "获取信息失败";
		}
	}
	
	public function prepareDatatmp($content,$plateNumber,$engineNumber){
        $detailUrl = "http://whucsers.sinaapp.com/violationInfo.php?plateNumber=".$plateNumber."&engineNumber=".$engineNumber;
		$dataArr = array (
			'Title'=>'违章信息查询',
			'Description'=>$content,
			'PicUrl'=>'',
            'Url'=>$detailUrl
		);
		return $dataArr; 
	}
    
    //获取valueArray数组
    public function getValueData($plateNumber,$engineNumber){
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
        
        if(count($title)==4){
            $valueArray = $title[2];
        }else{
            $valueArray = "无违章信息";
        }
        return $valueArray;
    }
    
    //存储违章信息进peccancyInfo表
    public function checkDatatmp($valueArray, $plate_num, $engine_num){
        
        $valueNum = count($valueArray);
        $nowtime = date("Y-m-d G:i:s");
        for($i=0; $i<($valueNum/10); $i++){
            //$plate_num = $valueArray[0];
            $office_a = $valueArray[$i*10];
            $place_a = $valueArray[$i*10+1];
       		$actioncode_a = $valueArray[$i*10+2];
            $point_a = $valueArray[$i*10+3];
            $money_a = $valueArray[$i*10+4];
            $action_a = $valueArray[$i*10+5];
            $law_a = $valueArray[$i*10+6];
        	$occurtime_a = $valueArray[$i*10+7];
       		$flag_a = $valueArray[$i*10+8];
            $origin_a = $valueArray[$i*10+9];

        	//判断违章信息是否储存，采用车牌号，违章行为代码，违章时间三值确定
            
            $rows = 0;
        	$select_sql = "select * from peccancyInfo where plate_num='$plate_num' and actioncode='$actioncode_a' and occurtime='$occurtime_a'";

			$result = _select_data($select_sql);
        
        	if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            	//检测更新违章是否缴费信息
            	$ret = 1;  //违章数据已存入数据库
        	}else{
            	//插入数据
            	$insert_sql = "insert into peccancyInfo(plate_num, engine_num, office, place, actioncode, point, money, action, law, occurtime, flag, origin, update_time) values('$plate_num', '$engine_num', '$office_a','$place_a','$actioncode_a','$point_a','$money_a','$action_a', '$law_a','$occurtime_a','$flag_a','$origin_a', '$nowtime')";

				$res = _insert_data($insert_sql);
            	if($res == 1){
                	$ret = 2;  //插入成功
				}else{
                	$ret = 0;  //插入失败
            	}
        	}
        }
        return $ret;
    }
    
    //计算并存储违章概要进peccancyBerif表
    public function updateDatatmp($valueArray, $plate_num){
        
        //$allitems = 0;
        
        $allpoint = 0;
        
        $allmoney = 0;
        
        $valueCount = count($valueArray);
        
        $items = $valueCount/10;   //处罚项数
        
        $nowtime = date("Y-m-d G:i:s");
        

        
        for($i=0; $i<=($valueCount/10); $i++){

            $allpoint = $allpoint + $valueArray[$i*10+3];		//处罚扣分
            $allmoney = $allmoney + $valueArray[$i*10+4];       //处罚金额
            //$allitems = $allitems + 1;						//处罚项数
        }
        
             
        
        $select_sql = "select * from peccancyBerif where plate_num = '$plate_num'";

		$result = _select_data($select_sql);
        
        if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            
        	//更新数据
			$update_sql = "update peccancyBerif set all_items='$items',all_point='$allpoint',all_money='$allmoney' where plate_num='$plate_num'";

			$res = _update_data($update_sql);
			if($res == 1){
                return 1;    //更新成功
			}elseif($res == 0){
                return 0;    //更新失败
			}elseif($res == 2){
                return 2;   //没有行受到影响
			}

        }else{
            $insert_sql = "insert into peccancyBerif(plate_num, all_items, all_point, all_money, update_time) values('$plate_num', '$items', '$allpoint', '$allmoney', '$nowtime')";

			$res = _insert_data($insert_sql);
			if($res == 1){
                return 3;   //插入成功
			}else{

                return -1;   //插入失败
			}
        }
        
    }
    
    //合成违章概要信息
    public function getMultieDatatmp($plateNumber,$engineNumber){
        
        $select_sql = "select * from peccancyBerif where plate_num='$plateNumber'";

		$result = _select_data($select_sql);
        
        $berif = "";
        
        if($rows = mysql_fetch_array($result,MYSQL_ASSOC)){
            $berif .= "车牌号：湘";
            $berif .= $plateNumber;
            $berif .= "\n尚未消除违章";
            $berif .= $rows['all_items'];
            $berif .= "项，共扣分";
            $berif .= $rows['all_point'];
            $berif .= "分，共罚款";
            $berif .= $rows['all_money'];
            $berif .= "元。";
            $berif .="\n点击查看详情。";
        }else{
            $berif .= "车牌号：湘";
            $berif .= $plateNumber;
            $berif .= "\n无违章记录。";
        }
        
        $detailUrl = "http://whucsers.sinaapp.com/violationInfo.php?plateNumber=".$plateNumber."&engineNumber=".$engineNumber;
		$dataArr = array (
			'Title'=>$berif,
			'Description'=>"",
			'PicUrl'=>'',
            'Url'=>$detailUrl
		);
		return $dataArr; 
	}
        
        
}

?>