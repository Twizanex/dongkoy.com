<?php namespace Edongkoy\Repositories\Admin\Models;

# app/library/Edongkoy/Repositories/Admin/Models/BestBuyIds.php

class BestBuyIds extends \Eloquent {

	protected $guarded = array('id');
	protected $table = 'best_buy_ids';
	protected $softDelete = true;
}