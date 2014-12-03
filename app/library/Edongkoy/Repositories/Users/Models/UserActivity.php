<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserActivity.php

class UserActivity extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'user_activity';

	public function name()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\Activities', 'activities_id');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'fk_id');
	}

	public function friend()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'fk_id');
	}

	public function pageReviews()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviews', 'fk_id')->withTrashed();
	}

	public function pageReviewsLikes()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\UserReviewsLikes', 'fk_id')->withTrashed();
	}

	public function pageReviewsComments()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviewComments', 'fk_id')->withTrashed();
	}

	public function pageReviewsCommentsLikes()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviewCommentLikes', 'fk_id')->withTrashed();
	}
}

