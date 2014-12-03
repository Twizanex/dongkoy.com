<?php namespace Edongkoy\Repositories\Users;

# app/library/Edongkoy/Repositories/Users/ProfileRepositoryInterface.php

interface ProfileRepositoryInterface {

	public function findById($model, $id);
	public function findByUsername($username);
	public function usernameInfo($username);	
	public function pages($username);	
	public function getAlbums($userid, $user_type, $username);
	public function getAlbumPhotos($album_id, $userid, $user_type, $username, $action);		
	public function confirmEmail($username, $token);
	public function userStatuses();
	public function updateUserBasicInfo();
	public function userBasicInfo($user_id);
	public function updateUserAbout();
	public function userAbout($user_id);
	public function updateUserQuotes();
	public function userQuotes($user_id);
	public function updateUserOccupation();
	public function userOccupation($user_id);
	public function updateUserContactInfo();
	public function userContactInfo($user_id);
	public function updateUserSocialNetworks();
	public function userSocialNetworks($user_id);
	public function userLinks($user_id);
	public function friendshipStatus($user_id);
	public function friends($user_id);
	public function pageLikeButton($page_id);
	public function peopleWhoLikes($page_id);
	public function pageMap($page_id);
	public function updatePageMap();
	public function updatePageSchedule();
	public function messages($user_id, $type);
	public function message($user_id, $type, $conversation_id);
	public function reply($username, $user_id, $type, $conversation_id);
	public function reportProfile($profile_id, $type);
	public function activities($user_id);
	public function deleteConversation($username);
	public function profileImagePreview($type, $id);
	public function pageReviews($page_id);
	public function pageReview($review_id);
	public function likeUnlikeReview($username, $review_id);
	public function commentReview($username, $review_id);
	public function likeUnlikeReviewComment();
}