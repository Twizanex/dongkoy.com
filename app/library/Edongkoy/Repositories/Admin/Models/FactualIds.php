<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/FactualIds.php

class FactualIds extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'factual_ids';
	protected $softDelete = true;
}

