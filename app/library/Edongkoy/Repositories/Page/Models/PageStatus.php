<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageStatus.php

class PageStatus extends \Eloquent {

	protected $guarded = array();
	protected $table = 'page_status';

	public function status()
	{
		return $this->belongsTo('Edongkoy\Repositories\Page\Models\Status', 'status_id');
	}
}