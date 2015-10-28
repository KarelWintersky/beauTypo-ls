
ls.settings.getMarkitup = function() {
    // переписываем нафиг кнопки
    return {
        onShiftEnter:   {keepDefault:false, replaceWith:'\n'},
        onCtrlEnter:    {keepDefault:false, replaceWith:'<br />\n'},
        onTab:          {keepDefault:false, replaceWith:'  '},
        markupSet:  [
            // {name: ls.lang.get('panel_code'), className:'editor-code', openWith:'<code>', closeWith:'</code>' },
            // {name: ls.lang.get('panel_list_li'), className:'editor-li', openWith:'<li>', closeWith:'</li>' },
            // {name: ls.lang.get('panel_image'), className:'editor-picture', key:'P', beforeInsert: function(h) { jQuery('#window_upload_img').jqmShow(); } },
            // {name: ls.lang.get('panel_video'), className:'editor-video', replaceWith:'<video>[!['+ls.lang.get('panel_video_promt')+':!:http://]!]</video>' },
            // {name: ls.lang.get('panel_url'), key:"L", openWith:'[', closeWith:']([![Url:!:http://]!] "[![Title]!]")', placeHolder:'Your text to link here...' },
            // {name: ls.lang.get('panel_url'), className:'editor-link', key:'L', openWith:'(([!['+ls.lang.get('panel_url_promt')+':!:http://]!] (!( title="[![Title]!]")!) ', closeWith:')) ', placeHolder:'Название ссылки' },
            {name:'H4', className:'editor-h4', openWith:'= ', closeWith:'' },
            {name:'H5', className:'editor-h5', openWith:'== ', closeWith:'' },
            {name:'H6', className:'editor-h6', openWith:'=== ', closeWith:'' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_b'), className:'editor-bold', key:'B', openWith:'**', closeWith:'**' },
            {name: ls.lang.get('panel_i'), className:'editor-italic', key:'I', openWith:'//', closeWith:'//'  },
            {name: ls.lang.get('panel_s'), className:'editor-stroke', key:'S', openWith:'--', closeWith:'--' },
            {name: ls.lang.get('panel_u'), className:'editor-underline', key:'U', openWith:'__', closeWith:'__' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_quote'), className:'editor-quote', key:'Q', replaceWith: function(m) { if (m.selectionOuter) return '<blockquote>'+m.selectionOuter+'</blockquote>'; else if (m.selection) return '<blockquote>'+m.selection+'</blockquote>'; else return '<blockquote></blockquote>' } },
            {name: ls.lang.get('panel_list'), className:'editor-ul', openWith:'\n* ', closeWith:'' },
            {name: ls.lang.get('panel_list'), className:'editor-ol', openWith:'\n1. ', closeWith:'' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_url'), className:'editor-link', key:'L', openWith:'(([!['+ls.lang.get('panel_url_promt')+':!:http://]!] [![Заголовок]!])) ', closeWith:'' },
            {name: ls.lang.get('panel_user'), className:'editor-user', replaceWith:'@[!['+ls.lang.get('panel_user_promt')+']!]' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_clear_tags'), className:'editor-clean', replaceWith: function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } },
            {name: ls.lang.get('panel_cut'), className:'editor-cut', replaceWith: function(markitup) { if (markitup.selection) return '<cut name="'+markitup.selection+'">'; else return '<cut>' }}
        ]
    };
};


ls.settings.getMarkitupComment = function() {
    return {
        onShiftEnter:   {keepDefault:false, replaceWith:'\n'},
        onCtrlEnter:    {keepDefault:false, replaceWith:'<br />\n'},
        onTab:          {keepDefault:false, replaceWith:'  '},
        markupSet:  [
            // {name: ls.lang.get('panel_code'), className:'editor-code', openWith:'<code>', closeWith:'</code>' },
            // {name: ls.lang.get('panel_image'), className:'editor-picture', key:'P', beforeInsert: function(h) { jQuery('#window_upload_img').jqmShow(); } },
            {name: ls.lang.get('panel_b'), className:'editor-bold', key:'B', openWith:'**', closeWith:'**' },
            {name: ls.lang.get('panel_i'), className:'editor-italic', key:'I', openWith:'//', closeWith:'//'  },
            {name: ls.lang.get('panel_s'), className:'editor-stroke', key:'S', openWith:'--', closeWith:'--' },
            {name: ls.lang.get('panel_u'), className:'editor-underline', key:'U', openWith:'__', closeWith:'__' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_quote'), className:'editor-quote', key:'Q', replaceWith: function(m) { if (m.selectionOuter) return '<blockquote>'+m.selectionOuter+'</blockquote>'; else if (m.selection) return '<blockquote>'+m.selection+'</blockquote>'; else return '<blockquote></blockquote>' } },
            {name: ls.lang.get('panel_url'), className:'editor-link', key:'L', openWith:'(([!['+ls.lang.get('panel_url_promt')+':!:http://]!] [![Заголовок]!])) ', closeWith:'' },
            {name: ls.lang.get('panel_user'), className:'editor-user', replaceWith:'@[!['+ls.lang.get('panel_user_promt')+']!]' },
            {separator:'---------------' },
            {name: ls.lang.get('panel_clear_tags'), className:'editor-clean', replaceWith: function(markitup) { return markitup.selection.replace(/<(.*?)>/g, "") } }
        ]
    }
};


var ls = ls || {};

ls.beautypo = ( function ($) {
    this.textarea;
    this.message;

    this.prepareText = function(el){
        if(!el.length) return;
        ls.beautypo.textarea = $(el);

        text = ls.beautypo._clearText(ls.beautypo.textarea.val());

        text = text.replace(/(<iframe([^>]*)>[^<]*<\/iframe>)/g, function(m, key){
            var ret = m;
            m = m.replace(/src="([^"]+)"/g, function(s,link){
                link = ls.beautypo._parseHref(link);
                if(link['host'].indexOf("vk.com")>-1) ret = link['href'];
                if(link['host'].indexOf("youtube.com")>-1) ret = link['href'];
                if(link['host'].indexOf("vimeo.com")>-1) ret = link['href'];
            });
            return "\n\n"+ret+"\n\n";
        });

        ls.beautypo.message = ls.beautypo._clearText(text);
        ls.beautypo.textarea.val(ls.beautypo.message);
    }

    this.getVkIframe = function(link){
        return '<iframe src="'+link+'" frameborder="0"></iframe>';
    }

    this.getYoutubeIframe = function(id){
        return '<iframe type="text/html" width="640" height="385" src="http://www.youtube.com/embed/'+id+'?wmode=opaque&amp;showsearch=0&amp;rel=0&amp;iv_load_policy=3&amp;controls=2&amp;autohide=1&amp;autoplay=1" frameborder="0">';
    }

    this.getVimeoIframe = function(id){
        return '<iframe src="http://player.vimeo.com/video/'+id+'?autoplay=1" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';
    }

    this._clearText = function(text){
        text = text.replace(/\r\n|\n/g, '\n');
        return jQuery.trim(text.replace(/\n{2,}/g, '\n\n'));
    }

    this._parseHref = function(href) {
        var a = document.createElement('a');
        a.href = href;
        var url = new Object();

        url["href"] = a.href; // 'http://site.ru/page/123?foo=bar#top'
        url["protocol"] = a.protocol; // 'http:'
        url["host"] = a.host; // 'site.ru'
        url["pathname"] = a.pathname; // '/page/123'
        url["search"] = ls.beautypo._parseQueryString(a.search); // '?foo=bar'
        url["hash"] = a.hash; // '#top'
        return url;
    }

    this._parseQueryString = function(query, groupByName) {
        if (typeof query != 'string') {
            throw 'Ivalid input';
        }

        var parsed = {},
            hasOwn = parsed.hasOwnProperty,
            query = query.substring(1).replace(/\+/g, ' '),
            pairs = query.split(/[&;]/);

        for (var i = 0; i < pairs.length; i++) {
            var pair = pairs[i].match(/^([^=]*)=?(.*)/);
            if (pair[1]) {
                try {
                    var name  = decodeURIComponent(pair[1]);
                    var value = decodeURIComponent(pair[2]);
                } catch(e) {
                    throw 'Invaid %-encoded sequence';
                }

                if (!groupByName) {
                    parsed[name] = value;
                } else if (hasOwn.call(parsed, name)) {
                    parsed[name].push(value);
                } else {
                    parsed[name] = [value];
                }
            }
        }
        return parsed;
    }

    this.getIframe = function(service, id){
        switch(service)
        {
            case "youtube":
                return '<iframe type="text/html" width="640" height="385" src="http://www.youtube.com/embed/'+id+'?wmode=opaque&amp;showsearch=0&amp;rel=0&amp;iv_load_policy=3&amp;controls=2&amp;autohide=1&amp;autoplay=1" frameborder="0" scrolling="no">';
                break;
            case "vimeo":
                return '<iframe src="http://player.vimeo.com/video/'+id+'?autoplay=1&amp;wmode=opaque" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen scrolling="no"></iframe>';
                break;
            case "rutube":
                return '<iframe src="http://rutube.ru/video/embed/'+id+'?wmode=opaque" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowfullscreen scrolling="no"></iframe>';
                break;
        }
    }

    return this;
}).call(ls.beautypo || {}, jQuery);

$(function() {
    ls.beautypo.prepareText($("#topic_text"));
    $("#topic_text, #form_comment_text").bind("focusout", function(){
        ls.beautypo.prepareText($(this));
    });

    $(".txt-video-youtube > a").click(function(e){
        e.preventDefault();
        var link = $(this);
        var url = ls.beautypo._parseHref(link.attr("href"));
        link.after(ls.beautypo.getIframe("youtube", url.search.v)).fadeOut("slow");
        $(".txt-video a").not(this).fadeIn("fast").next("iframe").remove();
    });

    $(".txt-video-vimeo > a").click(function(e){
        e.preventDefault();
        var link = $(this);
        var url = ls.beautypo._parseHref(link.attr("href"));
        link.after(ls.beautypo.getIframe("vimeo", url.pathname.substring(1))).fadeOut("slow");
        $(".txt-video a").not(this).fadeIn("fast").next("iframe").remove();
    });

    $(".txt-video-rutube > a").click(function(e){
        e.preventDefault();
        var link = $(this);
        var url = ls.beautypo._parseHref(link.attr("href"));
        link.after(ls.beautypo.getIframe("rutube", url.pathname.replace("/tracks/","").replace(".html",""))).fadeOut("slow");
        $(".txt-video a").not(this).fadeIn("fast").next("iframe").remove();
    });

 });

