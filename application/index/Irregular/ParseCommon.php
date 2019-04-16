<?php
namespace app\index\Irregular;
use think\Db;
//解析不规则的通用模板
class ParseCommon{
	protected $autoCheckFields = false;
	protected $project = "";
	protected $DB_resume = "";
	//返回姓名
	public function getName($CN_ENG_array){
//		$lastName = file_get_contents("Uploads/static/baijiaxing.txt");//获取百家姓
//		$lastName = iconv("gb2312", "utf-8//IGNORE",$lastName);
		//dump($CN_ENG_array);die;
		for($i = 0; $i < count($CN_ENG_array); $i++){
			if(strstr($CN_ENG_array[$i],"姓名") && $CN_ENG_array[$i+1]=="曾用名"){
				$name = $CN_ENG_array[$i+2];
				break;
			}elseif((strstr($CN_ENG_array[$i],"姓名") || strstr($CN_ENG_array[$i],"Name"))&& (strlen($CN_ENG_array[$i+1]) == 6 || strlen($CN_ENG_array[$i+1]) == 9) ){ //姓名为2个或3个汉字
				$name = $CN_ENG_array[$i+1];
				break;
			}else if(strstr($CN_ENG_array[$i],"姓名") && strlen($CN_ENG_array[$i+1]) < 6){ //姓名分开
				$name = $CN_ENG_array[$i+1].$CN_ENG_array[$i+2];
				break;
			}else if($CN_ENG_array[$i] == '姓' && $CN_ENG_array[$i+1]=="名"){
				if(strlen($CN_ENG_array[$i+2]) < 6){
					$name = $CN_ENG_array[$i+2].$CN_ENG_array[$i+3]; //姓名分开
					break;
				}else if (strlen($CN_ENG_array[$i+2]) == 6 || strlen($CN_ENG_array[$i+2]) == 9) {//姓名为2个或3个汉字
					$name = $CN_ENG_array[$i+2];
					break;
				}
			}
		}
		//名字黑名单
		$black_list = Db::table('resume.yp_black_name')->field('id,name,rule_name')->order('id asc')->select();
		$black_list = implode('|',array_column($black_list,'rule_name'));
		$black_list = '/'.$black_list.'/';
		if(preg_match($black_list,$name)){
			$name = null;
		}
		//如果没有找到姓名关键字，提取最前面的2\3个中文字符
		if(!$name){
			foreach($CN_ENG_array as $key=>$value) {
				if($key<30){
//					if(strlen($value) == 3){//如果匹配到一个字符为姓氏则看之后的第一位和第二位是不是一个字
//						//vde($value);
//						//匹配百家姓相似查询
//						$keyWord = substr($value,0,3);
//						//if(!strstr('年月日号姓',$keyWord)) {
//						if(preg_match($black_list,$keyWord)) {
//							$wherename['FName'] = array('like', '%' . $keyWord . '%');
//							$likeLastName = Db::table('sj_fname')->where($wherename)->find();
//							if ($likeLastName) {
//								//如果后面第一位和第二位都是一个字且不是性别则判断姓名为三个字取三个字
//								if (strlen($CN_ENG_array[$key + 1]) == 3 && strlen($CN_ENG_array[$key + 2]) == 3 && $CN_ENG_array[$key + 2] != "男" && $CN_ENG_array[$key + 2] != "女") {
//									$name = $CN_ENG_array[$key] . $CN_ENG_array[$key + 1] . $CN_ENG_array[$key + 2];
//									break;
//								} elseif (strlen($CN_ENG_array[$key + 1]) == 3) {//如果后面第二位不是一个字只取前两个字
//									$name = $CN_ENG_array[$key] . $CN_ENG_array[$key + 1];
//									break;
//								}
//							}
//						}
//					}elseif((strlen($value) == 6 or strlen($value) == 9)) {//如果遇到两个字或三个字且第一个字是姓氏则认为是名字
//						//vde($value);
//						//匹配百家姓相似查询
//						$keyWord = substr($value,0,3);
//						if(!strstr('年月日号姓',$keyWord)){
//							$wherename['FName'] = array('like','%'.$keyWord.'%');
//							$likeLastName = Db::table('sj_fname')->where($wherename)->find();
//							if($likeLastName){
//								$name = $value;
//								break;
//							}
//						}
//					}
					$flist = Db::table('sj_fname')->field('FName')->select();
					$fname = implode('|', array_column($flist, 'FName'));
					$fname = '('. $fname .')';
					$name = array();
					preg_match('/(?<=,|^)'. $fname .'[\x{4e00}-\x{9fa5}]{1,2}(?![\x{4e00}-\x{9fa5}])/u', $value, $name);
					if($name[0] && !preg_match($black_list,$name[0])){
						$name =  $name[0];
						break;
					}
					else
						continue;
				}
			}
		}
		return $name;
	}
	//返回性别 ?
	public function getSex($resume){
		preg_match("/(?:男|女|male|female|Female){1}/u",$resume,$sex);
		//如果没有找到男，默认为男
		if($sex[0] == '女' || $sex[0] == 'female' || $sex[0] == 'Female')
			$ret = '女';
		elseif($sex[0] == '男' || $sex[0] == 'male' || $sex[0] == 'Male')
			$ret = '男';
		if($ret)
			return $ret;
	}
	//获取所在地
	public function getCity($CN_ENG_array, $last_company){
		//vde($CN_ENG_array);
		for($i = 0; $i < count($CN_ENG_array); $i++){
			if(strstr($CN_ENG_array[$i],"意向地区")||strstr($CN_ENG_array[$i],"居住地")||strstr($CN_ENG_array[$i],"所在地")||strstr($CN_ENG_array[$i],"现所在")||strstr($CN_ENG_array[$i],"工作地")||strstr($CN_ENG_array[$i],"所在城市")||strstr($CN_ENG_array[$i],"期望地点")||strstr($CN_ENG_array[$i],"Location")||strstr($CN_ENG_array[$i],"意向城市"))
				if(strstr($CN_ENG_array[$i+1],"籍贯")||strstr($CN_ENG_array[$i+1],"户口")||strstr($CN_ENG_array[$i+1],"居住地")||strstr($CN_ENG_array[$i+1],"所在地")||strstr($CN_ENG_array[$i+1],"工作地")||strstr($CN_ENG_array[$i+1],"所在城市"))
					$city = $CN_ENG_array[$i+2];
				else
					$city = $CN_ENG_array[$i+1];
			elseif(($CN_ENG_array[$i]=="居")&&($CN_ENG_array[$i+1]=="住")&&($CN_ENG_array[$i+2]=="地"))
				$city = $CN_ENG_array[$i+3];
			elseif(($CN_ENG_array[$i]=="现")&&($CN_ENG_array[$i+1]=="居")&&($CN_ENG_array[$i+2]=="地"))
				$city = $CN_ENG_array[$i+3];
			elseif(($CN_ENG_array[$i]=="现")&&($CN_ENG_array[$i+1]=="居")&&($CN_ENG_array[$i+2]=="所"))
				$city = $CN_ENG_array[$i+3];
			if($city){
				//dump($city);
				if($city == "不限"){
					$workCity = "不限";
					$cityId = 1000;
				}else{
					//和数据库城市比对
					//$citylist = M('region')->field('name,id')->order('id desc')->select();
					$citylist = Db::table('sj_region')->field('name,id')->order('id desc')->select();
					foreach($citylist as $key=>$value) {
						if(strstr($city, $value['name'])) {
							$workCity = $value['name'];
							$cityId = $value['id'];
							break;
						}
					}
				}
				break;
			}
		}
		//如果找不到，提取最近公司所在地
		if(!$city){
			//提取数据库已有城市
			//$citylist = M('region')->where('type=2')->field('name,id')->order('id desc')->select();
			$citylist = Db::table('sj_region')->where('type=2')->field('name,id')->order('id desc')->select();
			foreach($citylist as $key=>$value) {
				if(strstr($last_company, $value['name'])) {
					$workCity = $value['name'];
					$cityId = $value['id'];
					break;
				}
			}
		}
		//dump($cityId);
		return $workCity;
	}
	public function parseSalary($strSalary){
		$multiple = 1;
		if(preg_match("/(k|K|千)/u",$strSalary)) $multiple = 1000;
		if(preg_match("/(w|W|万)/u",$strSalary)) $multiple = 10000;
		if(!preg_match("/(?:\/|每|\*)/u",$strSalary)){       //如果没有 每*/ 则认为是最简单的模式
			preg_match("/\d+/",$strSalary,$arrSalary);
			$salary = $arrSalary[0];
		}
		elseif(preg_match("/(?:\/|每|\*)?(?:\d+|月|年|M|m|Y|y)/u",$strSalary)){    //存在运算符
			if(!preg_match("/\*/",$strSalary)) {
				$strSalary = preg_replace("/(?:\/|每)(?:\d+|月|M|m|薪)/u", '*12', $strSalary);  //把月薪转换成年薪
				$strSalary = preg_replace("/(?:\/|每)(?:\d+|年|Y|y)/u", '*1', $strSalary);
			}
			//支持小数点
			preg_match_all("/(?:\d{1,2}\.\d)|(?:\d{2,}|\*|1)/",$strSalary,$arrSalary);
			foreach($arrSalary[0] as $k1=>$v1){
				if($v1=="*")
					$salary = $arrSalary[0][$k1-1]*$arrSalary[0][$k1+1];  //运算
			}
		}
		$salary *= $multiple;
		if($salary<=1000)  //如果最后小于1000则认为是k
			$salary *= 1000;
		if($salary<=100000)  //如果最后小于10W则认为是月薪
			$salary *= 12;
		return $salary/1000; //转为k
	}
	//获取目前\期望年薪 不支持加，支持乘
	public function getSalary($details){
		$details =  preg_replace("/\s/","",$details);
		preg_match_all("/(?:薪|收入)?(现有|当前|目前|期望|薪资).?(?:薪|收入)?.{0,36}?([\d.]+(?:万|W|w|千|K|k)?(?:\/|每)?(?:月|年|M|m|Y|y)?(.?\*\d*)?|待议|面议|面谈)/u",$details,$details);
		$temp[] = $details[0][0];
		$temp[] = $details[0][1];
		$temp[] = $details[0][2];
		foreach($temp as $k=>$v){
			if(!$salary['now'] && preg_match("/(?:薪|收入)?(?:现有|当前|目前|薪资).?(?:薪|收入)?.*?/u",$v)&&preg_match("/薪|收入|资/u",$v))    {//匹配当前薪资
				if(!preg_match("/面议|待议/u", $v))       //如果存在面议字样 就认为不是当前
					//$salary['now'] = $this->parseSalary($v);
					$salary['now'] = $v;
			}
			elseif(preg_match("/(?:薪|收入)?期望.?(?:薪|收入)?.*?/u",$v)&&preg_match("/薪|收入|资/u",$v)) {//匹配期望薪资
				if(preg_match("/面议|待议|面谈/u", $v,$out))       //如果存在面议字样 就认为是面议
					$salary['hope'] = $out[0];
				//else $salary['hope'] = $this->parseSalary($v);
				else $salary['hope'] = $v;
				break;
			}
		}
		return $salary;
	}
	//获取手机或电话
	public function getphone($details){
		//$mobile = "/13[0-9]{1}[0-9]{8}[\D*]|15[0-9]{1}[0-9]{8}[\D*]|17[0-9]{1}[0-9]{8}[\D*]|18[0-9]{1}[0-9]{8}[\D*]/";
		$mobile = "/13[0-9]{1}[0-9]{8}(?!\d)|15[0-9]{1}[0-9]{8}(?!\d)|17[0-9]{1}[0-9]{8}(?!\d)|18[0-9]{1}[0-9]{8}(?!\d)/";
		//$telephone = "/[0-9]{3,4}[-][0-9]{7,8}(?!\d)/"; 不需要固话
		preg_match_all("/([\x{4e00}-\x{9fa5}]|[0-9]|[-])+/u",$details,$arr);
		$num = count($arr[0]);
		for($i=0;$i<$num;$i++){
			$td_str = preg_replace('/\s|　|-| /','',$arr[0][$i]);
			if(preg_match($mobile,$td_str,$phoneArr)) {
				$phone = $phoneArr[0];
				break;
			}
		}
		return $phone;
	}
	//获取邮箱
	public function getemail($details){
		$mail = "/^(\w)+(\.\w+)*@(\w)+((\.\w+)+)/";
		preg_match_all("/([\x{4e00}-\x{9fa5}]|[a-zA-Z0-9]|[_]|[@.])+/u",$details,$arr);
		//preg_match_all("/\w[-\w.+]*@([A-Za-z0-9][-A-Za-z0-9]+\.)+[A-Za-z]{2,14}/u",$details,$arr);
		$num = count($arr[0]);
		for($i=0;$i<$num;$i++){
			if(preg_match_all($mail,$arr[0][$i],$emailTemp)) {
				$email = $emailTemp[0][0];
				break;
			}
		}
		return $email;
	}
	public function getBirth($details){
		$temp = preg_replace('/\s/',' ',$details);
		$temp = preg_replace('/nbsp/','',$temp);
		$regular = "/[0-9]{2}.{0,2}岁/u";
		preg_match($regular,$temp,$match);
		preg_match_all("/([\x{4e00}-\x{9fa5}]|[0-9]|[a-zA-Z])+/u", $temp, $arr);
		$num = count($arr[0]);
		$Year = date('Y');
		for ($i = 0; $i < $num; $i++) {
			if (strstr($arr[0][$i], "出生日期")||strstr($arr[0][$i], "生日")) {
				$year = $arr[0][$i + 1];
				if(preg_match("/\d/",$year)) {
					$len = strlen($year);
					if ($len > 4) {
						if (strstr($year, "岁")) {
							$year = str_replace('岁', '', $year);
							$birth = $Year - $year;
						} else {
							$year = substr($year, 0, 4);
							$birth = $year;
						}
					} else {
						//出生**：30
						if($len == 2 && $year < 60)
							$birth = $Year - $year;
						else
							$birth = $year;
					}
				}
			} elseif (strstr($arr[0][$i], "出生年")) {
				$year = $arr[0][$i + 1];
				if(preg_match("/\d/",$year)) {
					$len = strlen($year);
					if ($len > 4) {
						if (strstr($year, "年")) {
							$year = str_replace('年', '', $year);
							$year = substr($year, 0, 4);
							$birth = $year;
							break;
						}elseif (strstr($year, "岁")) {
							$year = str_replace('岁', '', $year);
							$birth = $Year - $year;
							break;
						}  else {
							$year = substr($year, 0, 4);
							$birth = $year;
							break;
						}
					} else {
						//出生**：30
						if($len == 2 && $year < 60)
							$birth = $Year - $year;
						else
							$birth = $year;
						break;
					}
				}
			}elseif (strstr($arr[0][$i], "年龄") || strstr($arr[0][$i], "年纪") || strstr($arr[0][$i], "Age")) {
				if(preg_match('/\d+/',$arr[0][$i])){
					preg_match('/\d+/',$arr[0][$i],$age_num);
					$birth = $Year - $age_num[0];
					break;
				}else{
					$year_old = $arr[0][$i + 1];
					if(preg_match("/\d/",$year_old)) {
						if (strstr($year_old, "年")) {
							$year = str_replace('年', '', $year_old);
							$year = substr($year, 0, 4);
							$birth = $year;
							break;
						} elseif (strstr($year_old, "岁")) {
							$year = str_replace('岁', '', $year_old);
							$birth = $Year - $year;
							break;
						} else {
							//年龄：1987.3
							if(intval($arr[0][$i + 1]) > 1960)
								$birth = $arr[0][$i + 1];
							else
								$birth = $Year - $arr[0][$i + 1];
							break;
						}
					}
				}
			} elseif ((strstr($arr[0][$i], "年") && strstr($arr[0][$i + 1], "纪")) || (strstr($arr[0][$i], "年") && strstr($arr[0][$i + 1], "龄"))) {
				$year_old = $arr[0][$i + 2];
				if(preg_match("/\d/",$year_old)){
					if (strstr($year_old, "年")) {
						$year = str_replace('年', '', $year_old);
						$year = substr($year, 0, 4);
						$birth = $year;
						break;
					} elseif (strstr($year_old, "岁")) {
						$year = str_replace('岁', '', $year_old);
						$birth = $Year - $year;
						break;
					} else {
						//年龄：1987.3
						if(intval($arr[0][$i + 2]) > 1960)
							$birth = $arr[0][$i + 2];
						else
							$birth = $Year - $arr[0][$i + 2];
						break;
					}
				}
			}
		}
		if(!$birth&&$match) {
			$birth = substr($match[0], 0, 2);
			$birth = date("Y") - intval($birth);
			return mb_convert_encoding($birth, 'UTF-8', 'UTF-8');
		}
		return mb_convert_encoding($birth, 'UTF-8', 'UTF-8');
	}
	public function getWorkExperiences($details){
		$details =  preg_replace("/\s/"," ",$details);
		$details = str_replace("&nbsp;"," ",$details);
		//工作经历和教育经历所在的位置
		$workExperiencesFromPattern = '/工作经历|工作经验/';
		preg_match_all($workExperiencesFromPattern, $details, $matches1,PREG_OFFSET_CAPTURE);
		$eduExperiencesFromPattern = '/教育经历|教育背景|教育情况/';
		preg_match_all($eduExperiencesFromPattern, $details, $matches2,PREG_OFFSET_CAPTURE);
		$projectExperiencesFromPattern = '/项目经历|项目经验/';
		preg_match_all($projectExperiencesFromPattern, $details, $matches3,PREG_OFFSET_CAPTURE);
		$workExperiencesFrom = $matches1[0][0][1];
		$eduExperiencesFrom = $matches2[0][0][1];
		$projectExperiencesFrom = $matches3[0][0][1];
		if($workExperiencesFrom>0 && $eduExperiencesFrom>0){
			if($workExperiencesFrom>$eduExperiencesFrom){//教育经历在工作经历前面
				$eduExperiencesStr = substr($details,$eduExperiencesFrom,$workExperiencesFrom-$eduExperiencesFrom);
				$workExperiencesStr = substr($details,$workExperiencesFrom);
			}else{
				if($projectExperiencesFrom>$eduExperiencesFrom){//教育经历在工作经历后面但是在项目经验前面
					$workExperiencesStr = substr($details,$workExperiencesFrom,$eduExperiencesFrom-$workExperiencesFrom);
					$eduExperiencesStr = substr($details,$eduExperiencesFrom,$projectExperiencesFrom-$eduExperiencesFrom);
					$projectExperiencesStr = substr($details,$projectExperiencesFrom);
					$workExperiencesStr = $workExperiencesStr.',项目经验：'.$projectExperiencesStr;
				}else{//教育经历在工作经历后面
					$workExperiencesStr = substr($details,$workExperiencesFrom,$eduExperiencesFrom-$workExperiencesFrom);
					$eduExperiencesStr = substr($details,$eduExperiencesFrom);
				}
			}
		}
		//标准2016*01
		//$yeaymonth = "/(?:19[7-9][0-9]|20[0-1][0-9])\D?(?:\d{1,2})?/u";
		//支持年份带空格
		$date = "(?:19[7-9][0-9]|20[0-2][0-9])\D{0,2}(?:\d{0,2}\D?)?";
		//$date = "(?:19[7-9][0-9]|20[0-1][0-9])\s*\D?\s*(?:\d{1,2}\s*\D?\s*)?";
		$startDate = "/" . $date;
		$interval = "\s*(?:-|—|至|–|－|~)*\s*";
		$endDate = "(?:" . $date . "|至今|现在)";
		$content = ".+?";
		$nextStartDate = "(?=(". $date;
		$nextEndDate = $endDate . ")|(?:$))/u";
		$match =  $startDate . $interval . $endDate . $content. $nextStartDate . $interval . $nextEndDate;
		if($workExperiencesStr && $eduExperiencesStr){
			preg_match_all($match,$workExperiencesStr,$workExperiencesArr);
			if($workExperiencesArr){
				foreach($workExperiencesArr[0] as $key=>$value){
					$workExperiences[]['content'] = $value;
				}
			}
			//preg_match_all("/(?:[\x{4e00}-\x{9fa5}])+/u",$resume_content,$CN_ENG_array);
			$eduExperiencesStr = preg_replace('/教育经历|教育背景|教育情况/',' ',$eduExperiencesStr);
			preg_match($match,$eduExperiencesStr,$eduExperiencesArr1,PREG_OFFSET_CAPTURE);
			$aheadStr = mb_substr($eduExperiencesStr,0,$eduExperiencesArr1[0][1]);
			$aheadSchool = $this->getSchool($aheadStr);
			preg_match_all($match,$eduExperiencesStr,$eduExperiencesArr);
			if($eduExperiencesArr){
				foreach($eduExperiencesArr[0] as $key=>$value){
					if(!preg_match("/(?:[\x{4e00}-\x{9fa5}])+/u",$value)){
						continue;
					}else{
						$education[] = $value;
					}
				}
			}
			if($aheadSchool){
				$education[0] = $aheadSchool.','.$education[0];
			}
			//vde($education);
		}else{
			//得到工作经历列表
			preg_match_all($match,$details,$detail1);
			//preg_match_all("/([\x{4e00}-\x{9fa5}]|[0-9]|[.]|[\/]|[-]|[\（]|[\）])+/u",$details,$arr);
			$workExperiencesList = $detail1[0];
			//提取教育经历
			$school = array("本科","学士","专科","中专","统招","大专","硕士","研究生","博士","MBA","EMBA","学位","大学");
			for($i=0;$i<count($workExperiencesList);$i++){
				$is_education = false;
				foreach($school as $key=>$value){
					if(strstr($workExperiencesList[$i],$value)){
						$education[] = $workExperiencesList[$i];
						$is_education = true;
						break;
					}
				}
				if(!$is_education){
					$workExperiences[]['content'] = $workExperiencesList[$i];
					//$workExperiences[]['duty'] = $workExperiencesList[$i];
				}
			}
		}
		//合并项目经历到工作经历
		$dealexperiences = $this->dealExperiences($workExperiences);
		for($i=0;$i<count($dealexperiences);$i++){
			if($dealexperiences[$i]){
				//判断公司是在年份的前面还是后面
				$content = $dealexperiences[$i]['content'];
				//提取工作职责和工作描述
				$Partation = new PartitionParse();
				$Partation->experienceDetail($dealexperiences[$i]);
				//如果工作经历长度大于120个汉字，且没有找到公司名，就去前面找公司名  ”2014年09月-2016年01月      平安好房（上海）电子商务有限公司“
				$content_front = mb_substr($content,0,120,'utf-8');
				$companyName = $this->getCompany($content_front,1);
				//当前工作经历前面没有找到公司名，在前面字符串里找公司名，如果找到，基本可以认为公司名在年份前面
				if(!$companyName) {
					$lastlength = 180;
					if($i == 0){
						//如果是第一段工作经历，在整个字符串定位
						$start = strpos($details, $content);
						if($start > $lastlength)
							$laststr = substr($details, $start - $lastlength, $lastlength);
					}
					else {
						//在上一段工作经历最后找公司名
						$lastcontent = $dealexperiences[$i-1]['content'];
						$strlen = mb_strlen($lastcontent,'utf-8');
						if($strlen  > $lastlength)
							$laststr = mb_substr($lastcontent,$strlen - $lastlength/2, $lastlength/2,'utf-8');
					}
					$companyName = $this->getCompany($laststr,2);
					//如果找到，将公司放在年份后面
					/*if($companyName){
						$match =  $startDate . $interval . $endDate ."/u";
						preg_match_all($match,$content,$dateStr);
						strpos($content, $dateStr, $start);
						$dealexperiences[$i] = strstr($content, 0, $start) .  $companyName . strstr($content, $start);
					}	*/
				}
				$dealexperiences[$i]['company'] = str_replace('至今','',$companyName);
				//去掉公司名称,职位经常和公司搞混
				$strNoCompany=str_replace($companyName,'',$dealexperiences[$i]);
				$dealexperiences[$i]['position'] = $this->getPosition($strNoCompany);
			}
		}
		return array($dealexperiences,$education);
	}
	//合并工作经历
	public function dealExperiences($experiences){
//		$date = "(?:19[7-9][0-9]|20[0-1][0-9])\D+(?:\d{1,2}\D?)?";
//		$endDate = "(?:" . $date . "|至今|现在)";
//		//给工作经历时间进行排序
//		foreach($experiences as $k=>$v){
//			preg_match_all("/".$endDate."/u",$v['content'],$experienceTime);
//			$times[$k] =$experienceTime;
//			foreach($times[$k] as $k1=>$v1){
//				foreach($v1 as $k2=>$v2){
//					if(preg_match("/.*至今|现在.*/u",$v2)){
//						$times[$k][$k1][$k2] = date("Y/m/d h:i:s",2147483647);
//					}
//				}
//			}
//			$starttime[$k] = $times[$k][$k1][0];
//			$endtime[$k] = $times[$k][$k1][1];
//		}
//		$year = "/\d{4}/";
//		$month = "/\d{1,2}/";
//		for ($i = 0; $i < count($experiences); $i++) {
//			//得到4位数字
//			preg_match_all($year, $starttime[$i], $startyeardate);
//			$startyear[$i] = $startyeardate;
//			//从第四位得到1或2位数字
//			preg_match_all($month, substr($starttime[$i], 4), $startmonthdate);
//			$startmonth[$i] = $startmonthdate;
//			if(strlen($startmonth[$i][0][0])==1){
//				$startmonth[$i][0][0] = "0".$startmonth[$i][0][0];
//			}
//			preg_match_all($year, $endtime[$i], $endyeardate);
//			$endyear[$i] = $endyeardate;
//			preg_match_all($month, substr($endtime[$i], 4), $endmonthdate);
//			$endmonth[$i] = $endmonthdate;
//			if(strlen($endmonth[$i][0][0])==1){
//				$endmonth[$i][0][0] = "0".$endmonth[$i][0][0];
//			}
//			$experiences[$i]['start_time'] = strtotime($startyear[$i][0][0] .'/'. $startmonth[$i][0][0].'/01');
//			$experiences[$i]['end_time'] = strtotime($endyear[$i][0][0] .'/'. $endmonth[$i][0][0].'/01');
//		}
		$Partation = new PartitionParse();
		$Partation->fundTime($experiences);
		$num = count($experiences);
		for ($k = 1; $k < $num; $k++) {
			for ($j = 0; $j < $k; $j++) {
				if ($experiences[$j]) {
					if (($experiences[$k]['start_time'] >= $experiences[$j]['start_time']) && ($experiences[$k]['end_time'] <= $experiences[$j]['end_time'])) {
						//$experiences[$j]['projectExperiences'][] = $experiences[$k]['content'];
						$projectExperiences[] = $experiences[$k]['content'];
						unset($experiences[$k]);
					}
				}
			}
		}
		if($projectExperiences){
			$this->project = $projectExperiences;
		}
		$experiences = array_values($experiences);
		return $experiences;
	}
	//获取工作过的公司,暂不支持英文公司名
	/**
	 * @param $workExperience 文本内容
	 * @param $type 是否再次搜索（1第一次在工作经历中查找 2 第二次在工作经历之前查找）
	 * @return mixed
	 */
	public function getCompany($workExperience,$type){
		//支持**(上海)**有限公司
		$companyName = "/(?:(?:[\x{4e00}-\x{9fa5}]){2,20}(?:\(|\（)(?:[\x{4e00}-\x{9fa5}]){2,4}(?:\)|\）)(?:[\x{4e00}-\x{9fa5}]){0,4}有限公司)/u";
		preg_match_all($companyName,$workExperience,$company);
		if($company[0]){
			if($type==1){
				return $company[0][0];
			}else{
				return $company[0][count($company[0])-1];
			}
		}
		//提取数据库已有公司
		$companyName = '';
		//$companylist = M('real_estate')->field('name')->select();
		$companylist = Db::table('sj_company_standard')->field('name')->select();
		foreach($companylist as $key=>$value)
			$companyName = $companyName . $value['name'] . "|";
		//把最后一个|改为)
		$companyName[strlen($companyName) -1] = ")";
		$companyName = "/(?:".$companyName."/u";
		preg_match_all($companyName,$workExperience,$company);
		if($company[0]){
			if($type==1){
				return $company[0][0];
			}else{
				return $company[0][count($company[0])-1];
			}
		}
		$companyNameEnd = "(?:有限公司|集团|地产|置地|科技|网络|金融|资本|基金|证券|人寿|信托|研究所|移动|联通|电信|银行|网|中心|研究院|电视台)";
		$companyName = "/(?:[\x{4e00}-\x{9fa5}]){2,20}" . $companyNameEnd ."/u";
		preg_match_all($companyName,$workExperience,$company);
		if($company[0]){
			if($type==1){
				return $company[0][0];
			}else{
				return $company[0][count($company[0])-1];
			}
		}
	}
	//获取职位
	public function getPosition($workExperiences){
		$positionName = "(?:总裁|助理|总监|主任|经理|师|主管|员|负责人|副总|总工|开发|美工|顾问|策划|行长|工程师|管理|Leader|leader|DBA|CTO)";
		$positionName = "/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z]){0,10}".$positionName."$/u";
		preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$workExperiences['content'],$contentArr);
		foreach($contentArr[0] as $key=>$value){
			if(preg_match($positionName, $value, $position))
				return $position[0];
		}
//		if(!$position[0]){
//			for($i=0;$i<count($workExperiences['projectExperiences']);$i++){
//				preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$workExperiences['projectExperiences'][$i],$contentArr);
//				//preg_match("/[\x{4e00}-\x{9fa5}]*".$positionName."/u", $workExperiences['projectExperiences'][0], $position);
//				foreach($contentArr[0] as $key=>$value){
//					if(preg_match($positionName, $value, $position))
//						return $position[0];
//				}
//			}
//		}
	}
	//提取学校
	public function getSchool($details){
		$details = preg_replace('/大学/','大学 ',$details);
		$details = preg_replace('/学院/','学院 ',$details);
		preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$details,$educationArr);
		$tmp_school = "/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+(?:学院|大学)$/u";
		for($i=0;$i<count($educationArr[0]);$i++){
			if(preg_match($tmp_school,$educationArr[0][$i])) {
				$school = $educationArr[0][$i];
				if(substr($school,0,3)=='年'||substr($school,0,3)=='月')
					$school = substr($school,3);
				break;
			}
		}
		return str_replace('至今','',$school);
	}
	public function getFirstTopDegree($tmpDegree){
		foreach($tmpDegree as $k=>$v){
			if($v == "大专"){
				$firstDegree = "大专";
				$firstDegree = "1";
				break;
			}elseif($v == "本科" || $v == "学位" || $v == "MBA" || $v == "硕士"){
				$firstDegree = "本科";
				$firstDegree = "2";
			}
		}
		if($firstDegree)
			$education['firstDegree'] = $firstDegree;
		foreach($tmpDegree as $k=>$v){
			if($v == "MBA" || $v == "EMBA"){
				//$topDegree = "MBA";
				$topDegree = "6";
				break;
			}
			if($v == "博士后")
				//$topDegree = "博士后";
				$topDegree = "5";
			if($v == "博士" && $topDegree != "博士后")
				//$topDegree = "博士";
				$topDegree = "4";
			if($v == "硕士" && $topDegree != "博士后"&& $topDegree != "博士")
				//$topDegree = "硕士";
				$topDegree = "3";
			if(($topDegree == "大专" || !$topDegree) && ($v == "本科" || $v == "学位"))
				//$topDegree = "本科";
				$topDegree = "2";
			if($v == "大专")
				//$topDegree = "大专";
				$topDegree = "1";
			else
				$topDegree = "0";
		}
		if($topDegree)
			$education['topDegree'] = $topDegree;
		return $education;
	}
	public function getEducationInfo($educationExperiences,$content){
		//学校提取
		//vde($educationExperiences);
		foreach($educationExperiences as $key=>$value){
			$tmpSchool = $this->getSchool($value);
			if($tmpSchool)
				break;
		}
		$school = $tmpSchool;
		if(!$school){
			$school = $this->getSchool($content);
		}
		$education['school'] = $school;
		//专业提取
		foreach($educationExperiences as $key=>$value){
			$tmpMajor = $this->getMajor($value);
			if($tmpMajor)
				break;
		}
		$major = $tmpMajor;
		//如果在教育经历没找到专业，就在全文搜索
		if(!$major){
			if($school){//如果在教育经历没找到专业而且存在学校，就在学校位置从前60位后的200位内部查找
				$schoolLocation = mb_strpos($content,$school);//找到学校的位置
				if($schoolLocation>60){
					$educcationContent = mb_substr($content,$schoolLocation-60,200,'utf-8');
				}else{
					$educcationContent = mb_substr($content,0,200,'utf-8');
				}
				//iconv("GB2312", "UTF-8//IGNORE", $educcationContent);
				//var_dump($educcationContent);
				$major = $this->getMajor($educcationContent);
			}else{//如果不存在学校全文搜索
				$major = $this->getMajor($content);
			}
		}
		if(!$school&&preg_match("/.+(大学|学院)/u",$major)){
			preg_match("/.+(大学|学院)/u",$major,$MajorSchool);
			//vde($MajorSchool);
			$education['school'] = $MajorSchool[0];
		}
		$education['major'] = $major;
		//学历提取
		foreach($educationExperiences as $key=>$value){
			$tmpDegree[] = $this->getDegree($value);
		}
		$degree = $this->getFirstTopDegree($tmpDegree);
		//如果教育经历找不到，就全文搜索
		if(!$degree){
			$tmpDegree[] = $this->getDegree($content);
			$degree = $this->getFirstTopDegree($tmpDegree);
			if(!$degree['topDegree'])
				$degree['topDegree'] = $degree['firstDegree'];
		}
		$education['topDegree'] = $degree['topDegree'];
		$education['firstDegree'] = $degree['firstDegree'];
		return $education;
	}
	//得到专业
	public function getMajor($details){
		//屏蔽汉字年、至今和月
		$details = str_replace("年","/",$details);
		$details = str_replace("月","/",$details);
		$details = str_replace("至今","/",$details);
		$details = str_replace("非统招","/",$details);
		$details = str_replace("统招","/",$details);
		$details = str_replace("MBA","工商管理",$details);//专业提取暂不支持英文
		$details = str_replace("mba","工商管理",$details);
		$details = str_replace("MB A","工商管理",$details);//372专业出现问题
		$details = str_replace("mb a","工商管理",$details);
		preg_match_all("/[\x{4e00}-\x{9fa5}]+/u",$details,$educationArr);
		$num = count($educationArr[0]);
		//dump($educationArr);
		for($i=0;$i<$num;$i++){//从前向后匹配避免出现学校在专业后面
			//for($i=$num-1;$i>=0;$i--){//从后向前匹配避免出现专业在（所学专业）（学校专业）后面
			if(preg_match("/^专业(?:名称)?$/u",$educationArr[0][$i])||preg_match("/^所学专业$/u",$educationArr[0][$i])||preg_match("/^学校专业$/u",$educationArr[0][$i])){
				$major = $educationArr[0][$i+1];
				if($major)
					break;
			}elseif(preg_match("/.+专业$/u",$educationArr[0][$i])&&!preg_match("/^所学专业$/u",$educationArr[0][$i])&&!preg_match("/^学校专业$/u",$educationArr[0][$i])){
				$major = $educationArr[0][$i];
				//dump($educationArr[0][$i]);
				if($major)
					break;
			}elseif(preg_match("/.+学院$|大学$/u",$educationArr[0][$i])){
				if(preg_match("/.+学院$|大学$/u",$educationArr[0][$i+1])||preg_match("/^所学专业$/u",$educationArr[0][$i+1])||preg_match("/^学校专业$/u",$educationArr[0][$i+1])||preg_match("/^专业$/u",$educationArr[0][$i+1])||preg_match("/.*分校$/u",$educationArr[0][$i+1])||preg_match("/^专业(?:名称)?$/u",$educationArr[0][$i+1])){//大学后面不能跟**学院
					$tempmajor = $educationArr[0][$i+2];
					if($tempmajor&&$tempmajor!="学历"&&$tempmajor!="本科"&&$tempmajor!="硕士"&&$tempmajor!="专科"){
						$major = $tempmajor;
						break;
					}elseif($tempmajor=="本科"||$tempmajor=="硕士"||$tempmajor=="专科"){
						$major = $educationArr[0][$i+3];
						break;
					}
				}else{
					$tempmajor = $educationArr[0][$i+1];
					if($tempmajor&&$tempmajor!="学历"&&$tempmajor!="本科"&&$tempmajor!="硕士"&&$tempmajor!="专科"){
						$major = $tempmajor;
						break;
					}elseif($tempmajor=="本科"||$tempmajor=="硕士"||$tempmajor=="专科"){
						$major = $educationArr[0][$i+2];
						break;
					}
				}
			}
		}
		/*if(!$major){//学校在日期前面，猜测日期后面为专业
			$major = $educationArr[0][0];
		}*/
		return $major;
	}
	public function getDegree($details){
		$degreeMatch1 = "/.*专科|大专.*/u";
		$degreeMatch2 = "/.*本科|学士|学位.*/u";
		$degreeMatch3 = "/.*硕士|研究生.*/u";
		$degreeMatch4 = "/.*博士.*/u";
		$degreeMatch5 = "/.*博士后.*/u";
		$degreeMatch6 = "/.*EMBA|MBA|工商管理硕士.*/u";
		preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$details,$educationArr);
		for($i=0;$i<count($educationArr[0]);$i++){
			if(preg_match($degreeMatch1,$educationArr[0][$i])){
				$Degree = "大专";
			}elseif(preg_match($degreeMatch2,$educationArr[0][$i])){
				$Degree = "本科";
			}elseif(preg_match($degreeMatch3,$educationArr[0][$i])){
				$Degree = "硕士";
			}elseif(preg_match($degreeMatch4,$educationArr[0][$i])){
				$Degree = "博士";
			}elseif(preg_match($degreeMatch5,$educationArr[0][$i])){
				$Degree = "博士后";
			}elseif(preg_match($degreeMatch6,$educationArr[0][$i])){
				$Degree = "MBA";
			}
		}
		return $Degree;
	}
	/**
	 * @param $educationExperiences
	 * @param $educationExperiences
	 * @return mixed
	 * for education
	 */
	public function education($educationExperiences){
		foreach($educationExperiences as $key=>$value){
			$school = null;
			$educationExp[$key]['content'] = $value;
			$school = $this->getSchool($value);
			if(!$school && $key>0){//如果没有找到学校则去上一段经历的后面30个字符串查找
				$aheadStr = mb_substr($educationExperiences[$key-1],count($educationExperiences[$key-1])-10);
				$educationExp[$key]['content'] = $aheadStr.','.$value;
				$school = $this->getSchool($aheadStr.','.$value);
			}
			$educationExp[$key]['school'] = $school;
			$educationExp[$key]['major'] = $this->getMajor($value);
			$educationExp[$key]['degree'] = $this->getDegree($value);
			//判断学校是985还是211
			if($educationExp[$key]['school']) {
				$where['fullname'] = array('like', '%' . $educationExp[$key]['school'] . '%');
				$schoolCategory = Db::table('sj_school')->where($where)->find();
				if ($schoolCategory['985'] == 1) {
					$educationExp[$key]['is985'] = true;
				} elseif ($schoolCategory['985'] != 1 && $schoolCategory['211'] == 1) {
					$educationExp[$key]['is211'] = true;
				}
			}
		}
		$partitionParse = new PartitionParse();
		$partitionParse->fundTime($educationExp);

		return $educationExp;
	}
	public function project($projectExperiences){
		foreach($projectExperiences as $key=>$value){
			$projectExp[$key]['description'] = $value;
			$projectExp[$key]['content'] = $value;
			$projectExp[$key]['position'] = $this->getPosition($value);
		}
		$partitionParse = new PartitionParse();
		$partitionParse->fundTime($projectExp);
		return $projectExp;
	}
	/**去除JSCSS标签
	 * @param $str
	 * @return mixed
	 */
	public function removeJsCss($document){
		$search = array ("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
				"'<style[^>]*?>.*?</style>'si", // 去掉 css
				"'<[/!]*?[^<>]*?>'si", // 去掉 HTML 标记
				"'<!--[/!]*?[^<>]*?>'si", // 去掉 注释标记
				"'([rn])[s]+'", // 去掉空白字符
				"'&(quot|#34);'i", // 替换 HTML 实体
				"'&(amp|#38);'i",
				"'&(lt|#60);'i",
				"'&(gt|#62);'i",
				"'&(nbsp|#160);'i",
				"'&(iexcl|#161);'i",
				"'&(cent|#162);'i",
				"'&(pound|#163);'i",
				"'&(copy|#169);'i",
				"'&#(d+);'e"); // 作为 PHP 代码运行

		$replace = array (" ",
				" ",
				" ",
				" ",
				"\1",
				"\"",
				"&",
				"<",
				">",
				" ",
				chr(161),
				chr(162),
				chr(163),
				chr(169),
				"chr(\1)");
		$conent = preg_replace($search, $replace, $document);
		//将全角空格(E38080)和UTF8空格(C2A0)替换成半角空方
		//$conent = str_replace(array(chr(194).chr(160),'　'),' ',$conent);
		$conent = preg_replace('/\s+/',' ',$conent);
		$conent = trim($conent);
		return $conent;
	}
	//预处理
	public function preprocess($content){
		$content = preg_replace('/姓名/',' 姓名 ',$content);
		$content = preg_replace('/性别/',' 性别 ',$content);
		return $content;
	}
	public function parse($origin_resume_content){
		//$resume_content = $this->removeJsCss($resume_content);
		$resume_content = preg_replace('/\s/',' ',$origin_resume_content);
		$resume_content = HtmlToText($resume_content);
		//剥去简历中的 HTML 标签, 还可以改进
		//$resume_content = strip_tags($resume_content);
		$resume_content = preg_replace('/<[^>]*>/','',$resume_content);
		//将&nbsp;替换为空格
		$resume_content =  str_replace("&nbsp;"," ",$resume_content);
		$resume_content = $this->preprocess($resume_content);
		//提取中文数组
		//preg_match_all("/(?:[\x{4e00}-\x{9fa5}]|[a-zA-Z])+/u",$resume_content,$CN_ENG_array);
		preg_match_all("/(?:[\x{4e00}-\x{9fa5}])+/u",$resume_content,$CN_ENG_array);
		if(!$CN_ENG_array){
			preg_match_all("/(?:[\x{4e00}-\x{9fa5}])+/u",$resume_content,$CN_ENG_array);
		}
		//获得姓名
		$resume['name'] = $this->getName($CN_ENG_array[0]);
		//获得所在城市
		$resume['target_city'] = $this->getCity($CN_ENG_array[0], $resume['last_company']);
		//获得邮箱
		$resume['email'] = $this->getemail($resume_content);
		//出生年份 1981
		//$resume['birth'] = date('Y') - intval($this->getBirth($resume_content));
		$resume['birth_year'] = $this->getBirth($resume_content);
		//获得性别
		$resume['sex'] = $this->getSex($resume_content);
		//获得电话
		$resume['phone'] = $this->getphone($resume_content);
		//目前期望薪资
		$salary = $this->getSalary($resume_content);
		$resume['current_salary'] = $salary['now'];
		$resume['target_salary'] = $salary['hope'];
		//将空格,回车,换行都替换为空格，这样匹配就只考虑空格
		//$resume =  preg_replace("/\s+/"," ",$resume);
		//提取工作经历列表
		$experiencesList = $this->getWorkExperiences($resume_content);
		$workExperiencesList = $experiencesList[0];
		$educationExperiences = $experiencesList[1];

		$resume['career'] = $workExperiencesList;
		if($this->project){
			$resume['projects'] = $this->project($this->project);
		}
		foreach($workExperiencesList as $k=>$v){
			if($workExperiencesList[$k]['company']){
				$resume['company'][] = $workExperiencesList[$k]["company"];
			}
			if($workExperiencesList[$k]["position"]){
				$resume['position'][] = $workExperiencesList[$k]["position"];
			}
		}
		//默认工作经历是倒序排列？
//		$resume['workExperiences'] = $workExperiencesList;
//		//工作年份
//		$resume['workyear'] = substr($workExperiencesList[count($workExperiencesList)-1]['startdate'],0,4);
		//提取最近工作过的公司
		$resume['last_company'] = $resume['company'][0];
		//提取最近职位
		$resume['last_position'] = $resume['position'][0];

		//统一工作经历格式
		/*$resume['career']['company'] = $resume['company'];
		$resume['career']['position'] = $resume['position'];
		$resume['career']['workExperiences'] = $workExperiencesList;
		$resume['career']['last_company'] = $resume['company'][0];
		$resume['career']['last_position'] = $resume['position'][0];
		$resume['career']['workyear'] = substr($workExperiencesList[count($workExperiencesList)-1]['startdate'],0,4);
		unset($resume['company']);
		unset($resume['position']);*/
		//vde($resume);

		$resume['education'] = $this->education($educationExperiences);
		//教育经历
		//$resume['educationExperiences'] = $educationExperiences;

		$education = $this->getEducationInfo($educationExperiences,$resume_content);
		$resume['major'] = $education['major'];
		$resume['degree'] = $education['firstDegree'];
		//$resume['top_degree'] = $education['topDegree'];
		$resume['school'] = $education['school'];
		//统一教育经历格式
		/*$education = $this->getEducationInfo($educationExperiences,$resume_content);
		$resume['education']['educationExperiences'] = $educationExperiences;
		$resume['education']['major'] = $education['major'];
		$resume['education']['first_degree'] = $education['firstDegree'];
		$resume['education']['top_degree'] = $education['topDegree'];
		$resume['education']['campus'] = $education['school'];
		//判断学校是985还是211
		if($resume['campus']) {
			$where['fullname'] = array('like', '%' . $resume['campus'] . '%');
			//$schoolCategory = M('school')->where($where)->find();
			$schoolCategory = Db::table('sj_school')->where($where)->find();
			if ($schoolCategory['985'] == 0) {
				$resume['education']['is985'] = true;
			} elseif ($schoolCategory['985'] != 1 && $schoolCategory['211'] == 1) {
				$resume['education']['is211'] = true;
			}
		}*/
		//return $resume;
		$Pased = false;
		if($resume['career']||$resume['phone']){
			$Pased = true;
		}
		if($Pased==true)
			return $resume;
		else{
			sendMail(0,$origin_resume_content);
			return null;
		}
	}
}