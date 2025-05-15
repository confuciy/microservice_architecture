<?php

header('Content-Type: application/json');

if (isset($_POST['dir'])) {

    $htdocs = '/var/www/html/';

    $image_list_arr = glob($htdocs.$_POST['dir'] . '*.{jpg,jpeg,JPG,gif,png,bmp,webp,dng}', GLOB_BRACE);

    $image_list = '';

    if (sizeof($image_list_arr) > 0) {

        foreach ($image_list_arr as $image) {
            $image_list .= '<img src="/' . $_POST['dir'] . rawurlencode(basename($image)) . '">';
        }
    }

    echo json_encode(['image_list' => $image_list], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} else {

    echo json_encode(['error' => 'Не передана директория'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
}