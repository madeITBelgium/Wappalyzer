<?php

namespace MadeITBelgium\Wappalyzer;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

/**
 * MadeITBelgium Wappalyzer PHP Library.
 *
 * @version    1.0.0
 *
 * @copyright  Copyright (c) 2018 Made I.T. (https://www.madeit.be)
 * @author     Tjebbe Lievens <tjebbe.lievens@madeit.be>
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-3.txt    LGPL
 */
class Wappalyzer
{
    private $client;

    private $categories;
    private $groups;

    private $url;
    private $html;
    private $headers;
    private $cookies;
    private $js;

    private $apps;
    private $jsPatterns;

    private $detected = [];

    public function __construct($client = null)
    {
        if ($client === null) {
            $this->client = new Client(['cookies' => true]);
        } else {
            $this->client = $client;
        }

        $files = array_diff(scandir(__DIR__ . '/technologies'), array('..', '.'));

        $this->apps = [];
        foreach ($files as $file) {
            $appData = json_decode(file_get_contents(__DIR__ . '/technologies/' . $file), true);
            $this->apps = array_merge($this->apps, $appData);
        }

        $this->categories = json_decode(file_get_contents(__DIR__ . '/categories.json'), true);
        $this->groups = json_decode(file_get_contents(__DIR__ . '/groups.json'), true);

        $this->parseJsPatterns();
    }

    public function analyze($url, $html = null, $headers = null)
    {
        $this->url = $url;
        
        if($html !== null) {
            $this->html = $html;
            $this->headers = $headers;
        }
        else if ($this->client === false) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_URL, $url);

            $response = curl_exec($ch);

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->headers = substr($response, 0, $header_size);
            $this->html = substr($response, $header_size);

            $this->headers = $this->getHeadersFromResponse($this->headers);
        } else {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() < 400) {
                $this->html = (string) $response->getBody();
                $this->headers = $this->fixHeaders($response->getHeaders());

                $cookieJar = $this->client->getConfig('cookies');
                $this->cookies = $cookieJar->toArray();

                $this->js = $this->client->getConfig('js') ?? [];
            } else {
                throw new Exception("Can't load URL.");
            }
        }

        // Additional information
        $language = null;

        if ($this->html) {
            if (!is_string($this->html)) {
                $this->html = '';
            }

            preg_match('/<html[^>]*[: ]lang="([a-z]{2}(([-_])[A-Z]{2})?)"/i', $this->html, $matches);

            $language = is_array($matches) && count($matches) > 0 ? $matches[1] : null;
        }

        $this->doAnalyze($this->apps);

        return [
            'url' => $url,
            'language' => $language,
            'detected' => $this->detected,
        ];
    }

    public function doAnalyze($apps)
    {
        foreach ($apps as $appName => $app) {
            $this->analyzeUrl($appName, $app);
            $this->analyzeHtml($appName, $app);
            $this->analyzeMeta($appName, $app);
            $this->analyzeHeaders($appName, $app);
            $this->analyzeScripts($appName, $app);
            $this->analyzeCookies($appName, $app);
            $this->analyzeJs($appName, $app);
            $this->analyzeDom($appName, $app);
            $this->analyzeXhr($appName, $app);
        }

        $this->resolveExcludes();
        $implies = $this->resolveImplies();

        if (empty($implies)) {
            return true;
        }

        return $this->doAnalyze($implies);
    }

    private function fixHeaders($headers)
    {
        $h = [];
        foreach ($headers as $k => $v) {
            $h[strtolower($k)] = $v;
        }
        return $h;
    }

    public function getHeadersFromResponse($response)
    {
        $headers = [];

        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                try {
                    list($key, $value) = explode(': ', $line);

                    $headers[strtolower($key)] = $value;
                } catch (Exception $e) {
                }
            }
        }

        return $headers;
    }

    /**
     * Enclose string in array
     */
    public function asArray($value)
    {
        return is_array($value) ? $value : [ $value ];
    }

    /**
     * Wappalizer categories
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Wappalizer groups
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Parse apps.json patterns
     */
    public function parsePatterns($patterns, $escape = true)
    {
        if (!$patterns) {
            return [];
        }

        $parsed = [];

        // Convert string to object containing array containing string
        if (is_string($patterns) || is_array($patterns)) {
            $patterns = $this->asArray($patterns);

            foreach ($patterns as $key => $pattern) {
                $attrs = [];
                if (!is_array($pattern)) {
                    foreach (explode('\\;', $pattern) as $i => $attr) {
                        if ($i) {
                            // Key value pairs
                            $attr = explode(":", $attr);

                            if (count($attr) > 1) {
                                $attrs[array_shift($attr)] = $attr;
                            }
                        } else {
                            $attrs['string'] = $attr;

                            try {
                                if ($escape) {
                                    $attrs['regex'] = str_replace('/', '\/', $attr); // Escape slashes in regular expression
                                } else {
                                    $attrs['regex'] = $attr;
                                }
                            } catch (Exception $e) {
                                $attrs['regex'] = '';
                            }
                        }
                    }
                }

                $parsed[$key] = $attrs;
            }
        }

        return $parsed;
    }

    /**
     * Parse JavaScript patterns
     */
    public function parseJsPatterns()
    {
        foreach (array_keys($this->apps) as $appName) {
            if (isset($this->apps[$appName]['js'])) {
                $this->jsPatterns[$appName] = $this->parsePatterns($this->apps[$appName]['js']);
            }
        }
    }

    public function resolveExcludes()
    {
        $excludes = [];

        // Exclude app in detected apps only
        foreach ($this->detected as $appName => $app) {
            if (isset($app['excludes'])) {
                $excludes = array_merge($excludes, $this->asArray($app['excludes']));
            }
        }

        // Remove excluded applications
        foreach ($excludes as $appName) {
            if (array_key_exists($appName, $this->apps)) {
                unset($this->apps[$appName]);
            }
            if (array_key_exists($appName, $this->detected)) {
                unset($this->detected[$appName]);
            }
        }
    }

    public function resolveImplies()
    {
        $keys = ['implies', 'requires'];
        $implies = [];

        // Implied and required applications
        foreach ($this->detected as $app) {
            $implications = [];
            foreach ($keys as $key) {
                if (isset($app[$key])) {
                    $implications = array_merge($implications, $this->asArray($app[$key]));
                }
            }

            foreach (array_unique($implications) as $implication) {
                $appName = $this->parsePatterns($implication)[0]['string'];

                if (!isset($this->apps[$appName])) {
                    continue;
                }

                if (!array_key_exists($appName, $implies) && !array_key_exists($appName, $this->detected)) {
                    $implies[$appName] = $this->apps[$appName];
                    $this->detected[$appName] = $this->apps[$appName];
                }
            }
        }

        return $implies;
    }

    /**
     * Analyze URL
     */
    public function analyzeUrl($appName, $app)
    {
        if (!isset($app['url'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['url']);

        if (empty($patterns)) {
            return;
        }

        foreach ($patterns as $pattern) {
            if (preg_match('~' . $pattern['regex'] . '~i', $this->url)) {
                $this->addDetected($appName, $app, $pattern, 'url', $this->url);
            }
        }
    }

    /**
     * Analyze HTML
     */
    public function analyzeHtml($appName, $app)
    {
        if (!isset($app['html'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['html'], false);

        if (empty($patterns)) {
            return;
        }

        foreach ($patterns as $pattern) {
            if (@preg_match('~' . $pattern['regex'] . '~i', $this->html)) {
                $this->addDetected($appName, $app, $pattern, 'html', $this->html);
            }
        }
    }

    /**
     * Analyze meta tag
     */
    public function analyzeMeta($appName, $app)
    {
        if (!isset($app['meta'])) {
            return;
        }

        $regex = "/<meta[^>]+>/i";
        $patterns = $this->parsePatterns($app['meta']);

        if ($patterns && (preg_match_all($regex, $this->html, $matches))) {
            foreach ($matches as $matchs) {
                foreach ($matchs as $match) {
                    foreach ($patterns as $key => $meta) {
                        $r = '/(?:name|property)=["\']' . $key . '["\']/i';

                        if (preg_match($r, $match)) {
                            preg_match("/content=([\"'])([^\"']+)([\"'])/i", $match, $content);

                            foreach ($patterns as $patternKey => $pattern) {
                                if (!isset($pattern['regex'])) {
                                    continue;
                                }
                                if ($patternKey === $key && $content && count($content) === 4 && preg_match('~' . $pattern['regex'] . '~i', $content[2])) {
                                    $this->addDetected($appName, $app, $pattern, 'meta', $content[2], $meta);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Analyze response headers
     */
    public function analyzeHeaders($appName, $app)
    {
        if (!isset($app['headers'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['headers']);

        if (empty($patterns)) {
            return;
        }

        foreach ($patterns as $headerName => $pattern) {
            $headerName = strtolower($headerName);

            if (array_key_exists($headerName, $this->headers)) {
                $headerValue = $this->headers[$headerName];
                if (is_array($headerValue)) {
                    $headerValue = $headerValue[0];
                }
                if (preg_match('~' . $pattern['regex'] . '~i', $headerValue)) {
                    $this->addDetected($appName, $app, $pattern, 'headers', $headerValue, $headerName);
                }
            }
        }
    }

    /**
     * Analyze script tag
     */
    public function analyzeScripts($appName, $app)
    {
        if (!isset($app['scriptSrc'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['scriptSrc']);

        if (empty($patterns)) {
            return;
        }

        $regex = "/<script[^>]+>/i";

        if ($patterns && (preg_match_all($regex, $this->html, $matches))) {
            foreach ($matches as $matchs) {
                foreach ($matchs as $match) {
                    foreach ($patterns as $pattern) {
                        $r = '~src=["\'].*' . $pattern['regex'] . '.*["\']~i';

                        if (preg_match($r, $match)) {
                            $this->addDetected($appName, $app, $pattern, 'scriptSrc', $match);
                        }
                    }
                }
            }
        }
    }

    /**
     * Analyze cookies
     */
    public function analyzeCookies($appName, $app)
    {
        if (!isset($app['cookies'])) {
            return;
        }
        $patterns = $this->parsePatterns($app['cookies']);

        if (empty($patterns)) {
            return;
        }

        foreach ($patterns as $patternName => $pattern) {
            $patternName = strtolower($patternName);
            foreach ($this->cookies as $cookie) {
                if ($patternName === strtolower($cookie['Name'])) {
                    if (preg_match('/' . $pattern['regex'] . '/i', $cookie['Value'])) {
                        $this->addDetected($appName, $app, $pattern, 'cookies', $cookie, $cookie['Name']);
                    }
                }
            }
        }
    }

    /**
     * Analyze JavaScript variables
     */
    public function analyzeJs($appName, $app)
    {
        if (!isset($app['js'])) {
            return;
        }

        foreach ($this->jsPatterns[$appName] as $key => $jsPattern) {
            if (in_array($key, $this->js, true)) {
                $this->addDetected($appName, $app, $key, 'js', null);
            }
        }
    }

    /**
     * Analyze DOM
     */
    public function analyzeDom($appName, $app)
    {
        if (!isset($app['dom'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['dom']);

        if (empty($patterns)) {
            return;
        }

        $crawler = new Crawler($this->html);

        foreach ($patterns as $pattern) {
            foreach ($pattern as $selector) {
                try {
                    // Handle malformed expressions
                    $results = $crawler->filter($selector);
                } catch (Exception $e) {
                    continue;
                }

                if ($results->count() > 0) {
                    $this->addDetected($appName, $app, $selector, 'dom', null);
                }
            }
        }
    }

    /**
     * Analyze XHR
     */
    public function analyzeXhr($appName, $app)
    {
        if (!isset($app['xhr'])) {
            return;
        }

        $patterns = $this->parsePatterns($app['xhr'], false);

        if (empty($patterns)) {
            return;
        }

        foreach ($patterns as $pattern) {
            if (@preg_match('~' . $pattern['regex'] . '~i', $this->html)) {
                $this->addDetected($appName, $app, $pattern, 'xhr', $this->html);
            }
        }
    }

    /**
     * Mark application as detected, set confidence and version
     */
    public function addDetected($appName, $app, $pattern, $type, $value, $key = null)
    {
        $this->apps[$appName]['detected'] = true;

        // Detect version number
        if (isset($pattern['version'])) {
            $versions = [];
            $version  = $pattern['version'];

            if (preg_match('~' . $pattern['regex'] . '~i', $value, $matches)) {
                if (isset($matches[1])) {
                    $version = trim($matches[1]);

                    if ($version !== '' && !in_array($version, $versions, true)) {
                        $versions[] = $version;
                    }
                }

                if (count($versions)) {
                    $this->apps[$appName]['version'] = $versions[count($versions) - 1];
                }
            }
        }

        $this->detected[$appName] = $this->apps[$appName];
    }
}
