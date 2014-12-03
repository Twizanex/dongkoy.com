<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/Countries.php

class Countries extends \Eloquent {

	protected $guarded = array('id');

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'countries';
	protected $softDelete = true;

	public function province()
	{
		return $this->hasMany('Edongkoy\Repositories\Admin\Models\Province', 'country_id');
	}

}