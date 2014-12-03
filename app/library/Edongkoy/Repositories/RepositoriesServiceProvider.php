<?php namespace Edongkoy\Repositories;

# app/library/Edongkoy/Repositories/EdongkoyServiceProvider.php

use Illuminate\Support\ServiceProvider;

class RepositoriesServiceProvider extends ServiceProvider {

	// Triggered automatically by Laravel
	public function register()
	{
		$this->app->bind(
			'Edongkoy\Repositories\Users\ProfileRepositoryInterface',
			'Edongkoy\Repositories\Users\EloquentProfileRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Users\UserRepositoryInterface',
			'Edongkoy\Repositories\Users\EloquentUserRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Page\PageRepositoryInterface',
			'Edongkoy\Repositories\Page\EloquentPageRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Admin\AdminRepositoryInterface',
			'Edongkoy\Repositories\Admin\EloquentAdminRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Emails\EmailsRepositoryInterface',
			'Edongkoy\Repositories\Emails\EloquentEmailsRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Members\MembersRepositoryInterface',
			'Edongkoy\Repositories\Members\EloquentMembersRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\GlobalRepo\GlobalRepositoryInterface',
			'Edongkoy\Repositories\GlobalRepo\EloquentGlobalRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Modals\ModalsRepositoryInterface',
			'Edongkoy\Repositories\Modals\EloquentModalsRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Image\ImageRepositoryInterface',
			'Edongkoy\Repositories\Image\EloquentImageRepository'
		);

		$this->app->bind(
			'Edongkoy\Repositories\Videos\VideosRepositoryInterface',
			'Edongkoy\Repositories\Videos\EloquentVideosRepository'
		);
	}
}