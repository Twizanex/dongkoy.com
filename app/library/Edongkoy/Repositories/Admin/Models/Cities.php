<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Cities.php

class Cities extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'cities';
	protected $softDelete = true;

	public function province()
	{
		return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Province', 'province_id');
	}
}