<?php namespace Edongkoy\Repositories\Image;

# app/library/Edongkoy/Repositories/Image/ImageRepositoryInterface.php

interface ImageRepositoryInterface {

	public function crop($album_id, $image_id, $filename);
	public function owner($user_id, $user_type);
	public function albumOwner($album_id, $user_id);
	public function imageOwner($album_id, $image_id, $user_id);
	public function makeAlbumCover($album_id, $image_id);
	public function changeProfileImage($album_id, $image_id);
	public function changeCover($album_id, $image_id);
	public function uploadProfilePhoto();
	public function uploadCoverPhoto();
	public function addPhotos();
	public function insertImage($album_id, $filename);
	public function getAlbumId($user_id, $user_type, $album_type, $album_name);
	public function insertProfileImage($user_id, $image_id, $user_type);
	public function insertCoverImage($user_id, $image_id, $user_type);
	public function userPhotos($username);
	public function deleteImage($album_id, $image_id);
	public function userInfo($username);
}