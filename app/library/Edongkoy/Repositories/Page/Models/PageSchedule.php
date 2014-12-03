<?php namespace Edongkoy\Repositories\Page\Models;

# app/library/Edongkoy/Repositories/Page/Models/PageSchedule.php

class PageSchedule extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'page_schedule';
	protected $softDelete = true;
}