<?php
/********************************************
 * Author: Andrew Borisenko
 * e-mail: seigiard@gmail.com
 * since 2012-01-03
 * TODO: 
 - перед обработкой на парсинг картинок, пройти автоальтом текст на наличие картинок с <img>
 - проверять урл картинки, если это ЛайтИмадж — позволить брать превью для тумбнейлов
 ********************************************/

require_once(dirname(__FILE__)."/../modules/libs/Markdown.class.php");
require_once(dirname(__FILE__)."/../modules/libs/Typo.class.php");
require_once(dirname(__FILE__)."/../modules/libs/phphypher/hypher.php");

function d() {
    // Получаем все аргументы одним массивом
    $all_args = func_get_args();
    echo "<meta charset='UTF-8'><table><tr valign='top'>";
    foreach ($all_args as $key => $value) {
        echo "<td>";
        if (is_string($value)) {
            echo $value;
        } else {
            echo "<pre style='font-family: Monaco;font-size: 12px;white-space: pre-wrap;'><code style='display: block; padding: .5em 1em; margin:1em 0; background: #F0F0F0;'>";
            var_dump($value);
            echo "</code></pre>";
        }
        echo "</td>";
    }
    echo "</tr></table>";
    echo "<hr />";
}

/**
 *
 */
class PluginBeautypo_HookBeautypo extends Hook {
    public function RegisterHook() {
        $this -> AddHook ('init_action', 'AddStylesAndJS');
    }

    public function AddStylesAndJS () {
        $sTemplateWebPath = Plugin::GetTemplateWebPath (__CLASS__);
        $this -> Viewer_AppendStyle ($sTemplateWebPath . 'css/style.css');
    }
}
