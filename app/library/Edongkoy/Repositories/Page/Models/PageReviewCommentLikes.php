<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageReviewCommentLikes.php

class PageReviewCommentLikes extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'page_review_comment_likes';
	protected $softDelete = true;

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function comment()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviewComments', 'comment_id');
	}
}