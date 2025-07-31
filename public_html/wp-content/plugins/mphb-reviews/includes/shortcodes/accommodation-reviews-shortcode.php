<?php

namespace MPHBR\Shortcodes;

use MPHBR\Views\ReviewView;
use MPHB\Shortcodes\AbstractShortcode;
use MPHB\Utils\ValidateUtils;

class AccommodationReviewsShortcode extends AbstractShortcode
{
    protected $name = 'mphb_accommodation_reviews';

    public function addActions()
    {
        parent::addActions();
        add_filter('mphb_display_custom_shortcodes', [$this, 'displayData']);
    }

    public function render($atts, $content = null, $shortcodeName = 'mphb_accommodation_reviews')
    {
        $defaults = [
            'id'           => 0,
            'count'        => '',   // "" or number
            'columns'      => 1,
            'show_details' => true, // true,yes,1,on|false,no,0,off
            'show_form'    => true, // true,yes,1,on|false,no,0,off
            'show_more'    => true, // true,yes,1,on|false,no,0,off
            'class'        => ''
        ];

        $atts = shortcode_atts($defaults, $atts, $shortcodeName);

        // Validate atts
        $roomTypeId     = (int)ValidateUtils::validateInt($atts['id']);
        $count          = (int)ValidateUtils::validateInt($atts['count']);
        $columns        = (int)ValidateUtils::validateInt($atts['columns']);
        $showDetails    = ValidateUtils::validateBool($atts['show_details']);
        $showForm       = ValidateUtils::validateBool($atts['show_form']);
        $showMoreButton = ValidateUtils::validateBool($atts['show_more']);
        $class          = mphb_clean($atts['class']);

        $hasId       = $roomTypeId > 0;
        $isAutoCount = $atts['count'] === '';

        // Query review comments
        $queryArgs = ['post_id' => $roomTypeId];

        if (!$isAutoCount) {
            $queryArgs['count'] = $count;
        }

        $queryArgs = MPHBR()->getReviewRepository()->getCommentsQueryArgs($queryArgs);

        if (!$hasId) {
            // Get rid of 0 or current post ID
            unset($queryArgs['post_id']);
        }

        $query    = new \WP_Comment_Query();
        $comments = $query->query($queryArgs);
        $hasMore  = count($comments) < $query->found_comments;

        // Build shortcode content
        $wrapperClass = apply_filters('mphb_sc_accommodation_reviews_wrapper_class', 'mphb_sc_accommodation_reviews-wrapper comments-area mphb-reviews');
        $wrapperClass = trim($wrapperClass . ' ' . $class);

        ob_start();

        echo '<div class="' . esc_attr($wrapperClass) . '" data-accommodation-id="' . esc_attr($hasId ? $roomTypeId : 'all') . '">';
            if ($hasId) {
                echo '<div class="mphbr-accommodation-rating">';
                    ReviewView::displayAverageRating($roomTypeId, $showForm);

                    if ($showDetails) {
                        ReviewView::displayRatings($roomTypeId);
                    }
                echo '</div>';
            }

            if ($showForm && $hasId) {
                ReviewView::displayForm($roomTypeId);
            }

            if ($count > 0 || ($isAutoCount && count($comments) > 0)) {
                ReviewView::displayCommentsList($comments, $columns);
            }

            if ($showMoreButton && $hasMore) {
                ReviewView::displayMoreButton();
            }
        echo '</div>';

        return ob_get_clean();
    }

    public function displayData($shortcodes)
    {
        $shortcodes[$this->getName()] = [
            'label' => esc_html__('Accommodation Reviews', 'mphb-reviews'),
            'description' => '',
            'parameters' => [
                'id' => [
                    'label' => esc_html__('Accommodation Type ID', 'mphb-reviews'),
                    'description' => esc_html__('Display reviews of this Accommodation Type.', 'mphb-reviews'),
                    'values' => esc_html__('integer number', 'mphb-reviews')
                ],
                'count' => [
                    'label' => esc_html__('Number of reviews to show', 'mphb-reviews'),
                    'description' => esc_html__('Leave empty to use the value from Discussion Settings.', 'mphb-reviews'),
                    'values' => esc_html__('integer number', 'motopress-hotel-booking')
                ],
                'columns' => [
                    'label' => esc_html__('Number of columns', 'mphb-reviews'),
                    'values' => sprintf('%d...%d', 1, 6),
                    'default' => 1
				],
                'show_details' => [
                    'label' => esc_html__('Show Rating Types', 'mphb-reviews'),
                    'values' => 'true | false (yes,1,on | no,0,off)',
                    'default' => 'true'
                ],
                'show_form' => [
                    'label' => esc_html__('Show Write Review Button', 'mphb-reviews'),
                    'values' => 'true | false (yes,1,on | no,0,off)',
                    'default' => 'true'
                ],
                'show_more' => [
                    'label' => esc_html__('Show Load More Button', 'mphb-reviews'),
                    'values' => 'true | false (yes,1,on | no,0,off)',
                    'default' => 'true'
                ],
                'class' => [
                    'label' => esc_html__( 'Custom CSS class for shortcode wrapper', 'motopress-hotel-booking' ),
                    'values' => esc_html__( 'whitespace separated css classes', 'motopress-hotel-booking' ),
                    'default' => ''
                ]
            ],
            'example' => [
                'shortcode' => $this->generateShortcode( ['id' => '777'] ),
				'description'	 => esc_html__( 'Show Reviews for Accommodation Type with id 777', 'mphb-reviews' )
            ]
        ];

        return $shortcodes;
    }
}
