<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageReviews.php

class PageReviews extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'page_reviews';
	protected $softDelete = true;

	public function reviewer()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'page_id');
	}

	public function userLikes()
	{
		return $this->hasMany('Edongkoy\Repositories\Users\Models\UserReviewsLikes', 'page_reviews_id');
	}

	public function comments()
	{
		return $this->hasMany('Edongkoy\Repositories\Page\Models\PageReviewComments', 'review_id');
	}
}