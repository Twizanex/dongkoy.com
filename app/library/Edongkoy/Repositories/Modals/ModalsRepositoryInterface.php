<?php namespace Edongkoy\Repositories\Modals;

# app/library/Edongkoy/Repositories/Modals/ModalsRepositoryInterface.php

interface ModalsRepositoryInterface {

	public function type($user_id);
	public function getInfo($type, $id);
	public function memberFullName($user_id);
	public function pageName($user_id);
	public function sendMessage();
	public function deactivateAccount();
	public function activateAccount();
	public function deactivatePage();
	public function activatePage();
	public function reportProblem();
	public function contactUs();
	public function editPageReview();
	public function getPageReview($review_id);
	public function getPageReviewComment($comment_id);
	public function editPageReviewComment();
}