<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserReviewsLikes.php

class UserReviewsLikes extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'user_reviews_likes';

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function pageReviews()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviews', 'page_reviews_id')->withTrashed();
	}
}