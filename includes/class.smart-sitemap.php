<?php
 
use samdark\sitemap\Sitemap;
use samdark\sitemap\Index;
 
class SmartSitemap 
{
    public $isActive     = null;
    public $sitemapPath  = null;
    public $siteUrl      = null;
    public $postTypes    = null;
    public $expiration   = null;
    public $trigger      = null;
    public $options      = null;
    public $size         = 1000;

    public function __construct()
    {  
        $this->options      = get_option('smartsitemap_basic');  

        $this->sitemapPath  = ABSPATH.'/sitemaps/';
        $this->siteUrl      = sanitize_url('https://' . $_SERVER['HTTP_HOST'] .'/');
        
        $this->expiration   = @strtotime(data_get($this->options, 'ttl'),'-1 days'); 
        $this->postTypes    = array_keys(data_get($this->options, 'posttypes'));
        $this->isActive     = data_get($this->options, 'is_active', 'no'); 
        $this->trigger      = data_get($this->options, 'auto_trigger', 'no'); 

        add_action( 'init', [$this, 'init']);
        add_action( 'save_post', [$this, 'triggerRegenerateSitemaps'], 99, 3 );
    }

    public function init()
    {   
        if($this->isActive && $this->isActive == 'yes')
        {
            self::createDir($this->sitemapPath); 
            self::cleanDirectory();
    
            foreach($this->postTypes as $type)
            {
                self::generateSitemap($type,'normal');
            }
    
            self::generateSitemapIndex();
        }
    }

    public function triggerRegenerateSitemaps()
    { 
        if($this->trigger && $this->trigger == 'yes')
        {
            self::createDir($this->sitemapPath);
            self::cleanDirectory();
    
            foreach($this->postTypes as $type)
            {
                self::generateSitemap($type,'trigger');
            }
    
            self::generateSitemapIndex();
        }
    }

    private function generateSitemap($type,$action)
    { 
        $filename = $this->sitemapPath .'/' .$type.'-sitemap.xml';
        $sitemap = new Sitemap($filename);

        $sitemap->setMaxUrls($this->size);
        $posts = [];
        $query = self::getPosts($type);
        $posts[$type] = data_get($query, 'posts');

        if($posts[$type])
        {
            foreach ($posts[$type] as $key => $value) 
            {
                $sitemap->addItem(get_permalink(data_get($value, 'ID')), strtotime(data_get($value,'post_date',time())));
            }
        }  

        if($action == 'trigger')
        {
            $sitemap->write();
        } else {
            if(!file_exists($filename))
            {
                $sitemap->write();
            }
    
            if (filectime($filename) > $this->expiration) {
                $sitemap->write();
            }
        }
    }

    private function cleanDirectory()
    {
        $files = glob($this->sitemapPath.'*');
        foreach($files as $file){  
            if(is_file($file)) {
                @unlink($file);  
            }
        }
    }

    private function createDir()
    {
        @mkdir($this->sitemapPath);
    }

    private function getPosts($postType)
    {
        return new WP_Query( 
            [
                'post_type'         => [$postType], 
                'post_status'       => 'publish',
                'posts_per_page'    => -1,
            ]    
        );
    }

    private function generateSitemapIndex()
    {
        $urls = [];

        $files = glob($this->sitemapPath.'*.xml');
        foreach($files as $file){  
            if(is_file($file)) {
                $urls[] = $file;
            }
        }

        $filename = $this->sitemapPath.'/sitemap-index.xml';

        $sitemap = new Index($filename);
        
        foreach($urls as $urzl)
        {  
            $url = self::formatUrl($urzl);
            $sitemap->addSitemap($this->siteUrl.''.$url, time(), Sitemap::DAILY, 0.3);
        } 
        
        $sitemap->write();  
    }

    private function formatUrl($url)
    {
        $url = explode('/', $url);
        $url = array_slice($url,-2,2,true);
        $url = implode('/',$url); 
        return $url;
    }
} 

new SmartSitemap();
