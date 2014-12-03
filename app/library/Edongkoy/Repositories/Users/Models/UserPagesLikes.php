<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserPagesLikes.php

class UserPagesLikes extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_pages_likes';
	protected $softDelete = true;

	public function whoLikes()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'page_id');
	}
}