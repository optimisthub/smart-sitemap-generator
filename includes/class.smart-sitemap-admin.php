<?php
 
class SmartSitemapAdmin 
{

    public $optionPrefix = 'smartsitemap';
    public $defaults = [
        'is_active' => 'yes',
        'auto_trigger' => 'yes',
        'ttl' => '-1 days',
        'posttypes' => [
            'post', 'page', 'product'
        ]
    ];

    function __construct() { 
 
        add_action( 'admin_init', [$this, 'admin_init'] );
        add_action( 'admin_menu', [$this, 'admin_menu'] );
    }

    function admin_init() { 
        
        register_setting( $this->optionPrefix.'-setting', $this->optionPrefix.'__options');
        
        add_settings_section($this->optionPrefix.'_section', __( 'Smart Sitemaps Options', 'smart-sitemap' ), [], $this->optionPrefix.'-setting' );
        
        add_settings_field( 
            'is_active', 
            __( 'Generate sitemaps smartly ?', 'smart-sitemap' ),
            [$this, 'selectCallback'],
            $this->optionPrefix.'-setting', $this->optionPrefix.'_section',
            [
                'options' => [
                    'yes' => __('Yes', 'smart-sitemap'),
                    'no' => __('No', 'smart-sitemap'),
                ],
                'id' => 'is_active'
            ]
        );

        add_settings_field( 
            'auto_trigger',
            __( 'Regenerate sitemaps after update/publish posts ?', 'smart-sitemap' ),
            [$this, 'selectCallback'],
            $this->optionPrefix.'-setting', $this->optionPrefix.'_section',
            [
                'options' => [
                    'yes' => __('Yes', 'smart-sitemap'),
                    'no' => __('No', 'smart-sitemap'),
                ],
                'id' => 'auto_trigger'
            ]
        );

        add_settings_field( 
            'ttl',
            __( 'Sitemaps regeneration time ?', 'smart-sitemap' ),
            [$this, 'selectCallback'],
            $this->optionPrefix.'-setting', $this->optionPrefix.'_section' ,
            [
                'options' => [
                    '-1 days' => __('24 Hours', 'smart-sitemap'),
                    '-7 days' => __('1 Week', 'smart-sitemap'),
                    '-15 days' => __('15 Days', 'smart-sitemap'),
                    '-30 days' => __('30 Days', 'smart-sitemap'),
                ],
                'id' => 'ttl'
            ]
        );

        add_settings_field( 
            'posttypes',
            __( 'Select Post Types for sitemaps', 'smart-sitemap' ),
            [$this, 'checkboxCallback'],
            $this->optionPrefix.'-setting', $this->optionPrefix.'_section',
            [
                'options' => [
                    'post' => __('Posts', 'smart-sitemap'), 
                    'page' => __('Pages', 'smart-sitemap'), 
                    'product' => __('Products', 'smart-sitemap'), 
                ],
                'id' => 'posttypes'
            ]
        );
 
    }

    public function admin_menu() 
    {
        add_options_page( 'Smart Sitemap Options', 'Smart Sitemap Options', 'delete_posts', 'smart-sitemap-generator', [$this, 'optionRender'] );
    }

    public function selectCallback($args)
    {
        $optionName         = $this->optionPrefix.'__options';
        $optionId           = data_get($args, 'id');
        $optionsForSelect   = get_option( $optionName ); 
        $argsOptions = data_get($args, 'options', $this->defaults[$optionId]);
        ?>
        <select name='<?php echo esc_attr($optionName.'['.$optionId.']'); ?>'>
            <?php 
                foreach ($argsOptions as $key => $value) {
                   echo '<option value="'.esc_attr($key).'" '.selected( $optionsForSelect[data_get($args, 'id')], esc_attr($key) ).'>'.esc_html($value).'</option>'; 
                }
            ?>
        </select> 
        
        <?php
    }

    public function checkboxCallback($args)
    {

        $optionName = $this->optionPrefix.'__options';
        $optionId = data_get($args, 'id');
        $optionsForSelect = get_option( $optionName );
        $argsOptions = data_get($args, 'options', $this->defaults[$optionId]);
 
        foreach ($argsOptions as $key => $value) {
            ?>
            <input type="checkbox" 
                id="<?php echo esc_attr($optionName.'['.$optionId.']'); ?>"
                name="<?php echo esc_attr($optionName.'['.$optionId.'][]'); ?>" 
                value="<?php echo esc_attr($key) ?>" 
                <?php checked( in_array(esc_attr($key),$optionsForSelect[data_get($args, 'id')]), 1 ); ?> />
             <label for="<?php echo esc_attr($optionName.'['.$optionId.']'); ?>"><?php echo esc_attr($value) ?></label>
        <?php
        }
       
    } 

    public function optionRender() 
    { 
        ?>
            <div class="wrap">
                <form action='options.php' method='post'> 
                    <?php
                        settings_fields( $this->optionPrefix.'-setting' );
                        do_settings_sections( $this->optionPrefix.'-setting' );
                        submit_button(); 
                    ?>
                </form>
            </div>
        <?php 
    } 

}
new SmartSitemapAdmin();