<?php namespace Edongkoy\Repositories\Page;

# app/library/Edongkoy/Repositories/Page/PageRepositoryInterface.php

interface PageRepositoryInterface {
	public function create();
	public function update($id);
	public function getAllCategories();
	public function getHomePages();
	public function getAllPages();
	public function getAllPagesByCatId($cat_id);
	public function getAllPagesBySubCatId($sub_cat_id);
	public function getAllPagesBySubCatIdAndCityId($sub_cat_id, $city_id);
	public function getAllPagesByCatIdAndCityId($cat_id, $city_id);
	public function hasProvince($country_id);
}