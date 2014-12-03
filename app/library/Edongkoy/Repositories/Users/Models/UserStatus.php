<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositiries/Users/Models/UserStatus.php

class UserStatus extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_status';

	public function userStatus()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}
}