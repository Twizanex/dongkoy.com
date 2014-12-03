<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserUnreadMessage.php

class UserUnreadMessage extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_unread_message';

	public function message()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\Message');
	}

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User');
	}

	public function userType()
	{
		
	}
}