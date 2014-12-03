<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/UserConversation.php

class UserConversation extends \Eloquent {

	protected $guarded = array('id');
	protected $softDelete = true;
	protected $table = 'user_conversation';

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'user_id');
	}

	public function messages()
	{
		return $this->hasMany('Edongkoy\Repositories\Users\Models\Message', 'conversation_id');
	}

	public function subject()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\Conversation', 'conversation_id');
	}
}