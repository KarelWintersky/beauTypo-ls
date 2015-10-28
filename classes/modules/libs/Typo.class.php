<?php
/*
Auto typograf by Maxim Popov http://ecto.ru/

dont forget set mb_internal_encoding('UTF-8');

//CSS правила для висячей пунктуации
span.sbrace {margin-right: 0.5em}
span.hbrace {margin-left: -0.5em}

span.slaquo {margin-right: 0.44em}
span.hlaquo {margin-left: -0.44em}
span.slaquo-s {margin-right: 0.7em}
span.hlaquo-s {margin-left: -0.7em}
span.slaquo-b {margin-left: 0.85em}
span.hlaquo-b {margin-left: -0.85em}

span.sbdquo {margin-right: 0.4em}
span.hbdquo {margin-left: -0.4em}
span.sbdquo-s {margin-right: 0.35em}
span.sbdquo-s {margin-left: -0.35em}

span.squot {margin-right: 0.32em}
span.hquot {margin-left: -0.32em}

span.sowc {margin-right: 0.04em}
span.howc {margin-left: -0.04em}

span.sowcr {margin-right: 0.05em}
span.howcr {margin-left: -0.05em}

*/


function typo_tag_encode($match)
{
    return '<' . base64_encode(htmlentities($match[1])) . '>';
}

function typo_tag_decode($match)
{
    return '<' . html_entity_decode(base64_decode($match[1])) . '>';
}

function typo_savetag_encode($match)
{
    return '<%' . base64_encode(htmlentities('%' . $match[1])) . '>';
}

function typo_savetag_decode($match)
{
    $t = html_entity_decode(base64_decode($match[1]));
    if ($t[0] != '%') return '<%' . $match[1] . '>';
    return '<' . substr($t, 1) . '>';
}

function typo_nbsp($match)
{
    $match_t = trim(preg_replace('/<[^>]+>/u', '', $match[0]));
    //if(substr($match_t,-1,1)=='.')return $match[0];
    $match_t = preg_replace('/[\s\W]/u', '', $match_t);

    $t = mb_strlen($match_t);
    if ($t > 0 && $t < 4) $match[0] = $match[1] . '&nbsp;';

    return $match[0];
}


function typo($text, $settings = 'none')
{

    if ($text == '') return '';

    $config = array(
        'cleen_utf' => true,
    );

    if ($settings != 'none') $config = $settings + $config;

    $spec_chars_normalaize = array(
        '&quot;' => '"',
        '&#34;' => '"',
        '&#034;' => '"',

        '&#39;' => "'",
        '&#039;' => "'",

        '&#160;' => '&nbsp;',
        '&#xA0;' => '&nbsp;',
        chr(194) . chr(160) => '&nbsp;',

        '&mdash;' => '&#151;',
        chr(226) . chr(128) . chr(148) => '&#151;',

        '«' => '&laquo;',
        '»' => '&raquo;',
        '„' => '&bdquo;',
        '”' => '&rdquo;',
        '“' => '&ldquo;',
        "‘" => '&lsquo;',
        "’" => '&rsquo;',
    );

    $spec_chars_good = array(
        '&quot;' => '"',
        '&#34;' => '"',
        '&#034;' => '"',

        '&#39;' => "'",
        '&#039;' => "'",

        '&lsquo;' => "‘",
        '&rsquo;' => "’",

        '&ldquo;' => '“',
        '&#147;' => '“',
        '&#x93;' => '“',

        '&rdquo;' => '”',
        '&#148;' => '”',
        '&#x94;' => '”',

        '&bdquo;' => '„',

        '&mdash;' => chr(226) . chr(128) . chr(148),
        '&#151;' => chr(226) . chr(128) . chr(148),


        '&laquo;' => '«',
        '&#171;' => '«',
        '&#xAB;' => '«',

        '&raquo;' => '»',
        '&#187;' => '»',
        '&#xBB;' => '»',

        '&nbsp;' => chr(194) . chr(160),
        '&#160;' => chr(194) . chr(160),
        '&#xA0;' => chr(194) . chr(160),

        //'&#8209;'=>chr(226).chr(128).chr(145),
        //'-'=>chr(226).chr(128).chr(145),

        '&copy;' => '©',
        '&#169;' => '©',
        '&reg;' => '®',
        '&#174;' => '®',
        '&trade;' => '™',
        '&#153;' => '™',
        '&hellip;' => '…',
        '&plusmn;' => '±',
    );

    $symbols = array(
        '(c)' => '©',
        '(с)' => '©',
        '(r)' => '®',
        '(tm)' => '™',
        '(C)' => '©',
        '(С)' => '©',
        '(R)' => '®',
        '(TM)' => '™',
        '...' => '…',
        '1/2' => '&frac12;',
        '1/4' => '&frac14;',
        '3/4' => '&frac34;',
        '+-' => '±',
        '+/-' => '±'
    );


    //Сохраняем нужное
    $text = preg_replace_callback('/<((video|audio|img|embed)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $text);
    $text = preg_replace_callback('/<([^%][^>]*)>/us', 'typo_tag_encode', $text);

    $text = strtr($text, $symbols);
    $text = strtr($text, $spec_chars_normalaize);

    //Кавычки

    $text = preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', ' ' . $text . ' '); //russian
    $text = preg_replace('/([^\w])"([^"]*\d"[^"]+)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
    $text = preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
    $text = preg_replace('/([^\w])"([^"]*)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian

    $text = preg_replace('/(&laquo;)\s+/Uus', '\1', $text);
    $text = preg_replace('/\s+(&raquo;)/Uus', '\1', $text);

    $text = preg_replace('/&laquo;(.*)&laquo;(.*)&raquo;(.*)&raquo;/Usu', '&laquo;\1&bdquo;\2&ldquo;\3&raquo;', $text); //russian

    $text = preg_replace('/([^\w])\'([^\']*)\'([^\w])/Usu', '\1&lsquo;\2&rsquo;\3', $text);

    //Пробелы у пунктуации - иногда лучше отключать
    $text = preg_replace('/\s+([\.,;:\!\?])(\s+)/u', '\1\2', $text);

    $text = trim($text);

    //Много тире
    $text = preg_replace('/\s*-{2,3}\s*/us', '&nbsp;— ', $text);

    //Длинное тире
    $text = preg_replace('/\s+-\s+/us', '&nbsp;— ', $text);
    if ($text[0] == '-' && $text[1] == ' ') $text = '—' . substr($text, 1);


    /* BEGIN Seigiard Mod */

    // Прямая речь
    // $text = preg_replace('/(==>|\s)\s?(?:--?|-|—|&mdash;)(?=\s)/usi','$1—&nbsp;', $text);

    // Нельзя отрывать сокращение от относящегося к нему слова.
    // Например: тов. Сталин, г. Воронеж
    // Ставит пробел, если его нет.
    $text = preg_replace('/(^|[^a-zA-Zа-яА-Я])(г|гр|тов|пос|c|ул|д|пер|м|зам|см)\.\s?([А-Я0-9]+)/ius', '$1$2.&thinsp;$3', $text);

    // Не отделять стр., с. и т.д. от номера.
    $text = preg_replace('/(стр|с|табл|рис|илл|гл)\.\s*(\d+)/usi', '$1.&thinsp;$2', $text);

    // Неразрывный пробел между цифрой и единицей измерения
    $text = preg_replace('/([0-9]+)\s*(мм|см|м|л|км|г|кг|б|кб|мб|гб|dpi|px)(.|\s)/us', '$1&thinsp;$2$3', $text);

    // Год отделяем коротким пробелом.
    $text = preg_replace('/([0-9]{4})\s*([гГ])\.\s/us', '$1&thinsp;$2.', $text);

    // Сантиметр и другие ед. измерения в квадрате, кубе и т.д.
    $text = preg_replace('/([0-9]+)(&thinsp;|\s)*(мм|см|м|л|км|г|кг|б|кб|мб|гб|dpi|px)2/usi', '$1&thinsp;$3²', $text);
    $text = preg_replace('/([0-9]+)(&thinsp;|\s)*(мм|см|м|л|км|г|кг|б|кб|мб|гб|dpi|px)3/usi', '$1&thinsp;$3³', $text);

    // Нельзя оставлять в конце строки предлоги и союзы
    $text = preg_replace('/(?<=\s|^|\W)(а|в|во|вне|и|или|к|о|с|у|о|со|об|обо|от|ото|то|на|не|ни|но|из|изо|за|уж|на|по|под|подо|пред|предо|про|над|надо|как|без|безо|что|да|для|до|там|ещё|их|или|ко|меж|между|перед|передо|около|через|сквозь|для|при|я)(\s+)/ui', '$1&nbsp;', $text);

    // Неразрывный пробел после инициалов.
    $text = preg_replace('/([А-ЯA-Z]\.)\s?([А-ЯA-Z]\.)\s?([А-Яа-яA-Za-z]+)/us', '$1&thinsp;$2&thinsp;$3', $text);

    // Оторвать скобку от слова
    $text = preg_replace('/(\w)\(/uis', '$1 (', $text);

    // Слепляем скобки со словами
    $text = preg_replace('/\( /us', '(', $text);
    $text = preg_replace('/ \)/us', ')', $text);

    //Короткие слова
    $text = preg_replace('/\s+(\w{1,3}($|\.))/u', '&nbsp;\1', $text);

    // Размеры 10x10, правильный знак + убираем лишние пробелы
    // $text=preg_replace('/(\s\d+)\s{0,}?[x|X|х|Х|*]\s{0,}(\d+\s)/u', '$1×$2', $text);

    /* END Seigiard Mod */

    //Удаляем лишние пробелы
    $text = preg_replace('/\s*&nbsp;\s*/u', '&nbsp;', $text);
    $text = str_replace(' &#151;', '&nbsp;&#151;', $text);
    $text = str_replace(' -', '&nbsp;-', $text);

    //language part
    //back nbsp
    $text = preg_replace('/\s+(ж|бы|б|же|ли|ль|либо|или)([\s\W])/u', '&nbsp;\1\2', $text);

    $text = strtr($text, $spec_chars_good);

    //nbsp
    $text = preg_replace_callback('/([^\s]+)[ \t]+/u', 'typo_nbsp', $text);

    $text = str_replace('&nbsp;', chr(194) . chr(160), $text);
    $text = str_replace('&thinsp;', ' ', $text);

    //------------------------------------------------------------------------------
    //Восстанавливаем нужное1
    $text = preg_replace_callback('/<([^%][^>]*)>/u', 'typo_tag_decode', $text);

    //вынос кавычек из ссылок
    $text = preg_replace('/<a([^>]+)>"([^<]+)"<\/a>/usi', '«<a\1>\2</a>»', $text);

    //Восстанавливаем нужное2
    $text = preg_replace_callback('/<%([^>]+)>/u', 'typo_savetag_decode', $text);

    return $text;

}