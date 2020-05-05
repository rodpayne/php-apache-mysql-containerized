<?php
/* ----- see https://www.w3schools.com/php/php_file_upload.asp ----- */

$upload_error = '';
	
if (isset($_POST['submitUploadButton'])) {
	if (! $_FILES['fileToUpload']['name']) {
		$upload_error .= "Select the file to be uploaded. ";
		$uploadOk = 0;
	} else {
		$target_dir = "images/";
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		// Check if image file is a actual image or fake image
		$check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
		if($check !== false) {
			$upload_error .= "File ".$target_file." is an image - " . $check["mime"] . ".<br>";
			$uploadOk = 1;
		} else {
			$upload_error .= "File ".$target_file." is not an image. ";
			$uploadOk = 0;
		}
		
		// Check if file already exists
		if (file_exists($target_file)) {
			$upload_error .= "File already exists. ";
			$uploadOk = 0;
		}
		// Check file size
		if ($_FILES["fileToUpload"]["size"] > 100000) {
			$upload_error .= "The file is too large. ";
			$uploadOk = 0;
		}
		// Allow certain file formats
		if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
		&& $imageFileType != "gif" ) {
			$upload_error .= "Only JPG, JPEG, PNG & GIF files are allowed.";
			$uploadOk = 0;
		}
		// Check if $uploadOk is set to 0 by an error
		if ($uploadOk == 1) {
		// if everything is ok, try to upload file
			if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
				$upload_error .= "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded. ";
			} else {
				$upload_error .= "Sorry, there was an error uploading your file. ";
			}
		}
	}
	$upload_error .= '<hr>';
}
?>
<!DOCTYPE html>
<html>
<body>

<p style='color: red;'><?php echo $upload_error; ?></p>
<form action="upload.php" method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submitUploadButton">
</form>

</body>
</html>
