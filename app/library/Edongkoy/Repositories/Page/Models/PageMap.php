<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageMap.php

class PageMap extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'page_map';
	protected $softDelete = true;
}