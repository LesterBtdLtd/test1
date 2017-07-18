<?php

if(!defined('STDIN')) exit;

include "PhpCliMod.php";
include "CollectPageRefsException.php";

class CollectPageRefs2
{
    private $description = 'Script for getting all unique hyperlinks on the web-page'.PHP_EOL.
        'Usage:'.PHP_EOL.
        '  php CollectPageRefs.php [OPTION] --url=<absolute-url>';
    private $phpCli;
    private $urls = [];

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

            $this->urls[] = $value;
        }

        $this->urls = array_unique($this->urls);
        foreach ($this->urls as $url)
        {
            $this->_printLine($url);
        }
        //$memoryUsage = memory_get_usage(false) - $memoryUsage;

        $this->urls = []; // reset array

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
 * Time: 0.001643 - 0.00154
 * Memory: 16192
 * Difference between Memory Peaks: 34440
 */