<?php

namespace MPHBR\Settings;

class MainSettings {

	const MIN_RATING = 1;
	const MAX_RATING = 5;

	function getMinRating(){
		return self::MIN_RATING;
	}

	function getMaxRating(){
		return self::MAX_RATING;
	}

    public function getReviewLimit()
    {
        return absint(get_option('mphbr_text_limit', 180));
    }

}
