<?php

if (!defined('ELK')) {
    die('No access...');
}

class ElgaSubs
{
    public static function json_response(array $data)
    {
        /*ob_end_clean();
            ob_start('ob_gzhandler');*/
        if (empty($data))
            log_error('$data is empty!');
        die(json_encode($data));
    }

    public static function getFile($id)
    {
        global $txt, $modSettings, $boardurl, $scripturl;

        if (!is_numeric($id)) {
            fatal_error('Bad id value. Required int type.', false);
        }

        $db = database();
        $req = $db->query('', '
        SELECT
            f.id, f.orig_name, f.fname, f.thumb, f.preview, f.fsize,
            f.id_album, f.title, f.description, f.views,
            f.id_member, f.member_name, f.time_added, f.exif,
            a.name AS album_name
        FROM {db_prefix}elga_files as f
            INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
        WHERE f.id = {int:id}
        LIMIT 1', [
            'id' => self::uint($id),
        ]);
        if (!$db->num_rows($req)) {
            $db->free_result($req);

            return false;
        }

        $url = $modSettings['elga_files_url'];
        $row = $db->fetch_assoc($req);
        // $row['thumb-url'] = $url.'/'.$row['thumb'];
        // $row['preview-url'] = $url.'/'.$row['preview'];
        // $row['icon'] = $url.'/'.$row['fname'];
        $row['thumb-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=thumb';
        $row['preview-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=preview';
        $row['icon'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
        $row['hsize'] = round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte'];
        $row['description'] = parse_bbc($row['description']);
        $row['img-bbc'] = '[img]' . $boardurl . '/gallery.php?id=' . $row['id'] . '[/img]';
        // $row['img-bbc'] = '[img]' . $scripturl . '?action=gallery;sa=show;id='.$row['id'] . '[/img]'; // [img]...[/img] not work!
        // $row['img-url'] = $boardurl . '/gallery.php?id=' . $row['id'];
        $row['img-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
        $row['img-download-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'] . ';mode=download';
        $db->free_result($req);

        return $row;
    }

    public static function countFiles($album_id)
    {
        $db = database();
        $req = $db->query('', '
            SELECT COUNT(*)
            FROM {db_prefix}elga_files
            WHERE id_album = {int:id}
            LIMIT 1',
            ['id' => $album_id]
        );
        if (!$db->num_rows($req)) {
            $total = 0;
        } else {
            $total = $db->fetch_row($req)[0];
        }
        $db->free_result($req);

        return $total;
    }

    public static function getLastFiles($limit=20)
    {
        $limit = self::uint($limit);

        return self::getFiles(0, 0, $limit);
    }

    public static function getFilesIterator($album_id, $offset, $limit)
    {
        global $modSettings, $txt, $scripturl;

        $db = database();
        $req = $db->query('', '
            SELECT
                f.id, f.orig_name, f.fname, f.thumb, f.preview, f.fsize, f.title,
                f.description, f.views, f.id_member, f.member_name
            FROM {db_prefix}elga_files as f' . ($album_id ? '
            WHERE f.id_album = {int:album}' : '') . '
            ORDER BY f.id DESC
            LIMIT {int:start}, {int:per_page}',
            [
                'album' => $album_id,
                'start' => $offset,
                'per_page' => $limit,
            ]
        );

        $url = $modSettings['elga_files_url'];

        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                // $row['thumb-url'] = $url.'/'.$row['thumb'];
                // $row['preview-url'] = $url.'/'.$row['preview'];
                // $row['icon'] = $url.'/'.$row['fname'];
                $row['thumb-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=thumb';
                $row['preview-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=preview';
                $row['icon'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
                $row['hsize'] = round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte'];

                yield $row;
            }
        }
        $db->free_result($req);
    }

    public static function parseSortQuery($val)
    {
        $order = '';
        if ( !empty($val) ) {
            // && preg_match('~^(time_added|title|views)-(desc|asc)$~i', $val, $s)) {
            $s = explode('-', $val);
            if ( (count($s) === 2)
                && in_array($s[0], ['time_added', 'title', 'views'])
                && in_array($s[1], ['asc', 'desc'])
            ) {
                $s1 = strtoupper($s[1]);
                if ('time_added' === $s[0]) {
                    $order = 'f.id ' . $s[1] . ', f.' . $s[0] . ' ' . $s[1];
                }
                else {
                    $order = 'f.' . $s[0] . ' ' . $s[1];
                }
            }
        }

        return $order;
    }

    public static function getFiles($album_id, $offset, $limit, array $params = [])
    {
        global $modSettings, $txt, $boardurl, $scripturl;

        $sort = self::parseSortQuery( ( isset($params['sort']) ? $params['sort'] : '' ) );

        $db = database();
        $req = $db->query('', '
            SELECT
                f.id, f.orig_name, f.fname, f.thumb, f.preview, f.fsize, f.title,
                f.description, f.views, f.id_member, f.member_name,
                a.id AS alb_id, a.name AS alb_name
            FROM {db_prefix}elga_files as f
                INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)' . ($album_id ? '
            WHERE f.id_album = {int:album}' : '') . '
            ORDER BY ' . (empty($sort) ? 'f.id DESC' : $sort) . '
            LIMIT {int:start}, {int:per_page}',
            [
                'album' => $album_id,
                'start' => $offset, // $context['start'],
                'per_page' => $limit, // $per_page,
            ]
        );

        $url = $modSettings['elga_files_url'];
        $files = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                // $row['thumb-url'] = $url.'/'.$row['thumb'];
                // $row['preview-url'] = $url.'/'.$row['preview'];
                // $row['icon'] = $url.'/'.$row['fname'];
                $row['thumb-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=thumb';
                $row['preview-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=preview';
                $row['icon'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
                $row['hsize'] = round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte'];
                $files[$row['id']] = $row;
            }
        }
        $db->free_result($req);

        return $files;
    }

// @TODO
// 2 prev
// SELECT f.id FROM elkarte_elga_files as f INNER JOIN elkarte_elga_albums AS a ON (a.id = f.id_album) WHERE f.id < 112 AND f.id_album = 1 order by id desc LIMIT 2
// 2 next
// SELECT f.id FROM elkarte_elga_files as f INNER JOIN elkarte_elga_albums AS a ON (a.id = f.id_album) WHERE f.id > 112 AND f.id_album = 1 order by id asc LIMIT 2 

    /*
     * @return int
     *
     */
    public static function getNextId($id, $idalbum)
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
            'id' => self::uint($id),
            'album' => self::uint($idalbum),
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
    public static function getPrevId($id, $idalbum)
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
            'id' => self::uint($id),
            'album' => self::uint($idalbum),
        ]);

        if (!$db->num_rows($req)) {
            $db->free_result($req);

            return 0;
        }

        $id = (int) $db->fetch_row($req)[0];
        $db->free_result($req);

        return $id;
    }

    public static function getAlbums()
    {
        global $scripturl, $modSettings;

        $db = database();

        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon, a.leftkey, a.rightkey, COUNT(f.id) as total, (COUNT(p.id) - 1) AS depth
        FROM {db_prefix}elga_albums AS a
            JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
            LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 100', []);

        // $data = new Foo();
        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];
                $row['url'] = $scripturl.'?action=gallery;sa=album;id='.$row['id'];
                $data[$row['id']] = $row;
            }
        }
        $db->free_result($req);

        return $data;
    }

    public static function getAlbumsSimple()
    {
        global $boardurl, $modSettings;

        $db = database();

        // @todo: limit
        $req = $db->query('', '
        SELECT a.id, a.name, (COUNT(p.id) - 1) AS depth
        FROM {db_prefix}elga_albums AS a, {db_prefix}elga_albums AS p
        WHERE a.leftkey BETWEEN p.leftkey AND p.rightkey
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 250', []);

        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $data[] = $row;
            }
        }
        $db->free_result($req);

        return $data;
    }

    public static function getAlbum($id, $load_descendants=false)
    {
        global $modSettings, $scripturl;

        if (!is_numeric($id)) {
            fatal_error('Bad id value. Required int type.', false);
        }

        $db = database();
        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon, a.leftkey, a.rightkey, COUNT(f.id) as total, (COUNT(p.id) - 1) AS depth
        FROM {db_prefix}elga_albums AS a
            JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
            LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
        WHERE a.id = {int:id}
        LIMIT 1', [
            'id' => self::uint($id),
        ]);

        if (!$db->num_rows($req)) {
            $db->free_result($req);

            return false;
        }

        $row = $db->fetch_assoc($req);
        $db->free_result($req);
        $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];
        $row['url'] = $scripturl.'?action=gallery;sa=album;id='.$row['id'];
        if ($load_descendants) {
            $row['descendants'] = self::getSubAlbums($row);
        }

        return $row;
    }

    public function getSubAlbums($r)
    {
        global $modSettings;

        $db = database();
        $req = $db->query('', '
        SELECT a.*, (COUNT(p.id) - 1) AS depth, COUNT(f.id) as total
        FROM {db_prefix}elga_albums AS a, {db_prefix}elga_albums AS p, {db_prefix}elga_files AS f
        WHERE a.leftkey BETWEEN p.leftkey AND p.rightkey
            AND a.leftkey > ' . $r['leftkey'] . '
            AND a.rightkey < ' . $r['rightkey'] . '
            AND a.id = f.id_album
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 250', []);

        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];
                $data[] = $row;
            }
        }
        $db->free_result($req);

        return $data;
    }

    public function getParentsAlbums($id, $depth = null, $get_current = false)
    {
        global $modSettings, $scripturl;

        $a = self::getAlbum($id);
        if (empty($a['depth'])) {
            return null;
        }

        $db = database();
        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon, a.leftkey, a.rightkey, COUNT(f.id) as total, (COUNT(p.id) - 1) AS depth
        FROM {db_prefix}elga_albums AS a
            JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
            LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
        WHERE a.leftkey < ' . $a['leftkey'] . '
            AND a.rightkey > ' . $a['rightkey'] . '
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 100', []);

        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $modSettings['elga_icons_url'].'/'.$row['icon'];
                $row['url'] = $scripturl.'?action=gallery;sa=album;id='.$row['id'];
                $data[$row['id']] = $row;
            }
        }
        $db->free_result($req);

        if ($get_current) {
            $data[$id] = $a;
        }

        return $data;
    }

    public static function loadAlbumsLinkTree($id_album, $load_title = false, $load_current = false)
    {
        global $context, $scripturl;

        $albms = self::getParentsAlbums($id_album, null, $load_current);

        if ( ! empty($albms) ) {
            foreach ($albms as $album) {
                if ($load_title) {
                    $context['page_title'] .= ' - ' . $album['name'];
                }

                $context['linktree'][] = [
                    'url' => $scripturl.'?action=gallery;sa=album;id='.$album['id'],
                    'name' => $album['name'],
                ];
            }
        }
    }

    public static function findFileUploadErrors($key, $path, $max_size)
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

            if (!preg_match('~^(png|gif|jpg|jpeg)$~i', $ext)) {
                fatal_error('Расширение постера должно быть <strong>png, gif, jpg, jpeg</strong>.', false);
            }

            return true;
        }

        return false;    
    }

    public static function uploadImage()
    {
        global $context, $modSettings;

        $fname = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $directory = $modSettings['elga_files_path']; //BOARDDIR.'/files/gallery';
        $max_size = 1024 * 1024 * 3;
        $fsize = filesize($_FILES['image']['tmp_name']);

        if ( ! self::findFileUploadErrors('image', $directory, $max_size) ) {
            return false;
        }

        $sha1 = sha1_file($_FILES['image']['tmp_name']);
        $nfname = $sha1 . '.' . $ext;
        $date = date('Y/m/d', time());
        $dest_dir = $directory.'/'.$date;
        if (!is_dir($dest_dir)) {
            if (!mkdir($dest_dir, 0777, true)) {
                fatal_error('Не получается создать директорию '.$dest_dir);
            }
        }
        $dest_name = $dest_dir.'/'.$nfname;

        // уже существует файл с таким же названием,
        // добавим к концу имени цифру
        if ( file_exists($dest_name) ) {
            // http://www.cowburn.info/2010/04/30/glob-patterns/
            $samefiles = glob($dest_dir.'/'.$sha1.'-[!_].*');
            natcasesort($samefiles);
            $last_same = array_pop($samefiles);
            if (preg_match('~-([0-9]+)\.[\w]+$~', $last_same, $ms)) {
                $nfname = $sha1 . '-' . ($ms[1] + 1) . '.' . $ext;
            } else {
                $nfname = $sha1 . '-1.' . $ext;
            }
        }

        $dest_name = $dest_dir.'/'.$nfname;
        if (file_exists($dest_name)) {
            fatal_error('уже существует файл с таким же названием');
        }

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest_name)) {
            fatal_error('Ошибка копирования временного файла!', false);
        } else {
            // create thumb image
            $thumb_name = pathinfo($dest_name, PATHINFO_FILENAME).'_thumb.'.pathinfo($dest_name, PATHINFO_EXTENSION);
            $width = empty($modSettings['elga_imgthumb_max_width']) ? 200 : $modSettings['elga_imgthumb_max_width'];
            $height = empty($modSettings['elga_imgthumb_max_height']) ? 200 : $modSettings['elga_imgthumb_max_height'];
            self::thumb($dest_name, $dest_dir.'/'.$thumb_name, $width, $height);

            // create preview image
            $preview_name = pathinfo($dest_name, PATHINFO_FILENAME).'_preview.'.pathinfo($dest_name, PATHINFO_EXTENSION);
            $width = empty($modSettings['elga_imgpreview_max_width']) ? 450 : $modSettings['elga_imgpreview_max_width'];
            $height = empty($modSettings['elga_imgpreview_max_height']) ? 450 : $modSettings['elga_imgpreview_max_height'];
            self::thumb($dest_name, $dest_dir.'/'.$preview_name, $width, $height);

            return [
                'name' => $date.'/'.$nfname,
                'orig_name' => $_FILES['image']['name'], // ? need sanitize?
                'size' => $fsize,
                'thumb' => $date.'/'.$thumb_name,
                'preview' => $date . '/' . $preview_name, 
            ];
        }
        /*
        }
        */
    }

    public static function removeFile($id)
    {
        $id = self::uint($_GET['id']);

        $file = self::getFile($id);
        if (!$file) {
            fatal_error('File not found.', false);
        }

        $db = database();
        $req = $db->query('', '
            DELETE FROM {db_prefix}elga_files
            WHERE id = {int:id}',
            [
                'id' => $id,
            ]
        );

        self::delOldImage($file);
    }

    public static function delOldImage($img)
    {
        global $modSettings;

        $path = $modSettings['elga_files_path']; //BOARDDIR.'/files/gallery';
        $imgs = [ $path.'/'.$img['fname'], $path.'/'.$img['thumb'], $path.'/'.$img['preview'] ];
        foreach ( $imgs as $file ) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    public static function delOldIcon($a)
    {
        global $modSettings;

        $path = $modSettings['elga_icons_path']; //BOARDDIR.'/files/gallery/icons';
        $file = $path.'/'.$a['icon'];
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public static function uploadIcon()
    {
        global $modSettings;

        $path = $modSettings['elga_icons_path']; //BOARDDIR.'/files/gallery/icons';
        $name = $_FILES['icon']['name'];

        if ( ! self::findFileUploadErrors('icon', $path, 1024 * 1024 * 3) )
            return false;

        self::thumb(
            $_FILES['icon']['tmp_name'],
            $path . '/' . $name,
            $modSettings['elga_icon_max_width'] ? $modSettings['elga_icon_max_width'] : 60,
            $modSettings['elga_icon_max_height'] ? $modSettings['elga_icon_max_height'] : 60
        );

        return $name;
    }

    # http://imagine.readthedocs.org/en/latest/index.html
    # https://speakerdeck.com/avalanche123/introduction-to-imagine
    /*
     * Создаем мини-постер
     * http://imagine.readthedocs.org/en/latest/index.html
     * https://speakerdeck.com/avalanche123/introduction-to-imagine
     * @return bool ?
     *
        example:
        thumb(
            $dir . '/' . $fname,
            $dir . '/' . $thumb_fname,
            $maxWidth,
            $maxHeight
            );
     *
    */
    public static function thumb($img, $thumb,  $width = 300, $height = 300)
    {
        # Check if GD extension is loaded
        // if (!extension_loaded('gd') && !extension_loaded('gd2')) {
            // trigger_error("GD is not loaded", E_USER_WARNING);

            // return false;
        // }

        // $loader = require_once EXTDIR.'/elga_lib/vendor/autoload.php';
        try {
            $imagine = new \Imagine\Imagick\Imagine();
        } catch (\Imagine\Exception\RuntimeException $e) {
            $imagine = new \Imagine\Gd\Imagine();
        }
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

    public static function createChecks($key)
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

    public static function uint($val)
    {
        return abs(intval($val));
    }

    public static function updateFile($id, $fields, array $vals = [])
    {
        $db = database();
        $req = $db->query('', '
            UPDATE {db_prefix}elga_files
                SET ' . $fields . '
            WHERE id = {int:id}',
            array_merge([ 'id' => $id, ], $vals)
        );
    }

    public static function getNestedSetsManager()
    {
        global $db_type, $db_host,  $db_prefix, $db_passwd, $db_name, $db_user, $db_port;

        $ns = new \Interlab\NestedSets\Manager();
        $ns->db_table = $db_prefix . 'elga_albums';
        $ns->id_column = 'id';
        $ns->left_column = 'leftkey';
        $ns->right_column = 'rightkey';
        $ns->setDb( $db_type, $db_host, $db_port, $db_name, $db_user, $db_passwd );

        return $ns;
    }
}
