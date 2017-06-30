<?php
error_reporting(E_ALL);

if(!defined('STDIN')) exit;

include "PhpCliMod.php";

class CollectPageRefs
{
    private $description = 'Script for getting all unique hyperlinks on the web-page'.PHP_EOL.
        'Usage:'.PHP_EOL.
        '  php CollectPageRefs.php [OPTION] --url=<absolute-url>';
    private $phpCli;
    private $urlHashes = [];

    public function __construct($argv)
    {
        $options = [
                // used @gruber's reg. exp. for url
                ['url', 'Absolute reference to the web-page', true, '~\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))~iS']
        ];
        $this->phpCli = new PhpCliMod($argv, $options, $this->description);
    }

    /**
     * Parse web-page and echo's all unique links on it
     */
    public function parse()
    {
        $url = $this->phpCli->getArgValue('url');

        // get web-page content
        $page = $this->_getPageByUrl($url);

        $xpath = $this->_prepareHTML($page);
        $hrefs = $xpath->query('//a/@href');

        foreach($hrefs as $href) {
            // if link is incorrect, skip it
            if(!$value = $this->_normalizeUrl($url, $href->nodeValue)) {
                continue;
            };
            // remove trailing slash
            $value = trim($value, '/');

            // skip url if it is not unique
            if($this->_addUniqueUrl($value) == false) {
                continue;
            }

            echo $value . PHP_EOL;
        }
        $this->_clearUrlHashes();
    }

    /**
     * Prepare html-page for searching elements
     *
     * @param $html
     * @return DOMXPath
     */
    private function _prepareHTML($html)
    {
        // create DOMDocument for parsing web-page
        $dom = new DOMDocument;
        // hide internal errors during loading web-page if verbose option is turn off
        if(!$this->phpCli->hasArg('v')) {
            libxml_use_internal_errors(true);
        }
        $dom->loadHTML($html);
        if(!$this->phpCli->hasArg('v')) {
            libxml_use_internal_errors(false);
        }
        // create XPath for easy searching by html
        return new DOMXPath($dom);
    }

    /**
     * Add unique url like a hash
     *
     * @param $url
     * @return bool - true if url added, false is not
     */
    private function _addUniqueUrl($url)
    {
        // hashing urls
        $hash = md5($url);
        // if hash exists, url is exists too, so skip it
        if(isset($this->urlHashes[$hash])) {
            return false;
        }
        $this->urlHashes[$hash] = true; // "true" value is means nothing
        return true;
    }

    /**
     * Clears list of url hashes
     */
    private function _clearUrlHashes() {
        $this->urlHashes = []; // clear array
    }

    /**
     * Normalize url to absolute view
     *
     * @param $originalUrl - should be full absolute url
     * @param $untreatedUrl
     * @return string
     */
    private function _normalizeUrl($originalUrl, $untreatedUrl)
    {
        $decomposedUrl = parse_url($originalUrl);
        // generate origin
        $origin = "$decomposedUrl[scheme]://$decomposedUrl[host]".(isset($decomposedUrl['port']) ? ':' . $decomposedUrl['port'] : '');

        // normalize url
        if(preg_match('~^(javascript|data:)~', $untreatedUrl))
        {
            return false;
        }
        else if(preg_match('~^#~', $untreatedUrl))
        {
            return $originalUrl . $untreatedUrl;
        }
        else if(preg_match('~^//~', $untreatedUrl))
        {
            return 'http:' . $untreatedUrl;
        }
        else if(preg_match('~^/~', $untreatedUrl))
        {
            return $origin . $untreatedUrl;
        }
        // create $issetHost variable
        else if(($issetHost = parse_url($untreatedUrl, PHP_URL_HOST))
            && !(parse_url($untreatedUrl, PHP_URL_SCHEME)))
        {
            return 'http://' . $untreatedUrl;
        }
        else if(empty($issetHost))
        {
            return $origin . '/' . $untreatedUrl;
        }
    }

    /**
     * Get web-page content by absolute url
     *
     * @param $url
     * @return mixed HTML page content
     */
    private function _getPageByUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // set referer so that the sites are trusted to us more
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com');
        // output info about curl query
        if($this->phpCli->hasArg('v')) {
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
        }
        $page = curl_exec($ch);
        curl_close($ch);
        return $page;
    }
}

$CPR = new CollectPageRefs($argv);
$CPR->parse();