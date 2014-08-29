<?php
if(true){
	require_once("./mysql_sae.func.php");
    require_once("./simple_html_dom.php");
}
echo "test";

//先检查下参数格式
$cmdArr = array();
$cmdArr[0] = "违章";
$cmdArr[1] = "asdasd";
$cmdArr[2] = "fff45";
$paramCheckobj = new ParamCheckUtil();
$checkResultArray = $paramCheckobj->trafficViolationParamCheck($cmdArr[0], $cmdArr[1], $cmdArr[2]);
if((is_array($checkResultArray)==1) && (count($checkResultArray)==4) && ($checkResultArray[0]==true)){
    //$tvObj = new trafficViolationApi();
    //$originDataStrzxc = $tvObj->getHHData($checkResultArray[2], $checkResultArray[3]);
    //$originDataArr = $tvObj->prepareDatatmp($originDataStrzxc,$checkResultArray[2], $checkResultArray[3]);
    //$resultStr = $this->transmitNew($object, $originDataArr);
    echo "good!";
}else{
    //$contentStr = "命令格式错误，正确的格式为：违章+aaaaaa+12345";
    //$resultStr = $this->transmitText($object, $contentStr);
    echo "bad!";
}

class ParamCheckUtil
{
	//检查输入参数的格式，格式必须为违章+aaaaaa+12345，如果格式正确，则返回里面有四个元素的数组，首个元素标记诶true或者false，表示参数检测的结果
	public function trafficViolationParamCheck($cmd,$plateNumber,$engineNumber){
		$finalParams = array();
		$checkFlag = true;//标记参数检测的结果
		$finalCmd = trim($cmd);
		$finalPlateNumber = trim($plateNumber);
		$finalEngineNumber = trim($engineNumber);
		if(trim($cmd)=="违章"){
			$finalParams[1] = $finalCmd;
		}else{
			$checkFlag = false;
		}
		//车牌号
		$finalPlateNumberLength = strlen($finalPlateNumber);
		if($finalPlateNumberLength==6){
			$finalParams[2] = $finalPlateNumber;
		}else if(($finalPlateNumberLength==9) && strpos($finalPlateNumber,"湘")==0){//中文长度为3
			$finalParams[3] = substr($finalPlateNumber,3);
		}else{
			$checkFlag = false;
		}
		//引擎号
		if(strlen($engineNumber)==5){
			$finalParams[3] = $finalEngineNumber;
		}else{
			$checkFlag = false;
		}
		//将标记的结果填充到返回数组中
		$finalParams[0] = $checkFlag;
		
		return $finalParams;
	}
	
}


?>