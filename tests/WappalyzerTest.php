<?php

use PHPUnit\Framework\TestCase;
use MadeITBelgium\Wappalyzer\Wappalyzer;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class WappalyzerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testHtml()
    {
        $jar = new CookieJar;
        $cookieJar = $jar->fromArray(['laravel_session' => 'ABC'], 'localhost');
        $mock = new MockHandler([
            new Response(200, [
                'cache-control' => 'no-store, no-cache, must-revalidate',
                'content-encoding' => 'gzip',
                'content-type' => 'text/html; charset="UTF-8"',
                'date' => 'Tue, 17 Jul 2018 12:31:08 GMT',
                'expires' => 'Thu, 19 Nov 1981 08:52:00 GMT',
                'link' => '<https://localhost/wp-json/>; rel="https://api.w.org/"',
                'link' => '<https://localhost/>; rel=shortlink',
                'pragma' => 'no-cache',
                'status' => '200',
                'vary' => 'Accept-Encoding,Cookie',
                'x-powered-by' => 'PHP/7.2.7',
                'x-github-request-id' => 'DA9A:AF74:3CF1A7:47D586:615EFC90'
            ], file_get_contents(__DIR__ . '/site.html')),
        ]);
        
        
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'cookies' => $cookieJar]);
        
        $wappalyzer = new Wappalyzer($client);
        $this->assertEquals([
            'url' => 'https://www.madeit.be/',
            'language' => 'nl-BE',
            'detected' => [
                /*
                'Font Awesome' => [
                    'cats' => [17],
                    'detected' => true,
                    "description" => "Font Awesome is a font and icon toolkit based on CSS and Less.",
                    "html" => [
                        "<link[^>]* href=[^>]+(?:([\\d.]+)/)?(?:css/)?font-awesome(?:\\.min)?\\.css\\;version:\\1",
                        "<link[^>]* href=[^>]*kit\\-pro\\.fontawesome\\.com/releases/v([0-9.]+)/\\;version:\\1"
                    ],
                    "icon" => "font-awesome.svg",
                    "js" => [
                        "FontAwesomeCdnConfig" => "",
                        "___FONT_AWESOME___" => ""
                    ],
                    "pricing" => [
                        "low",
                        "freemium",
                        "recurring"
                    ],
                    "scriptSrc" => [
                        "(?:F|f)o(?:n|r)t-?(?:A|a)wesome(?:.*?([0-9a-fA-F]{7,40}|[\\d]+(?:.[\\d]+(?:.[\\d]+)?)?)|)",
                        "\\.fontawesome\\.com/([0-9a-z]+).js"
                    ],
                    "website" => "https://fontawesome.com/"
                ],
                */
                'PHP' => [
                    'cats' => [27],
                    'cookies' => ['PHPSESSID' => ''],
                    'headers' => [
                        'Server' => 'php/?([\d.]+)?\;version:\1',
                        'X-Powered-By' => '^php/?([\d.]+)?\;version:\1',
                    ],
                    'icon' => 'PHP.svg',
                    'url' => '\.php(?:$|\?)',
                    'website' => 'https://php.net',
                    'detected' => true,
                    'version' => '7.2.7',
                    'cpe' => 'cpe:2.3:a:php:php:*:*:*:*:*:*:*:*',
                    "description" => "PHP is a general-purpose scripting language used for web development.",
                ],
                'WordPress' => [
                    'cats' => [
                        0 => 1,
                        1 => 11,
                    ],
                    'html' => [
                        0 => '<link rel=["\']stylesheet["\'] [^>]+/wp-(?:content|includes)/',
                        1 => '<link[^>]+s\d+\.wp\.com',
                    ],
                    'icon' => 'WordPress.svg',
                    'implies' => [
                        0 => 'PHP',
                        1 => 'MySQL'
                    ],
                    'js' => [
                        'wp_username' => ''
                    ],
                    'meta' => [
                        'generator' => '^WordPress(?: ([\d.]+))?\;version:\1',
                        "shareaholic:wp_version" => ""
                    ],
                    'website' => 'https://wordpress.org',
                    'detected' => true,
                    'version' =>  '4.9.7',
                    'headers' => [
                        'link' => 'rel="https://api\.w\.org/"',
                        'X-Pingback' => '/xmlrpc\.php$',
                    ],
                    "cpe" => "cpe:2.3:a:wordpress:wordpress:*:*:*:*:*:*:*:*",
                    "description" => "WordPress is a free and open-source content management system written in PHP and paired with a MySQL or MariaDB database. Features include a plugin architecture and a template system.",
                    "pricing" => [
                        "low",
                        "recurring",
                        "freemium"
                    ],
                    "saas" => true,
                    "scriptSrc" => [
                        "/wp-(?:content|includes)/",
                        "wp-embed\\.min\\.js"
                    ],
                    'oss' => true,
                ],
                'Yoast SEO' => [
                    'cats' => [ 54, 87 ],
                    "description" => "Yoast SEO is a search engine optimisation plugin for WordPress and other platforms.",
                    "dom" => [
                        "script.yoast-schema-graph" => [
                            "attributes" => [
                                "class" => ""
                            ]
                        ]
                    ],
                    "html" => [
                        '<!-- This site is optimized with the Yoast (?:WordPress )?SEO plugin v([^\s]+) -\;version:\1',
                        '<!-- This site is optimized with the Yoast SEO Premium plugin v(?:[^\s]+) \(Yoast SEO v([^\s]+)\) -\;version:\1',
                    ],
                    "icon" => "Yoast SEO.png",
                    "website" => "https://yoast.com/wordpress/plugins/seo/",
                    'detected' => true,
                    'version' => '7.8',
                    'implies' => 'WordPress',
                    'oss' => true
                ],
                'MySQL' => [
                    'cats' => [ 34 ],
                    'icon' => 'MySQL.svg',
                    'website' => 'https://mysql.com',
                    'cpe' => 'cpe:2.3:a:mysql:mysql:*:*:*:*:*:*:*:*',
                    "description" => "MySQL is an open-source relational database management system.",
                ],
                'Laravel' => [
                    'cats' => [18],
                    'cookies' => [
                        'laravel_session' => ''
                    ],
                    'icon' => 'Laravel.svg',
                    'implies' => 'PHP',
                    'js' => [
                        'Laravel' => ''
                    ],
                    'website' => 'https://laravel.com',
                    'detected' => true,
                    'cpe' => 'cpe:2.3:a:laravel:laravel:*:*:*:*:*:*:*:*',
                    "description" => "Laravel is a free, open-source PHP web framework.",
                    'oss' => true,
                ],
                'GitHub Pages' => [
                    'cats' => [
                        0 => 62,
                    ],
                    'description' => 'GitHub Pages is a static site hosting service.',
                    'headers' => [
                        'Server' => '^GitHub\\.com$',
                        'X-GitHub-Request-Id' => '',
                    ],
                    'icon' => 'GitHub.svg',
                    'url' => '^https?://[^/]+\\.github\\.io',
                    'website' => 'https://pages.github.com/',
                    'detected' => true,
                ],
                'reCAPTCHA' => [
                    'cats' => [
                        16,
                    ],
                    'icon' => 'reCAPTCHA.svg',
                    'js' => [
                        'Recaptcha' => '',
                        'recaptcha' => '',
                    ],
                    'scriptSrc' => [
                        'api-secure\\.recaptcha\\.net',
                        'recaptcha_ajax\\.js',
                        '/recaptcha/(?:api|enterprise)\.js',
                    ],
                    'website' => 'https://www.google.com/recaptcha/',
                    'detected' => true,
                    'description' => 'reCAPTCHA is a free service from Google that helps protect websites from spam and abuse.',
                    'dom' => "#recaptcha_image, iframe[src*='.google.com/recaptcha/'], div.g-recaptcha",
                    'pricing' => [
                        'freemium',
                        'payg',
                        'poa'
                    ],
                    'saas' => true,
                    'scripts' => '/recaptcha/api\.js',
                ],
                'Facebook Pixel' => [
                    'cats' => [
                        10,
                    ],
                    'description' => 'Facebook pixel is an analytics tool that allows you to measure the effectiveness of your advertising.',
                    'dom' => 'img[src*=\'facebook.com/tr\']',
                    'icon' => 'Facebook.svg',
                    'js' => [
                        '_fbq' => '',
                    ],
                    'scriptSrc' => [
                        'connect\\.facebook.\\w+/signals/config/\\d+\\?v=([\\d\\.]+)\\;version:\\1',
                        'connect\\.facebook\\.\\w+/.+/fbevents\\.js',
                    ],
                    'website' => 'https://facebook.com',
                    'detected' => true,
                ],
                'cdnjs' => [
                    'cats' => [
                        31
                    ],
                    'description' => 'cdnjs is a free distributed JS library delivery service.',
                    'dom' => "link[href*='cdnjs.cloudflare.com/']",
                    'icon' => 'cdnjs.svg',
                    'implies' => 'Cloudflare',
                    'oss' => true,
                    'scriptSrc' => 'cdnjs\.cloudflare\.com',
                    'website' => 'https://cdnjs.com',
                    'detected' => true,
                ],
                'Cloudflare' => [
                    'cats' => [
                        31
                    ],
                    'cookies' => [
                        '__cfduid' => '',
                    ],
                    'description' => 'Cloudflare is a web-infrastructure and website-security company, providing content-delivery-network services, DDoS mitigation, Internet security, and distributed domain-name-server services.',
                    'dns' => [
                        'NS' => '\.cloudflare\.com',
                        'SOA' => '\.cloudflare\.com',
                    ],
                    'dom' => "img[src*='//cdn.cloudflare']",
                    'headers' => [
                        'Server' => '^cloudflare$',
                        'cf-cache-status' => '',
                        'cf-ray' => ''
                    ],
                    'icon' => 'CloudFlare.svg',
                    'js' => [
                        'CloudFlare' => '',
                    ],
                    'meta' => [
                        'image' => '//cdn\.cloudflare',
                    ],
                    'website' => 'https://www.cloudflare.com',
                ]
            ]
        ], $wappalyzer->analyze('https://www.madeit.be/'));
    }

    public function testHtmlFile()
    {
        $jar = new CookieJar;
        $cookieJar = $jar->fromArray(['laravel_session' => 'ABC'], 'localhost');
        $js = ['adroll_adv_id', 'foo', 'bar'];
        $mock = new MockHandler([
            new Response(200, [
                'cache-control' => 'no-store, no-cache, must-revalidate',
                'content-encoding' => 'gzip',
                'content-type' => 'text/html; charset="UTF-8"',
                'date' => 'Tue, 17 Jul 2018 12:31:08 GMT',
                'expires' => 'Thu, 19 Nov 1981 08:52:00 GMT',
                'link' => '<https://localhost/wp-json/>; rel="https://api.w.org/"',
                'link' => '<https://localhost/>; rel=shortlink',
                'pragma' => 'no-cache',
                'status' => '200',
                'vary' => 'Accept-Encoding,Cookie',
                'x-powered-by' => 'PHP/7.2.7',
            ], file_get_contents(__DIR__ . '/site.html')),
        ]);
        
        
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler, 'cookies' => $cookieJar, 'js' => $js]);
        
        $wappalyzer = new Wappalyzer($client);
        $this->assertEquals([
            'url' => 'https://www.madeit.be/',
            'language' => 'nl-BE',
            'detected' => [
                /*'Font Awesome' => [
                    'cats' => [17],
                    'detected' => true,
                    "description" => "Font Awesome is a font and icon toolkit based on CSS and Less.",
                    "html" => [
                        "<link[^>]* href=[^>]+(?:([\\d.]+)/)?(?:css/)?font-awesome(?:\\.min)?\\.css\\;version:\\1",
                        "<link[^>]* href=[^>]*kit\\-pro\\.fontawesome\\.com/releases/v([0-9.]+)/\\;version:\\1"
                    ],
                    "icon" => "font-awesome.svg",
                    "js" => [
                        "FontAwesomeCdnConfig" => "",
                        "___FONT_AWESOME___" => ""
                    ],
                    "pricing" => [
                        "low",
                        "freemium",
                        "recurring"
                    ],
                    "scriptSrc" => [
                        "(?:F|f)o(?:n|r)t-?(?:A|a)wesome(?:.*?([0-9a-fA-F]{7,40}|[\\d]+(?:.[\\d]+(?:.[\\d]+)?)?)|)",
                        "\\.fontawesome\\.com/([0-9a-z]+).js"
                    ],
                    "website" => "https://fontawesome.com/"
                ],*/
                'PHP' => [
                    'cats' => [27],
                    'cookies' => ['PHPSESSID' => ''],
                    'headers' => [
                        'Server' => 'php/?([\d.]+)?\;version:\1',
                        'X-Powered-By' => '^php/?([\d.]+)?\;version:\1',
                    ],
                    'icon' => 'PHP.svg',
                    'url' => '\.php(?:$|\?)',
                    'website' => 'https://php.net',
                    'detected' => true,
                    'version' => '7.2.7',
                    'cpe' => 'cpe:2.3:a:php:php:*:*:*:*:*:*:*:*',
                    "description" => "PHP is a general-purpose scripting language used for web development.",
                ],
                'WordPress' => [
                    'cats' => [
                        0 => 1,
                        1 => 11,
                    ],
                    'html' => [
                        0 => '<link rel=["\']stylesheet["\'] [^>]+/wp-(?:content|includes)/',
                        1 => '<link[^>]+s\d+\.wp\.com',
                    ],
                    'icon' => 'WordPress.svg',
                    'implies' => [
                        0 => 'PHP',
                        1 => 'MySQL'
                    ],
                    'js' => [
                        'wp_username' => ''
                    ],
                    'meta' => [
                        'generator' => '^WordPress(?: ([\d.]+))?\;version:\1',
                        "shareaholic:wp_version" => ""
                    ],
                    'website' => 'https://wordpress.org',
                    'detected' => true,
                    'version' =>  '4.9.7',
                    'headers' => [
                        'link' => 'rel="https://api\.w\.org/"',
                        'X-Pingback' => '/xmlrpc\.php$',
                    ],
                    "cpe" => "cpe:2.3:a:wordpress:wordpress:*:*:*:*:*:*:*:*",
                    "description" => "WordPress is a free and open-source content management system written in PHP and paired with a MySQL or MariaDB database. Features include a plugin architecture and a template system.",
                    "pricing" => [
                        "low",
                        "recurring",
                        "freemium"
                    ],
                    "saas" => true,
                    "scriptSrc" => [
                        "/wp-(?:content|includes)/",
                        "wp-embed\\.min\\.js"
                    ],
                    'oss' => true,
                ],
                'Yoast SEO' => [
                    'cats' => [ 54, 87 ],
                    "description" => "Yoast SEO is a search engine optimisation plugin for WordPress and other platforms.",
                    "dom" => [
                        "script.yoast-schema-graph" => [
                            "attributes" => [
                                "class" => ""
                            ]
                        ]
                    ],
                    "html" => [
                        '<!-- This site is optimized with the Yoast (?:WordPress )?SEO plugin v([^\s]+) -\;version:\1',
                        '<!-- This site is optimized with the Yoast SEO Premium plugin v(?:[^\s]+) \(Yoast SEO v([^\s]+)\) -\;version:\1',
                    ],
                    "icon" => "Yoast SEO.png",
                    'website' => 'https://yoast.com/wordpress/plugins/seo/',
                    'detected' => true,
                    'version' => '7.8',
                    'implies' => 'WordPress',
                    'oss' => true,
                ],
                'MySQL' => [
                    'cats' => [ 34 ],
                    'icon' => 'MySQL.svg',
                    'website' => 'https://mysql.com',
                    'cpe' => 'cpe:2.3:a:mysql:mysql:*:*:*:*:*:*:*:*',
                    "description" => "MySQL is an open-source relational database management system.",
                ],
                'Laravel' => [
                    'cats' => [18],
                    'cookies' => [
                        'laravel_session' => ''
                    ],
                    'icon' => 'Laravel.svg',
                    'implies' => 'PHP',
                    'js' => [
                        'Laravel' => ''
                    ],
                    'website' => 'https://laravel.com',
                    'detected' => true,
                    'cpe' => 'cpe:2.3:a:laravel:laravel:*:*:*:*:*:*:*:*',
                    "description" => "Laravel is a free, open-source PHP web framework.",
                    'oss' => true,
                ],
                'AdRoll' => [
                    'cats' => [
                        0 => 36,
                        1 => 77,
                    ],
                    'description' => 'AdRoll is a digital marketing technology platform that specializes in retargeting.',
                    'icon' => 'AdRoll.svg',
                    'js' => [
                        'adroll_adv_id' => '',
                        'adroll_pix_id' => '',
                        'adroll_version' => '([\d\.]+)\;version:\1'
                    ],
                    'pricing' => [
                        0 => 'low',
                        1 => 'recurring',
                    ],
                    'saas' => true,
                    'scriptSrc' => '(?:a|s)\\.adroll\\.com',
                    'website' => 'https://adroll.com',
                    'detected' => true,
                    'dom' => "link[href*='.adroll.com']",
                ],
                'reCAPTCHA' => [
                    'cats' => [
                        16,
                    ],
                    'icon' => 'reCAPTCHA.svg',
                    'js' => [
                        'Recaptcha' => '',
                        'recaptcha' => '',
                    ],
                    'scriptSrc' => [
                        'api-secure\\.recaptcha\\.net',
                        'recaptcha_ajax\\.js',
                        '/recaptcha/(?:api|enterprise)\.js',
                    ],
                    'website' => 'https://www.google.com/recaptcha/',
                    'detected' => true,
                    'description' => 'reCAPTCHA is a free service from Google that helps protect websites from spam and abuse.',
                    'dom' => "#recaptcha_image, iframe[src*='.google.com/recaptcha/'], div.g-recaptcha",
                    'pricing' => [
                        'freemium',
                        'payg',
                        'poa'
                    ],
                    'saas' => true,
                    'scripts' => '/recaptcha/api\.js',
                ],
                'Facebook Pixel' => [
                    'cats' => [
                        10,
                    ],
                    'description' => 'Facebook pixel is an analytics tool that allows you to measure the effectiveness of your advertising.',
                    'dom' => 'img[src*=\'facebook.com/tr\']',
                    'icon' => 'Facebook.svg',
                    'js' => [
                        '_fbq' => '',
                    ],
                    'scriptSrc' => [
                        'connect\\.facebook.\\w+/signals/config/\\d+\\?v=([\\d\\.]+)\\;version:\\1',
                        'connect\\.facebook\\.\\w+/.+/fbevents\\.js',
                    ],
                    'website' => 'https://facebook.com',
                    'detected' => true,
                ],
                'cdnjs' => [
                    'cats' => [
                        31
                    ],
                    'description' => 'cdnjs is a free distributed JS library delivery service.',
                    'dom' => "link[href*='cdnjs.cloudflare.com/']",
                    'icon' => 'cdnjs.svg',
                    'implies' => 'Cloudflare',
                    'oss' => true,
                    'scriptSrc' => 'cdnjs\.cloudflare\.com',
                    'website' => 'https://cdnjs.com',
                    'detected' => true,
                ],
                'Cloudflare' => [
                    'cats' => [
                        31
                    ],
                    'cookies' => [
                        '__cfduid' => '',
                    ],
                    'description' => 'Cloudflare is a web-infrastructure and website-security company, providing content-delivery-network services, DDoS mitigation, Internet security, and distributed domain-name-server services.',
                    'dns' => [
                        'NS' => '\.cloudflare\.com',
                        'SOA' => '\.cloudflare\.com',
                    ],
                    'dom' => "img[src*='//cdn.cloudflare']",
                    'headers' => [
                        'Server' => '^cloudflare$',
                        'cf-cache-status' => '',
                        'cf-ray' => ''
                    ],
                    'icon' => 'CloudFlare.svg',
                    'js' => [
                        'CloudFlare' => '',
                    ],
                    'meta' => [
                        'image' => '//cdn\.cloudflare',
                    ],
                    'website' => 'https://www.cloudflare.com',
                ]
            ]
        ], $wappalyzer->analyze('https://www.madeit.be/'));
    }
}
