<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/Subcategories.php

class Subcategories extends \Eloquent {

	protected $guarded = array('id');
	protected $table   = 'sub_categories';
	protected $softDelete = true;
	
}