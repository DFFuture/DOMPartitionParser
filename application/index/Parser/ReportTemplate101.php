<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/19
 * Time: 16:21
 */
//正荣推荐报告模板解析
namespace app\index\Parser;
class ReportTemplate101 extends AbstractParser {
    //区块标题
    protected $titles = array(
        array('baseinfo', '基本情况'),
        array('education', '教育经历'),
        array('careersummary', '工作经历概览'),
        array('career', '工作经历'),
        array('salary', '薪酬信息'),
        array('otherinfo', '辅助信息'),
        array('advisorinfo', '猎头信息'),
    );


    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
        $content = str_replace(array('姓  名：', '性  别：', '籍  贯：', '学  历：','手  机：','·','一、','二、','三、','四、','五、','六、','七、'),
            array('姓名：', '性别：', '籍贯：', '学历：','手机：','','','','','','',''), $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;'), '|', $content);
        return $content;
    }

    public function parse($content) {

        $record = array();
        $record['resume'] = $content;
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //其他解析
        $length = $blocks[0][1]-1 > 0 ?$blocks[0][1]-1:count($data)-1;
        $basic = array_slice($data,0, $length);
        $this->basic($basic, 0, $length - 1, $record);
        //各模块解析
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record);
        }
        return $record;
    }
    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }
    public function baseinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('name', '姓名：'),
            array('sex', '性别：'),
            array('residence', '籍贯：'),
            array('birth_year', '出生年月：'),
            array('city', '定居地：'),
            array('marriage', '婚姻状况：'),
            array('degree', '学历：'),
            array('phone', '手机：'),
            array('email', '电子邮箱：'),
            array('now_city', '目前工作地：'),
            array('target_city', '期望工作地：'),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }
    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('underlings', '下属人数：'),
            array('duty', '工作职责：'),
            array('performance', '工作业绩：'),
            array('report_to', '汇报对象：'),
            array('left_reason', '离职原因：'),
        );
        $i = 0;
        $j = 0;
        $job = array();
        $jobs = array();
        for($i=0;$i<$length;$i++){
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $start_time = Utility::str2time($match[1]);
                $end_time = Utility::str2time($match[2]);
                if($start_time>=$jobs[$j-1]['start_time'] && $end_time<=$jobs[$j-1]['end_time']){
                    continue;
                }
                $job['start_time'] = $start_time;
                $job['end_time'] = $end_time;
                $job['company'] = strlen($data[$i+1])>50?"":$data[$i+1];
                if(likeDepartment($data[$i+2])){
                    $job['department'] = $data[$i+2];
                    $job['position'] = strlen($data[$i+3])>50?"":$data[$i+3];
                }else{
                    $job['position'] = strlen($data[$i+2])>50?"":$data[$i+2];
                }
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules)){
                $jobs[$j-1][$KV[0]] = $KV[1];
            }
        }
        if($jobs){
            $record['last_company'] = $jobs[0]['company'];
            $record['last_position'] = $jobs[0]['position'];
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }


    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        $rules = array(
            array('major', '专业名称：'),
            array('degree','学历：')
        );
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $data[$i+1];
                $edu['major'] = $data[$i+2];
                $edu['degree'] = $data[$i+3];
                $education[$j++] = $edu;
            }
            $i++;
        }
        if($education){
            $record['school'] = $education[count($education)-1]['school'];
            $record['major'] = $education[count($education)-1]['major'];
            $record['degree'] = $education[count($education)-1]['degree'];
            $record['first_degree'] = $education[count($education)-1]['degree'];
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function careersummary($data, $start, $end, &$record){

    }
    public function salary($data, $start, $end, &$record){

    }
    public function otherinfo($data, $start, $end, &$record){

    }
    public function advisorinfo($data, $start, $end, &$record){

    }
}
