<?php

namespace MPHBR;

class Plugin {

	/**
	 *
	 * @var \MPHBR\Plugin
	 */
	private static $instance = null;

	/**
	 *
	 * @var string
	 */
	private static $filepath;

	/**
	 *
	 * @var Settings\SettingsRegistry
	 */
	private $settings;

	/**
	 *
	 * @var PluginData
	 */
	private $pluginData;

	/**
	 *
	 * @var Dependencies
	 */
	private $dependencies;


	/**
	 * @var RatingTypeTaxonomy
	 */
	private $ratingTypeTaxonomy;

	/**
	 * @var ReviewRepository
	 */
	private $reviewRepository;

	/**
	 * @var RatingManager
	 */
	private $ratingManager;

	/**
	 * @var Importer
	 */
	private $importer;

    /**
     * @var Shortcodes\AccommodationReviewsShortcode
     */
    private $reviewsShortcode;

    /**
     * @var FrontendReviews
     */
    private $frontendReviews;

	private function __construct() {
		// Do nothing.
	}

	/**
	 *
	 * @param string $filepath
	 */
	public static function setBaseFilepath( $filepath ) {
		self::$filepath = $filepath;
	}

	public static function getInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->afterConstruct();
		}

		return self::$instance;
	}

	public function afterConstruct() {
		add_action( 'plugins_loaded', [ $this, 'init' ], 1 );
	}

	public function init() {

		$this->pluginData   = new PluginData( self::$filepath );
		$this->loadTextDomain();
		$this->settings     = new Settings\SettingsRegistry();
		$this->dependencies = new Dependencies();

		if ( $this->dependencies->check() ) {
			new AutoUpdater();

            new Admin\ExtensionSettings();

			$this->ratingTypeTaxonomy = new RatingTypeTaxonomy();
			$this->reviewRepository   = new ReviewRepository();
			$this->ratingManager      = new RatingManager();
			$this->importer           = new Importer();
			$this->reviewsShortcode   = new Shortcodes\AccommodationReviewsShortcode();

            // Init widgets
            Widgets\AccommodationReviewsWidget::init();

            $isAjax = defined( 'DOING_AJAX' ) && DOING_AJAX;

			if ( !is_admin() || mphbr_is_edit_page() || $isAjax ) {
				$this->frontendReviews = new FrontendReviews();
			} else {
				new AdminReviews();
			}

            if ( $isAjax ) {
                new Ajax();
            }

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
            if (function_exists('register_block_type')) {
                add_action( 'init', [ $this, 'enqueueBlockAssets' ], 11 );
            }
		}

		self::upgrade();
	}

	public function enqueueAssets() {
		wp_enqueue_script( 'mphb-reviews', $this->pluginData->getPluginUrl( 'assets/js/mphbr.min.js' ), [ 'jquery', 'mphb-jquery-serialize-json' ], $this->pluginData->getVersion(), true );
        wp_localize_script( 'mphb-reviews', 'MPHBR', [
            'settings' => [ 'ajaxUrl' => admin_url( 'admin-ajax.php' ) ],
            'nonce' => [ 'mphbr_load_more' => wp_create_nonce( 'mphbr_load_more' ) ]
        ] );
		wp_enqueue_style( 'mphb-reviews', $this->pluginData->getPluginUrl( 'assets/css/frontend.css' ), [ 'dashicons' ], $this->pluginData->getVersion() );
	}

	public function enqueueAdminAssets() {
		wp_enqueue_script( 'mphb-reviews-admin', $this->pluginData->getPluginUrl( 'assets/js/mphbr.admin.min.js' ), [ 'jquery' ], $this->pluginData->getVersion(), true );
		wp_enqueue_style( 'mphb-reviews-admin', $this->pluginData->getPluginUrl( 'assets/css/admin.css' ), [ 'dashicons' ], $this->pluginData->getVersion() );
	}

    public function enqueueBlockAssets()
    {
        wp_register_script('mphb-reviews-blocks', $this->pluginData->getPluginUrl('assets/js/blocks.min.js'), ['wp-i18n', 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components'], $this->pluginData->getVersion());

        $blocksRender = new BlocksRender();

        register_block_type(
            'motopress-hotel-booking/accommodation-reviews',
            [
                'editor_script' => 'mphb-reviews-blocks',
                'render_callback' => [$blocksRender, 'renderAccommodationReviews'],
                'attributes' => [
                    'id' => ['type' => 'string', 'default' => ''],
                    'count' => ['type' => 'string', 'default' => ''],
                    'columns' => ['type' => 'number', 'default' => 1],
                    'show_details' => ['type' => 'boolean', 'default' => true],
                    'show_form' => ['type' => 'boolean', 'default' => true],
                    'show_more' => ['type' => 'boolean', 'default' => true],
                    'align' => ['type' => 'string', 'default' => ''],
                    'className' => ['type' => 'string', 'default' => '']
                ]
            ]
        );
    }

	/**
	 * @return RatingTypeTaxonomy
	 */
	public function getRatingTypeTaxonomy() {
		return $this->ratingTypeTaxonomy;
	}

	/**
	 * @return ReviewRepository
	 */
	public function getReviewRepository() {
		return $this->reviewRepository;
	}

	/**
	 * @return RatingManager
	 */
	public function getRatingManager() {
		return $this->ratingManager;
	}

	/**
	 *
	 * @return Settings\SettingsRegistry
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 *
	 * @return Dependencies
	 */
	public function getDependencies() {
		return $this->dependencies;
	}

	/**
	 *
	 * @return PluginData
	 */
	public function getPluginData() {
		return $this->pluginData;
	}

    /**
     *
     * @return Shortcodes\AccommodationReviewsShortcode
     */
    public function getReviewsShortcode() {
        return $this->reviewsShortcode;
    }

    /**
     *
     * @return FrontendReviews
     */
    public function frontendReviews() {
        return $this->frontendReviews;
    }

    public function setFrontendReviews($object)
    {
        if (is_a($object, '\MPHBR\FrontendReviews')) {
            $this->frontendReviews = $object;
        }
    }

	public function loadTextDomain() {

		$slug = $this->pluginData->getSlug();

		$locale = mphbr_is_wp_version( '4.7', '>=' ) ? get_user_locale() : get_locale();

		$locale = apply_filters( 'plugin_locale', $locale, $slug );

		// wp-content/languages/mphb-reviews/mphb-reviews-{lang}_{country}.mo
		$customerMoFile = sprintf( '%1$s/%2$s/%2$s-%3$s.mo', WP_LANG_DIR, $slug, $locale );

		load_textdomain( $slug, $customerMoFile );

		load_plugin_textdomain( $slug, false, $slug . '/languages' );
	}

	public function capabilities()
	{
		return new UsersAndRoles\Capabilities();
	}

	public static function upgrade()
	{
		if (!self::getPluginDbVersion() || version_compare(self::getPluginDbVersion(), MPHBR()->getPluginData()->getVersion(), '<')) {
			UsersAndRoles\Capabilities::setup();
			self::setPluginDbVersion();
		}
	}

	public static function getPluginDbVersion()
	{
		return get_option('mphb_reviews_db_version');
	}

	public static function setPluginDbVersion()
	{
		return update_option('mphb_reviews_db_version', MPHBR()->getPluginData()->getVersion());
	}

	public static function activate()
	{
		UsersAndRoles\Capabilities::setup();
	}
}

register_activation_hook(PLUGIN_FILE, array('MPHBR\Plugin', 'activate'));
