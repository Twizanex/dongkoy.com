<?php namespace Edongkoy\Repositories\Users\Models;

# app/library/Edongkoy/Repositories/Users/Models/ReportedByNonMember.php

class ReportedByNonMember extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'reported_by_non_member';

	public function user()
	{
		return $this->belongsTo('Edongkoy\Repositories\Users\Models\User', 'id_fk');
	}

	public function page()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Page', 'id_fk');
	}
}