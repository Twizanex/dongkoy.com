<?php namespace Edongkoy\Repositories\Videos\Models;

# app/library/Edongkoy/Repositories/Videos/Models/Videos.php

class Videos extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'videos';
	protected $softDelete = true;

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'user_id');
	}
}