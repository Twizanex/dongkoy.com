<?php namespace Edongkoy\Repositories\GlobalRepo;

# app/library/Edongkoy/Repositories/Global/GlobalRepositoryInterface.php

interface GlobalRepositoryInterface {

	public function friendRequest();
	public function userNotifications();
	public function newMessage();
	public function getPageCategories($city_id);
	public function sitemap();
	public function sitemapPages();
}