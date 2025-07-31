<?php

namespace MPHBR;

class BlocksRender extends \MPHB\BlocksRender
{
    public function renderAccommodationReviews($atts)
    {
        return $this->renderShortcode(MPHBR()->getReviewsShortcode(), $atts);
    }

    protected function renderShortcode($shortcode, $atts)
    {
        if (is_admin()) {
            MPHBR()->setFrontendReviews(new FrontendReviews());
        }
        $atts = $this->filterAtts($atts);
        $this->disableAutop();
        return $shortcode->render($atts, '', $shortcode->getName());
    }

    protected function disableAutop()
    {
        if (has_filter('the_content', 'wpautop') !== false) {
            remove_filter('the_content', 'wpautop');
            add_filter('the_content', function ($content) {
                if (has_blocks()) {
                    return $content;
                }

                return wpautop($content);
            });
        }
    }

    protected function filterAtts($atts)
    {
        $atts = parent::filterAtts($atts);

        if (isset($atts['align'])) {
            $alignClass = 'align' . $atts['align'];

            if (!empty($atts['class'])) {
                $atts['class'] .= ' ' . $alignClass;
            } else {
                $atts['class'] = $alignClass;
            }

            unset($atts['align']);
        }

        return $atts;
    }
}
