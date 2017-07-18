<?php

if(!defined('STDIN')) exit;

include "PhpCliMod.php";
include "CollectPageRefsException.php";

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
                ['url', 'Absolute reference to the web-page (http or https)', true, '~\b((https?://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))~iS']
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

        //$memoryUsage = memory_get_usage(false);
        //$memoryPeakUsage = memory_get_peak_usage(false);
        //$time = microtime();
        foreach($hrefs as $href) {
            // if link is incorrect, skip it
            if(!$value = $this->_normalizeUrl($url, $href->nodeValue)) {
                continue;
            };
            // remove trailing slash
            $value = trim($value, '/');

            // skip url if it is not unique
            if(($hash = $this->_checkUniqueUrl($value)) === false) {
                continue;
            }
            $this->_addUniqueUrl($hash);

            $this->_printLine($value);
        }
        //$memoryUsage = memory_get_usage(false) - $memoryUsage;
        $this->_clearUrlHashes();
        //$time = microtime() - $time;
        //$memoryPeakUsage = memory_get_peak_usage(false) - $memoryPeakUsage;

        //$this->_printLine('Time: ' . $time);
        //$this->_printLine('Memory: ' . $memoryUsage);
        //$this->_printLine('Difference between Memory Peaks: ' . $memoryPeakUsage);
    }

    /**
     * Prepare html-page for searching elements
     *
     * @param $html
     * @return DOMXPath
     * @throws Exception
     */
    private function _prepareHTML($html)
    {
        if(empty($html))
        {
            throw new CollectPageRefsException("HTML-page is empty", 1);
        }

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
     * Checks url to unique
     *
     * @param $url
     * @return bool|string - false or hash of url if url is not exists
     */
    private function _checkUniqueUrl($url) {
        // hashing urls
        $hash = md5($url);
        // if hash exists, url is exists too, so skip it
        if(isset($this->urlHashes[$hash])) {
            return false;
        }
        return $hash;
    }

    /**
     * Add unique url hash
     *
     * @param $urlHash
     */
    private function _addUniqueUrl($urlHash)
    {
        $this->urlHashes[$urlHash] = true; // "true" value is means nothing
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
        //echo $untreatedUrl . PHP_EOL;
        $decomposedUrl = parse_url($originalUrl);
        $decompUntrtdUrl = parse_url($untreatedUrl);
        // generate origin
        $origin = "$decomposedUrl[scheme]://$decomposedUrl[host]".(isset($decomposedUrl['port']) ? ':' . $decomposedUrl['port'] : '');

        // normalize url
        if(isset($decompUntrtdUrl['scheme'])
            && preg_match('~^https?~', $decompUntrtdUrl['scheme']) == false)
        {
            return false;
        }
        else if(isset($decompUntrtdUrl['scheme']) === false
            && isset($decompUntrtdUrl['host'])) // '~^//~'
        {
            if($decompUntrtdUrl['host'] !== $decomposedUrl['host'])
            {
                return 'http:' . $untreatedUrl;
            }
            else
            {
                return $decomposedUrl['scheme'] . ':' . $untreatedUrl;
            }
        }
        else if(isset($decompUntrtdUrl['host']) === false
            && isset($decompUntrtdUrl['path'])) // '~^/~'
        {
            // trim for situations when $untreatedUrl with
            // beginning slash and not
            return $origin . '/' . trim($untreatedUrl, '/');
        }
        else if(isset($decompUntrtdUrl['host']) === false
            && isset($decompUntrtdUrl['path']) === false
            && isset($decompUntrtdUrl['fragment'])) // '~^#~'
        {
            return $originalUrl . $untreatedUrl;
        }

        if(isset($decompUntrtdUrl['scheme']) && isset($decompUntrtdUrl['host']))
        {
            return $untreatedUrl;
        }
    }

    /**
     * Get web-page content by absolute url
     *
     * @param $url
     * @return mixed HTML page content
     * @throws CollectPageRefsException
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

        $err_msg = curl_error($ch);
        $err_no = curl_errno($ch);

        curl_close($ch);

        if ($page === false)
        {
            throw new CollectPageRefsException($err_msg, $err_no);
        }

        return $page;
    }

    /**
     * Echoes string to console
     * @param $str
     */
    private function _printLine($str)
    {
        echo $str . PHP_EOL;
    }
}

/*
 * Time: 0.002801 - 0.002023
 * Memory: 9920
 * Difference between Memory Peaks: 8016
 */