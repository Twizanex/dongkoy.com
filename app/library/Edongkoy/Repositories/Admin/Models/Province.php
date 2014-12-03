<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/Province.php

class Province extends \Eloquent {

	protected $guarded = array('id');

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'province';
	protected $softDelete = true;

	public function cities()
	{
		return $this->hasMany('Edongkoy\Repositories\Admin\Models\Cities', 'province_id');
	}

	public function country()
	{
		return $this->belongsTo('Edongkoy\Repositories\Admin\Models\Countries', 'country_id');
	}
}