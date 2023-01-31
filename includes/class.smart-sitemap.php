<?php
 
# https://github.com/samdark/sitemap

use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;

class SmartSitemap 
{
    protected $sitemapPath  = null;
    protected $siteUrl      = null;
    protected $postTypes    = ['post', 'page', 'product'];

    public function __construct()
    { 
        $this->sitemapPath = ABSPATH.'/sitemaps/';
        $this->siteUrl = 'https://optimsithub.com/';
        self::init();
    }

    private function init()
    {   
        self::createDir($this->sitemapPath);

        foreach($this->postTypes as $type)
        {
            self::generateSitemap($type);
        }
    }

    private function generateSitemap($type)
    {
        // create sitemap
        $sitemap = new Sitemap($this->sitemapPath .'/' .$type.'-sitemap.xml');

        // add some URLs
        $sitemap->addItem('http://example.com/mylink1');
        $sitemap->addItem('http://example.com/mylink2', time());
        $sitemap->addItem('http://example.com/mylink3', time(), Sitemap::HOURLY);
        $sitemap->addItem('http://example.com/mylink4', time(), Sitemap::DAILY, 0.3);
        $sitemap->setMaxUrls(1);

        // write it
        $sitemap->write();
    }

    private function createDir()
    {
        @mkdir($this->sitemapPath);
    }
} 

new SmartSitemap();
