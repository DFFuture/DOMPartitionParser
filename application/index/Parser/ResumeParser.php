<?php
namespace app\index\Parser;

class ResumeParser {

    protected $templateIDs = array(
        '14' => '/121\.41\.112\.72\:12885/',
        '01' => '/简历编号(：|: )\d{5,8}[^\d\|]/',                //猎聘网
        '02' => '/<title>基本信息_个人资料_会员中心_猎聘猎头网<\/title>/',  //猎聘编辑修改页面
        '03' => '/<title>我的简历<\/title>.+?<div class="index">/s',         //可能是智联招聘
        '04' => '/\(编号:J\d{7}\)的简历/i',                   //中国人才热线
        '05' => '/简历编号(:|：)\d{8}\|猎聘通/',                    //猎聘网
        '06' => '/<title>.+?举贤网.+?<\/title>/i',            //举贤网
        '07' => '/编号\s+\d{16}/',                           //中华英才
        '08' => '/简历编号：\d{16}/',
        '09' => '/\(ID:\d{5,}\)|(51job\.com|^简_历|简历).+?基 本 信 息|个 人 简 历<\/b>/s',     //51job(前程无忧)
        '10' => '/<span[^>]*>智联招聘<\/span>|<div class="zpResumeS">/i',       //智联招聘
        '11' => '/<div (id="userName" )?class="main-title-fl fc6699cc"/',    //智联招聘
        '12' => '/来源ID:[\d\w]+<br>/',     //已被处理过的简历
        '13' => '/<title>简历ID：\d{5,}<\/title>.+?51job/s',  //新版51job
    );

    /**
     * 读取文档(windows下需要将路径转为GBK)
     * @param $path
     * @return bool|string
     */
    public function readDocument($path) {
        if(!$path) return '';
        $gbkPath = iconv("UTF-8", "GBK", $path);
        if(file_exists($gbkPath)) {
            $content = file_get_contents($gbkPath);
            if(preg_match('/\.mht$/',$path)){
                $content = Utility::mht2html($content);
            }
        }
        else
            $content = '';
        return $content;
    }

    //转码为UTF-8
    public function convert2UTF8($content) {
        $encodingList = array('UTF-8','GBK','GB2312');
        $encoding = mb_detect_encoding($content,$encodingList,true);

        if(!$encoding){
            $content = iconv('UCS-2', "UTF-8", $content);
            if(!$content) return false;       
        }elseif($encoding != 'UTF-8'){
            $content = mb_convert_encoding($content,'UTF-8',$encoding); 
        }
        $content = str_ireplace(array('gb2312', 'gbk'),'UTF-8',$content);
        return $content;
    }

    /**
     * 获取模板ID
     * @param $resume
     * @return int|string
     */
    public function getTemplateID($resume) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                return $id;
            }            
        }
        return false;
    }

    /**
     * 获取模板类
     * @param $resume
     * @param string $namespace
     * @return string
     */
    public function getTemplateClass($resume, $namespace = __NAMESPACE__) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                $templateClass = $namespace.'\Template'.$id;
                return $templateClass;
            }            
        }
        return false;
    }

    /**
     * 解析简历
     * @param $resume
     * @param $templateId string  简历模板编号
     * @return mixed
     */
    public function parse($resume, &$templateId = '') {
        $namespace = __NAMESPACE__;
        $templateId = strval($this->getTemplateID($resume));
        if($templateId)
            $templateClass = $namespace.'\Template'.$templateId;
        //dump($templateClass);
        $record = null;
        if(isset($templateClass)){
            $template = new $templateClass();
            $record = $template->parse($resume);
            if($record){
                $Converter = new DataConverter();
                $Converter->multiConvert($record);
            }
        }
        return $record;
    }

    /**
     * 获取DOM数组
     * @param $resume
     * @return mixed
     */
    public function getDomArray($resume) {
        $templateClass = $this->getTemplateClass($resume);
        $data = null;
        if($templateClass){
            $template = new $templateClass();
            $data = $template->getDomArray($resume);
        }

        return $data;
    }


    /**
     * 获取正则分割后的数组
     * @param $resume
     * @return array
     */
    public function getPregArray($resume) {
        $Parser = new CommonParser();
        $resume = $Parser->preprocess($resume);
        //dump($resume);
        $data = $Parser->pregParse($resume, false, false);
        return $data;
    }
}
