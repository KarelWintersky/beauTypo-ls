<?php

/**
 *
 */
class PluginBeautypo_ModuleText extends PluginBeautypo_Inherit_ModuleText {

    /**
     * @param $sText
     * @return string
     */
    public function Parser($sText) {
        if (!is_string($sText)) {
            return '';
        }

        $replace = array(
           '/', '+', '-', '*', '^', 'v', '_'
        );

        $is_parse = false;
        $eText = $sText;
        $eText = str_replace(array("\r\n","\n\r"),array("\n","\n"), $eText);
        $eText = str_replace(array("\r"),array(" "), $eText );

        $video_array = array("youtube.com","youtu.be","rutube.ru","vk.com","vimeo.com");

        //detect video string
        if(strpos($eText, " ") === false){
            foreach ($video_array as $key => $value) {
                if(strpos($eText, $value) !== false){
                    $is_parse = true;
                }
            }
        }

        // detect new lines
        if( count(explode("\n",trim($eText))) > 1 ){
            $is_parse = true;
        }

        // detect html tags
        if(!$is_parse) if(strlen(htmlspecialchars($eText)) != strlen($eText)){
            $is_parse = true;
        }

        // detect markdown headers
        if(!$is_parse) if( preg_match("/^[=#]{1,3}\s/im", $eText) ){
            $is_parse = true;
        }

        // detect markdown links
        if(!$is_parse) if( preg_match("/(\(\(|\[\[)[^\)\]]*(\)\)|\]\])/im", $eText)){
            $is_parse = true;
        }

        // detect markdown syntax
        foreach ($replace as $char) {
            if(!$is_parse) if( preg_match("/[\\".$char."]{2}[^\\".$char."]*[\\".$char."]{2}/im", $eText) ){
                $is_parse = true;
            }
        }

        if(!$is_parse) return $sText;

        $sResult = $this->PluginBeautypo_Beautypo_ParseText($sText);
        $sResult=$this->FlashParamParser($sResult);
        $sResult=$this->JevixParser($sResult);
        $sResult=$this->VideoParser($sResult);
        $sResult=$this->CodeSourceParser($sResult);
        return $sResult;
    }
}