<?php

/**
 * */
class PluginBeautypo_ModuleBeautypo extends Module
{
    public $text;

    // var $picture_regexp = "^(https?\:\/\/.*\.(jpe?g|gif|png))( +(.+))?$";

    public $picture_regexp = "^(https?\:\/\/.*\.(jpe?g|gif|png))( +(.+)?)?";
    public $gallery_picture_regexp = "(^(https?\:\/\/.*\.(jpe?g|gif|png))( +(.+)?)?\n){2,}";

    public $youtube_regexp = '~(?:http|https|)(?::\/\/|)(?:www.|)(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/ytscreeningroom\?v=|\/feeds\/api\/videos\/|\/user\S*[^\w\-\s]|\S*[^\w\-\s]))([\w\-]{11})[a-z0-9;:@?&%=+\/\$_.-]*~i';
    // var $youtube_regexp = "^https?\:\/\/(www\.)?youtu(\.be|be\.com).*(\/|\?|=)([\w-]{11})((&|\?).+)?$";

    public $vimeo_regexp = "^https?:\/\/[^\d]*([\d]{2,11})?";

    public function Init()
    {
    }

    /**
     * @param $text
     * @return mixed
     */
    public function ParseText($text)
    {
        $this->text = "\n\n" . str_replace(array("\r\n", "\n\r"), array("\n", "\n"), $text) . "\n\n";
        $this->text = str_replace(array("\r"), array(" "), $this->text);
        $this->text = str_replace(array("<br>\n", "<br />\n", "<br/>\n"), array("<br />", "<br />", "<br />"), $this->text);

        $this->_ParseImageAndVideo();
        if (Config::Get('plugin.beautypo.enable_formatter')) $this->_ParseFormatter();
        if (Config::Get('plugin.beautypo.enable_username')) $this->_ParseUsername();
        if (Config::Get('plugin.beautypo.enable_typograffer')) $this->_ParseTypo();
        if (Config::Get('plugin.beautypo.enable_hypher')) $this->_ParseHypher();
        return $this->text;
    }

    /**
     *
     */
    private function _ParseImageAndVideo()
    {
        $ar_text = explode("\n", $this->text);
        reset($ar_text);

        while (list($key, $val) = each($ar_text)) {
            $val = trim($val);
            if (strpos($val, "http") !== false && strpos($val, "http") === 0) {

                // detect youtube
                $url = $this->_parseUrl($val);

                if (trim($ar_text[$key - 1]) == "" && trim($ar_text[$key + 1]) == "") {
                    if (isset($url['host']) && strpos($url['host'], "youtube") !== false || strpos($url['host'], "youtu.be") !== false) {
                        $youtube_id = (preg_replace($this->youtube_regexp, '$1', trim($val)));
                        $ar_text[$key] = $this->_getYoutubePreview($youtube_id);
                        continue;
                    }
                    if (strpos($url['host'], "vimeo.com") !== false) {
                        $ar_text[$key] = preg_replace_callback("/" . $this->vimeo_regexp . "/im", array($this, '_getVimeoPreview'), $val);
                        continue;
                    }
                    if (strpos($url['host'], "vk.com") !== false) {
                        $ar_text[$key] = $this->_getVkPreview($url);
                        continue;
                    }
                    if (strpos($url['host'], "rutube.ru") !== false) {
                        $ar_text[$key] = $this->_getRutubePreview($url);
                        continue;
                    }
                }

                //  detect images
                preg_match("/^" . $this->picture_regexp . "/", $val, $matches, PREG_OFFSET_CAPTURE);
                if (count($matches)) {
                    if (trim($ar_text[$key - 1]) == "" && trim($ar_text[$key + 1]) == "") {
                        $ar_text[$key] = preg_replace_callback("/" . $this->picture_regexp . "/im", array($this, '_picture_code'), $val);
                    }
                }

            }
        }
        $this->text = implode("\n", $ar_text);
        $this->text = preg_replace_callback("/" . $this->gallery_picture_regexp . "/im", array($this, '_gallery_code'), $this->text);
    }

    /**
     * @param $uri
     * @return mixed
     */
    private function _parseUrl($uri)
    {
        $url = parse_url($uri);
        if (isset($url['query'])) $url['_query'] = $this->_parseQuery($url['query']);
        $url['all'] = $uri;
        return $url;
    }

    /**
     * @param $var
     * @return array
     */
    private function _parseQuery($var)
    {
        $var = html_entity_decode($var);
        $var = explode('&', $var);
        $arr = array();

        foreach ($var as $val) {
            $x = explode('=', $val);
            $arr[trim($x[0])] = trim($x[1]);
        }
        unset($val, $x, $var);
        return $arr;
    }

    /**
     * @param $youtube_id
     * @return string
     */
    private function _getYoutubePreview($youtube_id)
    {
        $json = json_decode(file_get_contents("http://gdata.youtube.com/feeds/api/videos/" . $youtube_id . "?v=2&alt=jsonc"));
        return $this->_getVideoReplace("youtube", "http://www.youtube.com/watch?v=" . $youtube_id, 'http://img.youtube.com/vi/' . $youtube_id . '/hqdefault.jpg', $json->data->title);
    }

    /**
     * @param $matches
     * @return string
     */
    private function _getVimeoPreview($matches)
    {
        $json = json_decode(file_get_contents("http://vimeo.com/api/v2/video/" . $matches[1] . ".json"));
        return $this->_getVideoReplace("vimeo", 'http://vimeo.com/' . $matches[1], $json[0]->thumbnail_large, $json[0]->title);
    }

    /**
     * @param $url
     * @return string
     */
    private function _getRutubePreview($url)
    {
        if (strpos($url['path'], "tracks") || strpos($url['path'], "embed")) {
            $id = str_replace(array("/tracks/", "/video/embed/", ".html"), "", $url['path']);

            $xml = simplexml_load_file("http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=movie&rt_movie_id=" . $id . "&utf=1");
            if ($xml) {
                return $this->_getVideoReplace("rutube", $xml->source_url, $xml->thumbnail_url, $xml->title);
            }
            ;
        } else {
            $html = $this->_getPageCurl("http://rutube.ru" . $url['path']);
            if (preg_match('/[http|https]+:\/\/(?:www\.|)rutube\.ru\/video\/embed\/([a-zA-Z0-9_\-]+)/i', $html, $matches) || preg_match('/[http|https]+:\/\/(?:www\.|)rutube\.ru\/tracks\/([a-zA-Z0-9_\-]+)(&.+)?/i', $html, $matches)) {

                $xml = simplexml_load_file("http://rutube.ru/cgi-bin/xmlapi.cgi?rt_mode=movie&rt_movie_id=" . $matches[1] . "&utf=1");
                if ($xml) {
                    return $this->_getVideoReplace("rutube", $xml->source_url, $xml->thumbnail_url, $xml->title);
                }
            }
        }
        return "";
    }

    /**
     * @param $url
     * @return string
     */
    private function _getVkPreview($url)
    {
        return '<div class="txt-video txt-video-vk"><iframe src="http://vk.com/video_ext.php?' . $url['query'] . '" frameborder="0"></iframe></div>';
    }

    /**
     * @param $service
     * @param $url
     * @param $image
     * @param $title
     * @return string
     */
    private function _getVideoReplace($service, $url, $image, $title)
    {
        // $alt = htmlspecialchars(str_replace(array('"',"'"), "", $title), ENT_QUOTES, 'UTF-8');
        $alt = str_replace(array('"', "'"), "", $title);
        return '<div class="txt-video txt-video-' . $service . '"><a href="' . $url . '" title="' . $alt . '"><img src="' . $image . '" alt="' . $alt . '" title="' . $alt . '"><h5>' . $title . '</h5></a></div>';
    }

    /**
     * @param $matches
     * @return string
     */
    private function _gallery_code($matches)
    {
        $images = preg_replace_callback("/" . $this->picture_regexp . "\n?/im", array($this, '_gallery_picture_code'), trim($matches[0]));
        return '<div class="imageslider">' . $images . ' </div>';
    }

    /**
     * @param $matches
     * @return string
     */
    private function _gallery_picture_code($matches)
    {
        $alt = (isset($matches[4]) && trim($matches[4])) ? $this->_ImageAltSanitize($matches[4]) : "";
        return '<img src="' . $matches[1] . '" alt="' . $alt . '" title="' . $alt . '" />';
    }

    /**
     * @param $matches
     * @return string
     */
    private function _picture_code($matches)
    {
        $alt = (isset($matches[4]) && trim($matches[4])) ? $this->_ImageAltSanitize($matches[4]) : "";
        return '<div class="txt-picture"><img src="' . $matches[1] . '" alt="' . $alt . '" title="' . $alt . '" /> </div>';
    }

    /**
     *
     */
    private function _ParseUsername()
    {
        $this->text = preg_replace("/@(\w+)/i", '<ls user="${1}" />', $this->text);
    }

    /**
     *
     */
    private function _ParseFormatter()
    {
        $parser = new MarkdownParser();
        $this->text = $parser->transform($this->text);
    }

    /**
     *
     */
    private function _ParseTypo()
    {
        $this->text = typo($this->text);
    }

    /**
     *
     */
    private function _ParseHypher()
    {
        $hy_ru = new phpHypher(dirname(__FILE__) . '/../libs/phphypher/hyph_ru_RU.conf');
        // Расстановка переносов.

        $this->text = preg_replace_callback('/<((script|style|code|save)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $this->text);
        $this->text = preg_replace_callback('/<([^%][^>]*)>/us', 'typo_tag_encode', $this->text);

        $this->text = $hy_ru->hyphenate($this->text, 'UTF-8');

        $this->text = preg_replace_callback('/<([^%][^>]*)>/u', 'typo_tag_decode', $this->text);
        $this->text = preg_replace_callback('/<%([^>]+)>/u', 'typo_savetag_decode', $this->text);
    }

    /**
     * @param $alt
     * @return mixed
     */
    private function _ImageAltSanitize($alt)
    {
        return str_replace('"', "'", $alt);
    }

    /**
     * Method for loading remote url
     * @param string $url Remote url
     * @return mixed $data Results of an request
     * */
    private function _getPageCurl($url = '')
    {
        if (empty($url)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $data = curl_exec($ch);
        return $data;
    }
}
