<?php
session_start();
header('Content-Type: image/png');
$image = imagecreatetruecolor(150, 100) or die('Невозможно инициализировать GD поток');;

$color = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $color);

$fontFile = '../resources/fonts/font.ttf';
if (!file_exists($fontFile)) {
    echo 'Файл шрифта не найден!';
    exit;
}
$captcha = '';
$captchaParams = [];
for ($i = 1; $i <= rand(5, 10); $i++) {
    $color = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
    $type = rand(1, 2);
    if ($type === 1) {
        imagefilledellipse($image, rand(0, 150), rand(0, 100), rand(0, 150), rand(0, 100), $color);
    } else {
        imageellipse($image, rand(0, 150), rand(0, 100), rand(0, 150), rand(0, 100), $color);
    }
}

for ($i = 1; $i <= 5; $i++) {
    $captchaParams[$i]['entry'] = rand(0, 9);
    $captcha = $captcha . $captchaParams[$i]['entry'];
    $captchaParams[$i]['size'] = rand(20, 40);
    $captchaParams[$i]['angle'] = rand(0, 60) - 30;
    $captchaParams[$i]['xCoord'] = $i * rand(22, 26);
    $captchaParams[$i]['yCoord'] = rand(30, 70);
    $captchaParams[$i]['textColor'] = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));

    imagettftext($image, $captchaParams[$i]['size'], $captchaParams[$i]['angle'], $captchaParams[$i]['xCoord'],
        $captchaParams[$i]['yCoord'], $captchaParams[$i]['textColor'], $fontFile, $captchaParams[$i]['entry']);
}

$_SESSION['captcha'] = $captcha;

imagepng($image);
imagedestroy($image);