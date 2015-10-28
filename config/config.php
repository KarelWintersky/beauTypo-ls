<?php
$config=array(); 

$config['enable_formatter'] = true; // Enable Formatter
$config['enable_username'] = true; // Enable @username
$config['enable_typograffer'] = true; // Enable Beauty Typography
$config['enable_hypher'] = true; // Enable Hyphers

// AUTO CUT
$config['length_before_cut']=450; // Автоматически вставляем тег <cut> после ХХХ символов (слова и теги разрывать не должен)
$config['TagUnbreakable']=array('video','code','a','blockquote'); // Не разрешает вставлять CUT внутри этих тегов:
$config['cutPersonal']=true; // В персональных блогах топики резать будем?
$config['cutAdmin']=true; // * Топики администратора тоже урезать? 
$config['LightModeOn']=false; // * ЕСЛИ полбователь поставил <cut>, АвтоКат установит другой лимит: SecondBarrier.
// * Иначе, пользовательский кат будет заменен автоматическим ЕСЛИ он был установлен ПОСЛЕ лимита. 
$config['SecondBarrier']=800; // * Установите 0, если пользователь может поставить КАТ в ЛЮБОМ месте, или установите второе разумное ограничение;

if($config['enable_formatter']){
    $aAllowTags = Config::Get ('jevix.default.cfgAllowTags');
    $aAllowTags [0][0][] = 'br';
    $aAllowTags [0][0][] = 'p';
    $aAllowTags [0][0][] = 'small';
    $aAllowTags [0][0][] = 'sub';
    $aAllowTags [0][0][] = 'sup';
    $aAllowTags [0][0][] = 'strike';
    $aAllowTags [0][0][] = 's';
    $aAllowTags [0][0][] = 'div';
    Config::Set ('jevix.default.cfgAllowTags', $aAllowTags );

    Config::Set ('jevix.default.cfgSetAutoBrMode', array(array(0)) );
}

$aAllowTagParams = Config::Get ('jevix.default.cfgAllowTagParams');
$aAllowTagParams [] = array ( 'div', array ( 'class'=>array("txt-video", "txt-picture", "imageslider", "txt-video txt-video-youtube", "txt-video txt-video-vimeo", "txt-video txt-video-vk", "txt-video txt-video-rutube") ) );
Config::Set ('jevix.default.cfgAllowTagParams', $aAllowTagParams );

/*
Config::Set ('jevix.default.cfgSetAutoReplace', array(
    array(
        array('+/-', '(c)', '(с)', '(r)', '(C)', '(С)', '(R)', '(tm)', '(TM)', '&thinsp;'),
        array('±', '©', '©', '®', '©', '©', '®', '™', '™', ' ')
    )
) );
*/

return $config;