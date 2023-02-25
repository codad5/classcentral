<?php
namespace Codad5\Classcentral;

use Exception;
use finfo;
use GuzzleHttp\Client;
use PHPHtmlParser\Dom;


class Scraper{
    public static function scrapeFromUrl($url, $depth = 0, \Closure $aftereach = null, \Closure $onDone = null)
    {
        $full_host = parse_url($url);
        // var_dump($full_host);
        try{
            $html = self::download($url);
            if(!$html) return false;
            $dom = new Dom;
            $dom->loadStr($html);
            $links = $dom->find('a');
            $title = $dom->find('title')[0]->text;
            $host_link = $full_host['scheme']."://".$full_host['host'];
            $path = rtrim($full_host['path'], "/");
            echo $path."\n";
            if(rtrim($path, '/') == '') $path = "index.html";
            $html = str_replace('/webpack/',"$host_link/webpack/", $html);
            $html = str_replace('/cdn-cgi/',"$host_link/cdn-cgi/", $html);
            (function() use (&$html, $url, $aftereach){
                return $aftereach($html, $url);
            })();
            self::saveFIle($path, $html);
            self::downloadStyleSheets($dom->find('link'), $host_link);
            self::downloadStyleSheets($dom->find('style'), $host_link);
            self::downloadScripts($html, $host_link);
            self::downloadImages($dom->find('img'), $host_link);
            if($depth <= 0) return true;
            echo "Links found in $url ".count($links)."\n";
                for ($i=0; $i < count($links); $i++) { 
                    # code...
                    $link = $links[$i]->href;
                    $crawl_url = parse_url($link);
                    if(isset($crawl_url['host']) && $crawl_url['host'] != $full_host['host']) continue;
                    if(!isset($crawl_url['host'])) $link = $full_host['scheme'] . "://" . $full_host['host'].$link;
                    echo $i."\n";
                    $links[$i]->setAttribute('href', $link);
                    self::scrapeFromUrl($link, $depth -1, $aftereach);
                    
                        
                }
            return !$onDone ? $html :
            (function () use ($path, $onDone) {
                return $onDone($path);
            })();
        }catch(Exception $e)
        {
            echo $e->getMessage()." at line ".$e->getLine()." in ".$e->getFile()."for URL at $url \n";
            return false;
        }
         
    }
    public static function downloadStyleSheets($links, $host)
    {
        for ($i=0; $i < count($links); $i++) { 
            $link = $links[$i]->href;
            if(!$link) continue;
            $link_d = parse_url($link);
            if(!isset($link_d['host'])) $link = $host."/$link";
            $link_d = parse_url($link);
            if(isset($link_d['host']) && parse_url($host)['host'] !== $link_d['host']) continue;
            $content = self::download($link);
            if($content) self::saveFile($link_d['path'] ?? 'index', $content, '.css');
            
        }
        return true;
    }
    public static function downloadScripts($html, $host)
    {
        $pattern = '/<script[^>]*src=[\'"]([^\'"]*)[\'"][^>]*>/i';
        $scripts = array();
        preg_match_all($pattern, $html, $matches);

        // var_dump($matches);
        foreach ($matches[1] as $match) {
            if (!in_array($match, $scripts)) {
                $scripts[] = $match;
            }
        }

        // var_dump($scripts);
        for ($i=0; $i < count($scripts); $i++) { 
            $link = $scripts[$i];
            $link_d = parse_url($link);
            if(!isset($link_d['host'])) $link = $host."/".$link;
            $link_d = parse_url($link);
            if (isset($link_d['host']) && parse_url($host)['host'] !== $link_d['host']) continue;
            $content = self::download($link);
            if ($content) self::saveFile($link_d['path'] ?? 'index', $content, '.js');
            
        }
        return true;
    }
    public static function downloadImages($links, $host)
    {
        for ($i=0; $i < count($links); $i++) { 
            $link = $links[$i]->src;
            // echo "saving $link \n";
            if(!$link) continue;
            $link_d = parse_url($link);
            if(!isset($link_d['host'])) $link = $host."/$link";
            $link_d = parse_url($link);
            if(isset($link_d['host']) && parse_url($host)['host'] !== $link_d['host']) continue;
            $content = self::download($link);
            if($content) self::saveFile($link_d['path'] ?? 'index', $content, '.png');
            
        }
        return true;
    }
    public static function download($url)
    {
        $client = new Client();

        // Make a GET request to the website
        $full_host = parse_url($url);
        try {
            echo "fetching $url \n";
            $response = $client->get($url);
            echo "$url fetched \n\n";
            // Check the status code of the response
            if ($response->getStatusCode() == 200) return $response->getBody();
            return false;
        } catch (Exception $e) {
            echo $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile() . "for URL at $url \n";
            return false;
        }
    }
    

    public static function saveFile($file_name, $content, $ext = ".html")
    {
        // $file_name = trim($file_name) == '' || trim($file_name) == '/' || empty(trim($file_name)) ?  "index".$ext : rtrim($file_name, '/') .$ext;
        $file_info = pathinfo($file_name);
        if(!isset($file_info['extension'])) $file_name = rtrim($file_name, "/").$ext;
        $file_name = "download/".$file_name;
        if(!file_exists(dirname($file_name))){
            mkdir(dirname($file_name), 0777, true);
        }
        $file = fopen($file_name, 'w');
        echo "saving $file_name \n";
        fwrite($file, $content);
        fclose($file);
        return true;
    }
}