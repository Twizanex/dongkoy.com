<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/Categories.php

class Categories extends \Eloquent {

	protected $guarded = array('id');
	protected $table   = 'categories';
	protected $softDelete = true;

	public function subCategories()
	{
		return $this->hasMany('Edongkoy\Repositories\Admin\Models\Subcategories', 'category_id')->orderBy('sub_category_name', 'asc');
	}

	public function pages()
	{
		return $this->hasMany('Edongkoy\Repositories\Page\Models\Page', 'category_id');
	}
}