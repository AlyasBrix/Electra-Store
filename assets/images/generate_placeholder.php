<?php
header('Content-Type: image/png');

$width = 400;
$height = 400;

$image = imagecreatetruecolor($width, $height);

$bgColor = imagecolorallocate($image, 44, 62, 80); // #2c3e50
$textColor = imagecolorallocate($image, 255, 255, 255);

imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

$text = "No Image";
$fontSize = 5;
$textWidth = imagefontwidth($fontSize) * strlen($text);
$textHeight = imagefontheight($fontSize);

$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

imagestring($image, $fontSize, $x, $y, $text, $textColor);

imagepng($image);
imagedestroy($image);
?>