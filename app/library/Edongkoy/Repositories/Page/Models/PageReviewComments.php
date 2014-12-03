<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageReviewComments.php

class PageReviewComments extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'page_review_comments';
	protected $softDelete = true;

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function review()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviews', 'review_id')->withTrashed();
	}

	public function userLikes()
	{
		return $this->hasMany('Edongkoy\Repositories\Page\Models\PageReviewCommentLikes', 'comment_id');
	}
}