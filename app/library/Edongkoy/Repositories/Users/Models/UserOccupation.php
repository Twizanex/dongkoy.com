<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserOccupation.php

class UserOccupation extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'user_occupation';

	public function occupationVisibility()
	{
		return $this->belongsToMany('Edongkoy\Repositories\Users\Models\Visibility', 'user_info_visibility', 'user_occupation_id', 'occupation');
	}
}