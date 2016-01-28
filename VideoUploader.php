<?php
require_once './vendor/autoload.php';
/**
 * Youtube Video Auto Uploader
 * The php script is using google oauth2 API v3 and Youtube API v3
 * 28,Jan,2016
 * A simple and modified examples that upload batches of video youtube by using php script.
 * @author Louis Choi (louis@simplylouis.com)
 */
/**
 *	Upload the video files to youtube
 *	No matter success or fail, the result will be logged in logStatus()
 * 	@param String $videoPath 		Local video path (./1.mp4)
 *	@param String $videoTitle 		Video Title
 *	@param String $videoDescription Video Description, allow \n characters and links
 *	@param int 	  $videoCategory	Video Category ID, Please refer to the list - https://gist.github.com/dgp/1b24bf2961521bd75d6c
 *	@param String[] $videoTags		Keyword Tags array
 */
function uploadYoutube($videoPath, $videoTitle, $videoDescription, $videoCategory, $videoTags) {
	$OAUTH2_CLIENT_ID = 'XXX.apps.googleusercontent.com'; //TODO: UPDATE YOUR CLIENT ID
	$OAUTH2_CLIENT_SECRET = 'XXX'; //TODO:UPDATE YOUR CLIENT SECRET
	$RESULT = array('refreshToken' => 'XXXXXXXXXXXXX'); //TODO:UPDATE YOUR PROPOSED ACCOUNT REFRESH ID
	$client = new Google_Client();
	$client->setClientId($OAUTH2_CLIENT_ID);
	$client->setClientSecret($OAUTH2_CLIENT_SECRET);
	$client->setScopes('https://www.googleapis.com/auth/youtube');
	$redirect = filter_var('http://localhost/authorize/');
	$client->setRedirectUri($redirect);
	$youtube = new Google_Service_YouTube($client);
	$client->refreshToken($RESULT['refreshToken']);
	$RESULT['accessToken'] = $client->getAccessToken()['access_token'];
	$client->authenticate($RESULT['accessToken']);
	if ($client->getAccessToken()) {
		try {
			logStatus('Video Start Upload - ' . $videoTitle);
			$snippet = new Google_Service_YouTube_VideoSnippet();
			$snippet->setTitle($videoTitle);
			$snippet->setDescription($videoDescription);
			$snippet->setTags($$videoTags);
			$snippet->setCategoryId($videoCategory);
			$status = new Google_Service_YouTube_VideoStatus();
			$status->privacyStatus = "private"; //TODO: UPDATE YOUR UPLOAD VIDEO STATUS , (private/public)
			$video = new Google_Service_YouTube_Video();
			$video->setSnippet($snippet);
			$video->setStatus($status);
			$chunkSizeBytes = 1 * 1024 * 1024;
			$client->setDefer(true);
			$insertRequest = $youtube->videos->insert("status,snippet", $video);
			$media = new Google_Http_MediaFileUpload(
				$client,
				$insertRequest,
				'video/*',
				null,
				true,
				$chunkSizeBytes
			);
			$media->setFileSize(filesize($videoPath));
			$status = false;
			$handle = fopen($videoPath, "rb");
			while (!$status && !feof($handle)) {
				$chunk = fread($handle, $chunkSizeBytes);
				$status = $media->nextChunk($chunk);
			}
			fclose($handle);
			$client->setDefer(false);
			logStatus('Video Uploaded -' . $status['snippet']['title'] . ' ' . $status['id']);
			saveYoutubePath($status['id']);
		} catch (Google_Service_Exception $e) {
			logStatus($e->getMessage());
			exit();
		} catch (Google_Exception $e) {
			logStatus($e->getMessage());
			exit();
		}
	} else {
		$state = mt_rand();
		$client->setState($state);
		$authUrl = $client->createAuthUrl();
		logStatus($authUrl);
		exit();
	}
}
/**
 *	Save the log the status during the program into logs.txt
 *	@param String 	$txt Log string of the input data
 */
function logStatus($txt) {
	echo $txt . "\n";
	file_put_contents('logs.txt', $txt . PHP_EOL, FILE_APPEND);
}
/**
 *	Save the Youtube Key Identifier  https://www.youtube.com/watch?v="gkTb9GP9lVI"
 *	@param String 	$id Youtube Key Identifier
 */
function saveYoutubePath($id) {
	file_put_contents('youtubepath.txt', $id . PHP_EOL, FILE_APPEND);
}
/**
 *	Run the upload script
 */
function run() {
	logStatus('Start command');
	//TODO: YOU MAY MODIFY YOUR VIDEO INFORMATION HERE
	$videoPath = "1.mp4";
	$videoTitle = 'VIDEOTITLE';
	$videoDescription = 'VIDEODESCRIPTION';
	$videoCategory = 27;
	$videoTags = array('KEYWORD0', 'KEYWORD1');
	uploadYoutube($videoPath, $videoTitle, $videoDescription, $videoCategory, $videoTags);

	logStatus('Successfully uploaded.' . $videoPath);
	logStatus('Finished command');
}
run();
?>