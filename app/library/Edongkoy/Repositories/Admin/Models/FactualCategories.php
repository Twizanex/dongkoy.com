<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/FactualCategories.php

class FactualCategories extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'factual_categories';
	protected $softDelete = true;
}