<?php
class SmartSitemapAdmin 
{

    private $settings_api;

    function __construct() {
        
        $this->settings_api = new WeDevs_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() 
    {
        add_options_page( 'Smart Sitemap Options', 'Smart Sitemap Options', 'delete_posts', 'smart-sitemap', [$this, 'optionRender'] );
    }

    function get_settings_sections() {
        
        $sections = [

            [
                'id' => 'smartsitemap_basic',
                'title' => 'Basic Settings'
            ],
            [
                'id' => 'smartsitemap_advenced',
                'title' => 'Advenced Settings'
            ],
        ];

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $fields =  [
            'smartsitemap_basic' => [
                [ 
                    'name'  => 'is_active',
                    'label' => __( 'Generate Sitemaps Smartly ?', 'smart-sitemap' ),
                    'desc'  => __( 'If you use Yoast SEO, or other plugins can disable our smart sitemap builder.',
                    'smart-sitemap' ),
                    'type'  => 'select',
                    'default' => 'no',
                    'options' =>
                    [
                        'yes' => 'Yes',
                        'no' => 'No'
                    ],
                ],
                [ 
                    'name'  => 'auto_trigger',
                    'label' =>  __( 'Regenarate sitemaps after post publish/update ?', 'smart-sitemap' ),
                    'desc'  =>  __( 'Regenarate sitemaps after post publish/update ?', 'smart-sitemap' ),
                    'type'  => 'select',
                    'default' => 'no',
                    'options' =>
                    [
                        'yes' => 'Yes',
                        'no' => 'No'
                    ],
                ],
                [
                    'name'  => 'ttl',
                    'label' => __( 'Sitemap Regeneration Time', 'smart-sitemap' ),
                    'desc'  => __( 'Default is 24 Hours.', 'smart-sitemap' ),
                    'type'  => 'select',
                    'default' => 'no',
                    'options' => 
                    [
                        '-1 days' => '24 Hours',
                        '-1 week' => '7 Days'
                    ],
                ],
                [
                    'name'    => 'posttypes',
                    'label'   => __( 'Select Post Types for sitemaps', 'smart-sitemap' ),
                    'desc'    => __( 'Select Post Types for smartly generated sitemaps', 'smart-sitemap' ),
                    'type'    => 'multicheck',
                    'default' => [
                        'post' => 'Posts',
                        'page' => 'Pages',
                    ],
                    'options' => [
                        'post' => 'Posts',
                        'page' => 'Pages',
                        'product' => 'Products',
                    ],
                ]
            ],
            'smartsitemap_advenced' => [
                [
                    'name' => 'cpt_info',
                    'label' => 'Custom Post Types',
                    'desc' => __( '<strong style="color:red">This feature will be avaliable on Premium Version.</strong>', 'smart-sitemap' ),
                    'type' => 'html'
                ],
                [
                    'name' => 'news_sitemap',
                    'label' => 'Google News Sitemap',
                    'desc' => __( '<strong style="color:red">This feature will be avaliable on Premium Version.</strong>', 'smart-sitemap' ),
                    'type' => 'html'
                ],
                [
                    'name' => 'image_sitemap',
                    'label' => 'Image Sitemap',
                    'desc' => __( '<strong style="color:red">This feature will be avaliable on Premium Version.</strong>', 'smart-sitemap' ),
                    'type' => 'html'
                ],
                [
                    'name' => 'merchant_center',
                    'label' => 'Google Shopping Sitemap',
                    'desc' => __( '<strong style="color:red">This feature will be avaliable on Premium Version.</strong>', 'smart-sitemap' ),
                    'type' => 'html'
                ],
                [
                    'name' => 'add_robots',
                    'label' => 'Add Sitemap Links to robots.txt by Smart Sitemap ',
                    'desc' => __( '<strong style="color:red">This feature will be avaliable on Premium Version.</strong>', 'smart-sitemap' ),
                    'type' => 'html'
                ],
            ]
        ];
        return $fields;
    }

    function optionRender() 
    {
        echo '<div class="wrap">'; 
            $this->settings_api->show_navigation();
            $this->settings_api->show_forms();
        echo '</div>';
    } 

}
new SmartSitemapAdmin();