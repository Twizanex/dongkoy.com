<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/Message.php

class Message extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'message';

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'user_id');
	}

	public function conversation()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\Conversation');
	}
}