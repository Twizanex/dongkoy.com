<?php namespace Edongkoy\Repositories\Users;

# app/library/Edongkoy/Repositories/Users/UserRepositoryInterface.php

interface UserRepositoryInterface {

	public function login();
	public function register();
	public function facebookAuth();
	public function changeName();
	public function changePassword();
	public function addFriend();
	public function unfriend();
	public function updateUnreadFriendRequest();
	public function acceptFriendRequest();
	public function deniedFriendRequest();
	public function jsonFriends();
	public function likePage();
	public function unlikePage();
	public function deleteMessage();
	public function updateUnreadMessages();
	public function emailNotificationsSettings();
	public function changeEmailNotifications();
	public function writePageReview();
	public function deletePageReview();
}