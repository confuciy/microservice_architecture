<?php

if (isset($_FILES['image_field_photo'])) {

    $_FILES['image_field'] = $_FILES['image_field_photo'];
}

//if (preg_match('/\.dng$/', $_FILES['image_field']['name'])) {
//
//    $_FILES['image_field']['name'] = str_replace('.dng', '.jpeg', $_FILES['image_field']['name']);
//}

//echo '<pre>'; print_r($_FILES); echo '</pre>';
//die;

require __DIR__ . '/../../vendor/autoload.php';

$handle = new \Verot\Upload\Upload($_FILES['image_field']);
if ($handle->uploaded) {
//    $handle->file_new_name_body   = 'image_resized';
//    $handle->image_resize         = true;
//    $handle->image_x              = 100;
//    $handle->image_ratio_y        = true;
    $handle->process('/var/www/html/gallery/unsorted/');

    if ($handle->processed) {
        $handle->clean();

//        exec('chmod -R 755 /var/www/html/gallery/unsorted/'.$_FILES['image_field']['name']);
//        exec('chown -R nauths:nauths /var/www/html/gallery/unsorted/'.$_FILES['image_field']['name']);

        header('Location: /?upload');

    } else {
        echo 'error : ' . $handle->error;
    }
}