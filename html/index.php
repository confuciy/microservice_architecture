<!DOCTYPE html>
<html lang="ru">
<head>
    <title>IN GOD WE TRUST</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    <link rel="shortcut icon" href="/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
    <link rel="manifest" href="site.webmanifest" />
    <link href="/script/gallery/1b-gallery.css" rel="stylesheet">
    <script src="/script/gallery/1c-gallery.js"></script>
    <script src="/js/jquery-1.9.1.min.js"></script>
</head>
<body>
    <?php
    echo '<h2>'.((sizeof($_GET) > 0 or sizeof($_POST) > 0)?'<a href="/" style="text-decoration: none;">Ga-ga-ga!</a>':'Ga-ga-ga!').'</h2>';
    echo '<br><br>';

    if (isset($_GET['upload'])) {

        echo '<div class="gallery-upload-block">';
            echo '<div style="display:inline-block; border: 1px solid #000; border-radius: 5px; padding: 10px;">';
                echo '<div class="gallery-upload-title">Upload You Idea!</div>';
                echo '<br>';
                echo '<div class="gallery-upload-form">';
                    echo '<form enctype="multipart/form-data" method="post" action="/script/gallery/upload.php">';
                        echo 'Select a Photo<br><br>';
                        echo '<input type="file" size="32" name="image_field" value="" placeholder="Select a Photo">';
                        echo '<br><br><hr><br>';
                        echo 'Take a Photo<br><br>';
                        echo '<input type="file" size="32" name="image_field_photo" value="" accept="image/jpeg" capture="camera" placeholder="Take a Photo"> <input type="submit" name="Submit" value="Upload Photo">';
//                        echo '<br><br><br>';
//                        echo '<input type="submit" name="Submit" value="Upload">';
                    echo '</form>';
                echo '</div>';
            echo '</div>';
        echo '</div>';
        echo '<br>';
    }

    function getGallery(string $dir_gallery = '', int $padding_left = 0) : string
    {
        $gallery_html = '';

        #echo $dir_gallery.'<br>';

        $dir_list = scandir('./'.$dir_gallery, SCANDIR_SORT_DESCENDING);

        if (!sizeof($dir_list) or (sizeof($dir_list) - 2) == 0) {

            return '';
        }

        foreach ($dir_list as $dir_name) {

            $dir_name = trim($dir_name);

            # DIR
            if ($dir_name == '.' or $dir_name == '..') {
                continue;
            }
            # GET
            if (!isset($_GET['family']) and preg_match('/^\d{4}$/', $dir_name) == true) {
                continue;
            }
            # Family
            if (isset($_GET['family']) and $dir_name == 'unsorted') {
                continue;
            }

            $dir_image = $dir_gallery . $dir_name . '/';
            $image_list = glob($dir_image . '*.{jpg,jpeg,JPG,gif,png,bmp,webp,dng}', GLOB_BRACE);

            if (sizeof($image_list) > 0) {

                $gallery_html .= '<div data-dir="'.$dir_image.'" class="gallery-title" style="padding-left: '.$padding_left.'px;'.($dir_name != 'unsorted'?' cursor: pointer;':'').'"'.($dir_name != 'unsorted'?' onclick="showGallery(this);"':'').'>'.($padding_left > 0?'|_ ':'').$dir_name.($dir_name != 'unsorted'?' &Darr;':' &swarhk;').'</div>';
                $gallery_html .= '<div class="gallery"'.($dir_name != 'unsorted'?' style="display: none;"':'').' data-dir-id="'.$dir_image.'">';

                    # Family
                    if (isset($_GET['family'])) {

                        foreach ($image_list as $filename) {

                            $result[$filename] = $filename;
                        }

                        ksort($result);

                        #foreach ($result as $image) {
                        #    $gallery_html .= '<img src="/' . $dir_gallery . $dir_name . '/' . rawurlencode(basename($image)) . '">';
                        #}

                    # unsorted
                    } else {

                        foreach ($image_list as $filename) {

                            $time = \filectime($filename);

                            $result[$time][$filename] = $filename;
                        }

                        krsort($result);

                        foreach ($result as $time => $images) {

                            foreach ($images as $image) {

                                $gallery_html .= '<img src="/' . $dir_gallery . $dir_name . '/' . rawurlencode(basename($image)) . '">';
                            }
                        }
                    }

                $gallery_html .= '</div>';

            } else {

                $gallery_html .= '<div class="gallery-title">' . $dir_name . '</div>';
                $gallery_html .= getGallery($dir_gallery.$dir_name.'/', ($padding_left + 30));
            }
        }

        return $gallery_html;
    }

    $dir_gallery = 'gallery/';
    $gallery_html = getGallery($dir_gallery, 0);

    if ($gallery_html != '') {

        echo $gallery_html;
    }
    ?>

    <script>

        function showGallery(elem)
        {
            var elem = elem;

            if ($(elem).next().css('display') == 'none') {

                var dir = $(elem).data('dir');

                $.ajax({
                    url: "/script/gallery/images.php",
                    dataType: "json",
                    type: "POST",
                    data: ({ 'dir': dir }),
                    async: false,
                    success: function(data) {

                        $(document).find('.gallery').html('')
                        $(elem).next().html(data.image_list);
                    },
                    error: function (xhr, ajaxOptions, thrownError) {

                        alert("Ошибка!", xhr.error);
                    }
                });

                $(elem).next().show();

                var replaced = $(elem).text().replace('↡', '↟');
                $(elem).text(replaced);

            } else {

                $(document).find('.gallery').html('')

                $(elem).next().hide();

                var replaced = $(elem).text().replace('↟', '↡');
                $(elem).text(replaced);
            }
        }

        function convertToJpeg(file, callback) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    canvas.toBlob(callback, 'image/jpeg', 0.9); // 0.9 - качество JPEG
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        <?php
        if (isset($_GET['upload'])) {
        ?>

            document.querySelector('input[type="file"]').addEventListener('change', function(e) {

                const file = e.target.files[0];
                if (!file) {

                    return;
                }

                if (file.type === 'image/jpeg') {

                    // Уже JPEG, можно сразу загружать
                    uploadFile(file);

                } else {

                    // Конвертируем в JPEG
                    convertToJpeg(file, function(jpegBlob) {

                        const jpegFile = new File([jpegBlob], file.name.replace(/\.[^/.]+$/, '.jpg'), {
                            type: 'image/jpeg'
                        });

                        uploadFile(jpegFile);
                    });
                }
            });

            function uploadFile(file) {

                const formData = new FormData();
                formData.append('image_field', file);

                fetch('/script/gallery/upload.php', {
                    method: 'POST',
                    body: formData,
                    // Если нужна авторизация:
                    // headers: { 'Authorization': 'Bearer YOUR_TOKEN' }
                }).then(response => {

//                    if (!response.ok) {
//
//                        alert('Ошибка загрузки');
//                        //throw new Error('Ошибка загрузки');
//                    }
                    return;
                })
                .then(data => {

                    location.reload();

                    //console.log('Файл загружен:', data);
                    // Дополнительные действия после загрузки
                })
                .catch(error => {

                    var alert_message = 'Ошибка:' + error;

                    alert(alert_message);
                    //console.error('Ошибка:', error);
                    // Обработка ошибки
                });
            }

        <?php
        }
        ?>
    </script>
</body>
