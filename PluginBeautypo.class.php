<?php
/********************************************
 * Author: Vladimir Linkevich
 * e-mail: Vladimir.Linkevich@gmail.com
 * since 2011-02-25
 ********************************************/

if(!class_exists('Plugin')) {
	die('Hacking attemp!');
}
class PluginBeautypo extends Plugin {
    protected $aInherits=array(
       'module'=>array('ModuleText'),
    );


	public function Activate() {
		return true;
	}

	public function Init() {
        if(Config::Get('plugin.beautypo.enable_formatter')){
            Config::Set('head.rules.beautypo', array(
                'path' => '___path.root.web___/',
                'js' => array(
                    'include' => array(
                        Plugin::GetTemplateWebPath(__CLASS__) . 'js/markitup-settings.js?'
                    )
                )
            ));
        }
		return true;
	}

	public function Deactivate() {
		return true;
	}
}

?>