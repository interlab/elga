<?php

if (!defined('ELK')) {
    die('No access...');
}

function getFile($id)
{
    if (!is_numeric($id)) {
        fatal_error('Bad id value. Required int type.', false);
    }

    $db = database();
    $req = $db->query('', '
    SELECT
        f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.id_album, f.title, f.description, f.views,
        f.id_member, f.member_name, f.time_added,
        a.name AS album_name
    FROM {db_prefix}elga_files as f
        INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
    WHERE f.id = {int:id}
    LIMIT 1', [
        'id' => _uint($id),
    ]);
    if (!$db->num_rows($req)) {
        $db->free_result($req);

        return false;
    }

    $file = $db->fetch_assoc($req);
    $db->free_result($req);

    return $file;
}

/*
 * @return int
 *
 */
function getNextId($id, $idalbum)
{
    if (!is_numeric($id)) {
        fatal_error('Bad id value. Required int type.', false);
    }

    $db = database();
    $req = $db->query('', '
    SELECT MAX(f.id)
    FROM {db_prefix}elga_files as f
        INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
    WHERE f.id < {int:id}
        AND f.id_album = {int:album}
    LIMIT 1', [
        'id' => _uint($id),
        'album' => _uint($idalbum),
    ]);

    if (!$db->num_rows($req)) {
        $db->free_result($req);

        return 0;
    }

    $id = (int) $db->fetch_row($req)[0];
    $db->free_result($req);

    return $id;
}

/*
 * @return int
 *
 */
function getPrevId($id, $idalbum)
{
    if (!is_numeric($id)) {
        fatal_error('Bad id value. Required int type.', false);
    }

    $db = database();
    $req = $db->query('', '
    SELECT f.id
    FROM {db_prefix}elga_files as f
        INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
    WHERE f.id > {int:id}
        AND f.id_album = {int:album}
    LIMIT 1', [
        'id' => _uint($id),
        'album' => _uint($idalbum),
    ]);

    if (!$db->num_rows($req)) {
        $db->free_result($req);

        return 0;
    }

    $id = (int) $db->fetch_row($req)[0];
    $db->free_result($req);

    return $id;
}

function getAlbums()
{
    global $boardurl, $modSettings;

    $db = database();

    $req = $db->query('', '
    SELECT a.id, a.name, a.description, a.icon, COUNT(f.id) as total
    FROM {db_prefix}elga_albums AS a
        LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
    GROUP BY a.id
    ORDER BY a.id ASC
    LIMIT 100', []);

    // $data = new Foo();
    $data = [];
    if ($db->num_rows($req) > 0) {
        while ($row = $db->fetch_assoc($req)) {
            $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];
            $data[$row['id']] = $row;
        }
    }
    $db->free_result($req);

    return $data;
}

function getAlbum($id)
{
    global $modSettings;

    if (!is_numeric($id)) {
        fatal_error('Bad id value. Required int type.', false);
    }

    $db = database();
    $req = $db->query('', '
    SELECT id, name, description, icon
    FROM {db_prefix}elga_albums
    WHERE id = {int:id}
    LIMIT 1', [
        'id' => _uint($id),
    ]);

    if (!$db->num_rows($req)) {
        $db->free_result($req);

        return false;
    }

    $row = $db->fetch_assoc($req);
    $db->free_result($req);
    $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];

    return $row;
}

function findFileUploadErrors($key, $path, $max_size)
{
    global $context;

    # http://www.php.net/manual/ru/features.file-upload.errors.php
    if (UPLOAD_ERR_OK !== $_FILES[$key]['error']) {
        switch ($_FILES[$key]['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $context['errors'][] = 'Слишком большой размер файла';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $context['errors'][] = 'Слишком большой размер файла';
                break;
            case UPLOAD_ERR_PARTIAL:
                $context['errors'][] = 'Файл был получен только частично';
                break;
            case UPLOAD_ERR_NO_FILE:
                $context['errors'][] = 'Файл не был загружен';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $context['errors'][] = 'Отсутствует временная папка';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $context['errors'][] = 'Не удалось записать файл на диск';
                break;
            case UPLOAD_ERR_EXTENSION:
                $context['errors'][] = 'PHP-расширение остановило загрузку файла';
                break;
            default:
                $context['errors'][] = 'Unknown Error';
                break;
        }
    }

    if (!empty($context['errors'])) {
        return false;
    }

    if (!empty($_FILES[$key]) && $_FILES[$key]['error'] === 0) {
        $ext = pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION);

        if (!is_dir($path)) {
            fatal_error('Директория постеров указана неверно!', false);
        }

        if (!is_uploaded_file($_FILES[$key]['tmp_name'])) {
            fatal_error('Ошибка загрузки файла на сервер. Попробуйте заново закачать файл.', false);
        }

        $fsize = filesize($_FILES[$key]['tmp_name']);
        if ($fsize > $max_size) {
            fatal_error('Файл превышает максимально допустимый размер!', false);
        }

        if (preg_match('~\\/:\*\?"<>|\\0~', $_FILES[$key]['name'])) {
            fatal_error(Util::htmlspecialchars($_FILES[$key]['name']).'Недопустимые символы в имени постер-файла!', false);
        }

        if (!preg_match('~png|gif|jpg|jpeg~i', $ext)) {
            fatal_error('Расширение постера должно быть <strong>png, gif, jpg, jpeg</strong>.', false);
        }

        return true;
    }

    return false;    
}

function uploadImage()
{
    global $context, $modSettings;

    /*
    # http://www.php.net/manual/ru/features.file-upload.errors.php
    if (UPLOAD_ERR_OK !== $_FILES['image']['error']) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $context['errors'][] = 'Слишком большой размер файла';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $context['errors'][] = 'Слишком большой размер файла';
                break;
            case UPLOAD_ERR_PARTIAL:
                $context['errors'][] = 'Файл был получен только частично';
                break;
            case UPLOAD_ERR_NO_FILE:
                $context['errors'][] = 'Файл не был загружен';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $context['errors'][] = 'Отсутствует временная папка';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $context['errors'][] = 'Не удалось записать файл на диск';
                break;
            case UPLOAD_ERR_EXTENSION:
                $context['errors'][] = 'PHP-расширение остановило загрузку файла';
                break;
            default:
                $context['errors'][] = 'Unknown Error';
                break;
        }
    }

    if (!empty($context['errors'])) {
        return false;
    }

    if (!empty($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $fname = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $directory = BOARDDIR.'/files/gallery';
        $max_size = 1024 * 1024 * 3;

        if (!is_dir($directory)) {
            fatal_error('Директория постеров указана неверно!', false);
        }

        if (!is_uploaded_file($_FILES['image']['tmp_name'])) {
            fatal_error('Ошибка загрузки файла на сервер. Попробуйте заново закачать файл.', false);
        }

        $fsize = filesize($_FILES['image']['tmp_name']);
        if ($fsize > $max_size) {
            fatal_error('Файл превышает максимально допустимый размер!', false);
        }

        if (preg_match('~\\/:\*\?"<>|\\0~', $_FILES['image']['name'])) {
            fatal_error(Util::htmlspecialchars($_FILES['image']['name']).'Недопустимые символы в имени постер-файла!', false);
        }

        if (!preg_match('~png|gif|jpg|jpeg~i', $ext)) {
            fatal_error('Расширение постера должно быть <strong>png, gif, jpg, jpeg</strong>.', false);
        }
        */

        $fname = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $directory = $modSettings['elga_files_path']; //BOARDDIR.'/files/gallery';
        $max_size = 1024 * 1024 * 3;
        $fsize = filesize($_FILES['image']['tmp_name']);

        if ( ! findFileUploadErrors('image', $directory, $max_size) ) {
            return false;
        }

        $nfname = sha1_file($_FILES['image']['tmp_name']).'.'.$ext;
        $date = date('Y/m/d', time());
        $dest_dir = $directory.'/'.$date;
        if (!is_dir($dest_dir)) {
            if (!mkdir($dest_dir, 0777, true)) {
                fatal_error('Не получается создать директорию '.$dest_dir);
            }
        }
        $dest_name = $dest_dir.'/'.$nfname;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest_name)) {
            fatal_error('Ошибка копирования временного файла!', false);
        } else {

            // create thumb image
            $thumb_name = pathinfo($dest_name, PATHINFO_FILENAME).'_thumb.'.pathinfo($dest_name, PATHINFO_EXTENSION);
            $width = 350;
            $height = 350;
            thumb($dest_name, $dest_dir.'/'.$thumb_name, $width, $height);

            return [
    'name' => $date.'/'.$nfname,
    'orig_name' => $_FILES['image']['name'], // ? need sanitize?
    'size' => $fsize,
    'thumb' => $date.'/'.$thumb_name,
            ];
        }
    /*
    }
    */
}

function delOldImage($img)
{
    global $modSettings;

    $path = $modSettings['elga_files_path']; //BOARDDIR.'/files/gallery';
    $orig = $path.'/'.$img['fname'];
    $thumb = $path.'/'.$img['thumb'];
    foreach ([$orig, $thumb] as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

function delOldIcon($a)
{
    global $modSettings;

    $path = $modSettings['elga_icons_path']; //BOARDDIR.'/files/gallery/icons';
    $file = $path.'/'.$a['icon'];
    if (file_exists($file)) {
        @unlink($file);
    }
}

function uploadIcon()
{
    global $modSettings;

    $path = $modSettings['elga_icons_path']; //BOARDDIR.'/files/gallery/icons';
    $name = $_FILES['icon']['name'];

    if ( ! findFileUploadErrors('icon', $path, 1024 * 1024 * 3) )
        return false;

    thumb(
        $_FILES['icon']['tmp_name'],
        $path . '/' . $name,
        $modSettings['elga_icon_max_width'] ? $modSettings['elga_icon_max_width'] : 60,
        $modSettings['elga_icon_max_height'] ? $modSettings['elga_icon_max_height'] : 60
    );

    return $name;
}

# Создаем мини-постер
# http://imagine.readthedocs.org/en/latest/index.html
# https://speakerdeck.com/avalanche123/introduction-to-imagine
/*
thumb(
    $dir . '/' . $fname,
    $dir . '/' . $thumb_fname,
    $maxWidth,
    $maxHeight
    );
*/
function thumb($img, $thumb,  $width = 300, $height = 300)
{
    # Check if GD extension is loaded
    if (!extension_loaded('gd') && !extension_loaded('gd2')) {
        trigger_error("GD is not loaded", E_USER_WARNING);

        return false;
    }

    $loader = require_once EXTDIR.'/elga_lib/vendor/autoload.php';

    $imagine = new \Imagine\Gd\Imagine();
    $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET; # THUMBNAIL_OUTBOUND
    $image = $imagine->open($img);
    $size = $image->getSize();

    # Если размеры меньше, то масштабирования не нужно
    if ($size->getWidth() <= $width && $size->getHeight() <= $height) {
        $image->copy()
              ->save($thumb);
    } else {
        $image->thumbnail(new \Imagine\Image\Box($width, $height), $mode)
              ->save($thumb);
    }

    return true;
}

function _createChecks($key)
{
    global $context;

    if ($context['require_verification']) {
        // Could they get the right send topic verification code?
        require_once SUBSDIR.'/VerificationControls.class.php';
        $verificationOptions = [
            'id' => $key,
        ];
        $context['require_verification'] = create_control_verification($verificationOptions);
        $context['visual_verification_id'] = $verificationOptions['id'];
    }
    createToken($key);
}

function _uint($val)
{
    return abs(intval($val));
}

class Foo extends ArrayObject
{
    
}
