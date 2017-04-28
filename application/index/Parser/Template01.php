<?php
namespace app\index\Parser;

class Template01 extends AbstractParser {
    //模块标题
    protected $titles = array(
        array('target', '职业发展意向：'),
        array('evaluation', '自我评价:'), 
        array('education', '教育经历：'),
        array('projects', '项目经历：'),
        array('career', '工作经历：'), 
        array('addition', '附加信息:'),
        array('history', '历史版本:'), 
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '最后更新：'), 
        array('name', '姓名：'), 
        array('sex', '性别：'), 
        array('phone', '手机号码：'), 
        array('age', '年龄：'), 
        array('email', '电子邮件：'), 
        array('degree', '教育程度：'), 
        array('marriage', '婚姻状况：'), 
        array('city', '所在地：'), 
        array('work_begin', '工作年限：'), 
        array('work_status', '目前职业概况：'),
        array('industry', '所在行业：'), 
        array('last_company', '公司名称：'),
        array('last_position', '所任职位：'), 
        array('current_salary', '目前薪金：'), 
        array('bonus', '绩效奖金：'),
        array('target_industry', '期望行业：'), 
        array('target_position', '期望职位：'), 
        array('target_city', '期望地点：'), 
        array('target_salary', '期望月薪：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        return preg_match('/简历编号：\d{8}\D/',$content);
    }

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<script.*?>.*?<\/script>|<style.*?>.*?<\/style>/is',
            '/\||<br.*?>/i',
        );
        $replacements = array(
            '',
            '</td><td>',
        );
        $content = preg_replace($patterns, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->domParse($content, 'td', false);
        //dump($data);
        //dump($blocks);
        $end = $blocks?$blocks[0][1]-2:count($data)-1;
        $this->basic($data,0,$end, $record);
        if(isset($record['update_time']))
            $record['update_time'] = strtotime($record['update_time']);
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'td', false, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('duty', '-工作职责：|主要工作:'), 
            array('performance', '-工作业绩：'),
        );
        $sequence = array('company', 'industry', 'position');
        $i = 0;
        $j = 0;
        $k = 0;
        $currentKey = '';
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
                $k = 1;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $jobs[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
            }elseif($currentKey){
                $jobs[$j-1][$currentKey] .=  $data[$i];
            }
            $i++;
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
        $k = 0;
        $sequence = array('school');
        $rules = array(
            array('major', '-专业：', 0), 
            array('degree', '-学历：', 0), 
        );
        $education = array('');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $education[$j++] = $edu;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $education[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
            }
            $i++;
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

}
