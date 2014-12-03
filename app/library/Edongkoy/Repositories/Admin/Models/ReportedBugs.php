<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/ReportedBugs.php

class ReportedBugs extends \Eloquent {

	protected $guarded = array('id');
	protected $table   = 'reported_bugs';
	protected $softDelete = true;
	
}