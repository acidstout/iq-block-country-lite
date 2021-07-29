<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

global $searchengines;

global $searchengines;
$searchengines = array(
	"Alexa" => "ia_archiver",
	"AppleBot" => "Applebot",
	"Ask" => "ask jeeves",
	"Baidu" => "Baiduspider",
	"Bing" => "bingbot",
	"Bitly" => "bitlybot",
	"Broken Link Check" => "brokenlinkcheck.com",
	"Cliqz" => "cliqzbot",
	"Dead Link Checker" => "www.deadlinkchecker.com",
	"Duck Duck Go" => "duckduckbot",
	"Feedly" => "Feedly",
	"Facebook" => "facebookexternalhit",
	"Feedburner" => "FeedBurner",
	"Google" => "Googlebot",
	"Google Ads" => "AdsBot-Google",
	"Google AdsBot Mobile Web " => "AdsBot-Google-Mobile",
	"Google Ads (Mediapartners)" => "Mediapartners-Google",
	"Google Feed" => "Feedfetcher-Google",
	"Google Images" => "Googlebot-Image",
	"Google News" => "Googlebot-News",
	"Google Page Speed Insight" => "Google Page Speed Insight",
	"Google Read Aloud" => "Google-Read-Aloud",
	"Google Search Console" => "Google Search Console",
	"Google Site Verification" => "Google-Site-Verification",
	"Google StoreBot" => "Storebot-Google",
	"Google Video" => "Googlebot-Video",
	"Jetpack" => "Jetpack by WordPress.com",
	"Link checker" => "W3C-checklink",
	"MOZ" => "rogerbot",
	"MSN" => "msnbot",
	"Pingdom" => "Pingdom.com_bot",
	"Pinterest" => "Pinterest",
	"SEMrush" => "SemrushBot",
	"SEOkicks" => "SEOkicks-Robot",
	"TinEye" =>  "tineye-bot",
	"Twitter" => "twitterbot",
	"Yahoo!" => "yahoo! slurp",
	"Yandex" => "yandexbot"
);

function iqblockcountry_check_searchengine($user_agent, $allowse) {
    global $searchengines;
    
    foreach ( $searchengines AS $se => $seua ) {
        if (is_array($allowse) && in_array($se, $allowse)) {        
            if (stripos($user_agent, $seua) !== false) {
                return true;
            }
        }
    }
	
    return false;
}