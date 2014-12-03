<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UsersNotifications.php

class UserNotifications extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_notifications';
	protected $softDelete = true;

	public function friendRequestAccepted()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'fk_id');
	}

	public function message()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\UserConversation', 'fk_id');
	}

	public function pageStatus()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'fk_id');
	}

	public function pageLike()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\UserPagesLikes', 'fk_id');
	}	

	public function userReviews()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviews', 'fk_id')->withTrashed();
	}

	public function userReviewsLikes()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\UserReviewsLikes', 'fk_id')->withTrashed();
	}

	public function reviewComment()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviewComments', 'fk_id')->withTrashed();
	}

	public function reviewCommentLikes()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\PageReviewCommentLikes', 'fk_id')->withTrashed();
	}
}