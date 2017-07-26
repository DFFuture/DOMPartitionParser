<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/5
 * Time: 20:55
 */

namespace app\index\Parser;

/**
 * 数据转换器
 * Class DataConverter
 * @package app\index\Parser
 */
class DataConverter {

    //属性到转换方法的映射关系
    protected $MethodMap = array(
        'name'        => 'clean',
        'update_time' => 'str2time',
        'email'       => 'email',
        'phone'       => 'phone',
        'birth_year'  => 'birthYear',
        'age'         => 'age',
        'work_year'   => 'workYear',
        'phone_image' => 'image2Phone',
    );

    protected $rawData = array();

    /**
     * 数据转换
     * @param $keyName string 属性名
     * @param $rawValue mixed 属性值
     * @return array  返回格式：array('属性名', '属性值')
     */
    public function convert($keyName, $rawValue) {
        $map = $this->MethodMap;
        $method = $map[$keyName];
        if($method){
            $newKey = '';
            $value = $this->$method($rawValue, $newKey);
            if($newKey){
                return array($newKey, $value);
            }
            return array($keyName, $value);
        }else{
            //原值返回
            return array($keyName, $rawValue);
        }
    }

    /**
     * 多数据转换
     * @param $rawData array
     */
    public function multiConvert(&$rawData) {
        $this->rawData = $rawData;
        foreach($rawData as $key => $value) {
            $value = $this->str2Empty($value);
            if($value === '')
                $rawData[$key] = $value;
            else{
                $res = $this->convert($key, $value);
                $rawData[$res[0]] = $res[1];
            }
        }
    }

    //“未填”等信息转为空
    public function str2Empty($rawData) {
        $emptyValue = array('未填', '未设置');
        if(in_array($rawData, $emptyValue))
            return '';
        else
            return $rawData;
    }

    //转化为时间戳
    public function str2time($rawData) {
        return Utility::str2time($rawData)?:0;
    }

    //标准化电子邮件
    public function email($rawData) {
        $pattern = '/\w+(?:[-+.]\w*)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*/';
        if(preg_match($pattern, $rawData, $match)) {
            return $match[0];
        }else{
            return '';
        }
    }

    //标准化手机号
    public function phone($rawData) {
        $pattern = '/1[3|4|5|7|8][0-9]{9}/';
        if(preg_match($pattern, $rawData, $match)) {
            return $match[0];
        }else{
            return '';
        }
    }

    //出生年份
    public function birthYear($rawData) {
        $pattern = '/(?:20|19)\d{2}/';
        if(preg_match($pattern, $rawData, $match)) {
            return intval($match[0]);
        }else{
            return '';
        }
    }

    //年龄
    public function age($rawData) {
        $pattern = '/\d{2}/';
        if(preg_match($pattern, $rawData, $match)) {
            return intval($match[0]);
        }else{
            return '';
        }
    }

    //工作经验
    public function workYear($rawData) {
        $rawData = preg_replace('/\s+/','',$rawData);
        $pattern = '/.+?年(以上)?/';
        if(preg_match($pattern, $rawData, $match)) {
            return $match[0];
        }else{
            return '';
        }
    }

    public function clean($data) {
        $data = html_entity_decode(strip_tags($data));
        $find = array("\n","\t","\r");
        $data = str_replace($find,"",$data);
        $data = str_replace("，",",",$data);
        return trim($data);
    }

    public function image2Phone($data, &$newKey) {
        if($this->phone($this->rawData['phone']))
            return $data;
        $newKey = 'phone';
        $str = $this->image2Str($data, 'phone');
        return $str;
    }

    public function image2Str($data, $key) {
        $image_bs64 = str_replace('data:image/png;base64,', '', $data);
        $url = 'http://118.242.40.171:5000';
        $res = $this->doPost(array('img_base64' => $image_bs64), $url);
        return $res[$key];
    }

    public function doPost($data, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);
        return $result;
    }

}
