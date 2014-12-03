<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserFriends.php

class UserFriends extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_friends';
	protected $softDelete = true;

	public function friendRequest()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}

	public function friends()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'friend_id');
	}
}