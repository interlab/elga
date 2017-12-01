<?php

if (!defined('ELK')) {
    die('No access...');
}

use Imagine\Image\Metadata\ExifMetadataReader;

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
		$row['preview'] = $row['preview'];
        $row['preview-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'].';mode=preview';
        $row['icon'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
        $row['hsize'] = round($row['fsize'] / 1024, 2) . ' ' . $txt['kilobyte'];
        $row['description'] = parse_bbc($row['description']);
        $row['img-bbc'] = '[img]' . $boardurl . '/gallery.php?id=' . $row['id'] . '[/img]';
        // $row['img-bbc'] = '[img]' . $scripturl . '?action=gallery;sa=show;id='.$row['id'] . '[/img]'; // [img]...[/img] not work!
        // $row['img-url'] = $boardurl . '/gallery.php?id=' . $row['id'];
        $row['img-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'];
        $row['img-download-url'] = $scripturl . '?action=gallery;sa=show;id='.$row['id'] . ';mode=download';
		$row['exif'] = empty($row['exif']) ? [] : json_decode($row['exif']);
		// dump($row['exif']);
        $db->free_result($req);

        return $row;
    }

    public static function countFiles(array $query=[])
    {
        $query['user'] = empty($query['user']) ? 0 : $query['user'];
        $query['album'] = empty($query['album']) ? 0 : $query['album'];

        $db = database();
        $req = $db->query('', '
            SELECT COUNT(*)
            FROM {db_prefix}elga_files AS f
            WHERE 1=1' . ($query['user'] ? '
                AND f.id_member = {int:id_member}' : '') . '' . ($query['album'] ? '
                AND f.id_album = {int:id_album}' : '') . '
            LIMIT 1',
            [
                'id_member' => $query['user'],
                'id_album' => $query['album'],
            ]
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

        return self::getFiles(0, $limit);
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

    public static function getFiles($offset, $limit, array $params = [])
    {
        global $modSettings, $txt, $boardurl, $scripturl;

        $sort = self::parseSortQuery( ( isset($params['sort']) ? $params['sort'] : '' ) );
        $params['album'] = empty($params['album']) ? 0 : $params['album'];
        $params['user'] = empty($params['user']) ? 0 : $params['user'];

        $db = database();
        $req = $db->query('', '
            SELECT
                f.id, f.orig_name, f.fname, f.thumb, f.preview, f.fsize, f.title,
                f.description, f.views, f.id_member, f.member_name,
                a.id AS alb_id, a.name AS alb_name
            FROM {db_prefix}elga_files as f
                INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
            WHERE 1=1' . ($params['album'] ? '
                AND f.id_album = {int:id_album}' : '') . ($params['user'] ? '
                AND f.id_member = {int:id_member}' : '') . '
            ORDER BY ' . (empty($sort) ? 'f.id DESC' : $sort) . '
            LIMIT {int:start}, {int:per_page}',
            [
                'id_member' => $params['user'],
                'id_album' => $params['album'],
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

        $id = 0;
        if ($db->num_rows($req)) {
            $id = (int) $db->fetch_row($req)[0];
        }
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

        $id = 0;
        if ($db->num_rows($req)) {
            $id = (int) $db->fetch_row($req)[0];
        }
        $db->free_result($req);

        return $id;
    }

    public static function getAlbums()
    {
        global $scripturl, $modSettings;

        $db = database();

        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon_name AS icon, a.icon_thumb, a.icon_fhash, a.leftkey, a.rightkey,
            (COUNT(DISTINCT p.id) - 1) AS depth, COUNT(DISTINCT f.id) as total
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
                $row['icon'] = $modSettings['elga_icons_url'].'/'.$row['icon_thumb'];
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

        if ( ! is_numeric($id) ) {
            fatal_error('Bad id value. Required int type.', false);
        }

        $db = database();
        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon_name AS icon, a.icon_thumb, a.leftkey, a.rightkey,
            (COUNT(DISTINCT p.id) - 1) AS depth, COUNT(DISTINCT f.id) as total
        FROM {db_prefix}elga_albums AS a
            INNER JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
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
        $row['icon'] = $modSettings['elga_icons_url'].'/'.$row['icon_thumb'];
        $row['url'] = $scripturl.'?action=gallery;sa=album;id='.$row['id'];
        if ($load_descendants) {
            $row['descendants'] = self::getSubAlbums($row);
        }

        return $row;
    }

    public static function getSubAlbums($r)
    {
        global $modSettings;

        $db = database();
        $req = $db->query('', '
        SELECT a.*, (COUNT(DISTINCT p.id) - 1) AS depth, COUNT(DISTINCT f.id) as total
        FROM {db_prefix}elga_albums AS a
            INNER JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
            LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
        WHERE  a.leftkey > ' . $r['leftkey'] . '
            AND a.rightkey < ' . $r['rightkey'] . '
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 250', []);

        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = $modSettings['elga_icons_url'].'/'.$row['icon_thumb'];
                $data[] = $row;
            }
        }
        $db->free_result($req);

        return $data;
    }

    public static function getParentsAlbums($id, $depth = null, $get_current = false)
    {
        global $modSettings, $scripturl;

        $a = self::getAlbum($id);
        if (empty($a)) {
            return null;
        }
        if (empty($a['depth'])) {
            if ( ! $get_current ) {
                return null;
            } else {
                return [$id => $a];
            } 
        }

        $db = database();
        $req = $db->query('', '
        SELECT a.id, a.name, a.description, a.icon_name AS icon, a.icon_thumb, a.leftkey, a.rightkey,
            (COUNT(DISTINCT p.id) - 1) AS depth, COUNT(DISTINCT f.id) as total
        FROM {db_prefix}elga_albums AS a
            INNER JOIN {db_prefix}elga_albums AS p ON (a.leftkey BETWEEN p.leftkey AND p.rightkey)
            LEFT JOIN {db_prefix}elga_files AS f ON (a.id = f.id_album)
        WHERE a.leftkey < ' . $a['leftkey'] . '
            AND a.rightkey > ' . $a['rightkey'] . '
        GROUP BY a.id
        ORDER BY a.leftkey
        LIMIT 100', []);

        $data = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = $modSettings['elga_icons_url'].'/'.$row['icon_thumb'];
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


    /*
        key
        path
        maxsize
        max_thumb_width
        max_thumb_height
        max_preview_width
        max_preview_height
        is_preview
    */
    public static function createFileImage(array $img = [])
    {
        global $context, $modSettings;

        if (empty($img)) {
            $img = [
                'key' => 'image',
                'path' => $modSettings['elga_files_path'],
                'maxsize' => 1024 * 1024 * 3,
                'max_thumb_width' => empty($modSettings['elga_imgthumb_max_width']) ? 200 : $modSettings['elga_imgthumb_max_width'],
                'max_thumb_height' => empty($modSettings['elga_imgthumb_max_height']) ? 200 : $modSettings['elga_imgthumb_max_height'],
                'max_preview_width' => empty($modSettings['elga_imgpreview_max_width']) ? 450 : $modSettings['elga_imgpreview_max_width'],
                'max_preview_height' => empty($modSettings['elga_imgpreview_max_height']) ? 450 : $modSettings['elga_imgpreview_max_height'],
                'is_preview' => true,
            ];
        }

        if ( ! self::findFileUploadErrors($img['key'], $img['path'], $img['maxsize']) ) {
            return false;
        }

        $name = $_FILES[$img['key']]['name'];
        $tmpname = $_FILES[$img['key']]['tmp_name'];
        list(, , $ext, $fname) = array_values(pathinfo($name));
        $fsize = filesize($tmpname);

        $sha1 = sha1_file($tmpname);
        $nfname = $sha1 . '.' . $ext;
        $date = date('Y/m/d', time());
        $dest_dir = $img['path'].'/'.$date;
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
            fatal_error('Уже существует файл с таким же названием');
        }

        $originalname = $dest_dir.'/'.pathinfo($nfname, PATHINFO_FILENAME).'_original.'.$ext;

        if (!move_uploaded_file($tmpname, $originalname) && $sha1 === sha1_file($originalname)) {
            fatal_error('Ошибка копирования временного файла!', false);
            die('error copy file');
        }

        try {
            $imagine = new \Imagine\Imagick\Imagine();
        } catch (\Imagine\Exception\RuntimeException $e) {
            try {
                $imagine = new \Imagine\Gd\Imagine();
            } catch (\Imagine\Exception\RuntimeException $e) {
                $imagine = new \Imagine\Gmagick\Imagine();
            }
        }

        try {
            $image = $imagine
				->setMetadataReader(new ExifMetadataReader())
				->open($originalname);
			$exif = $image->metadata()->toArray();
			unset($exif['filepath']);
			unset($exif['uri']);
			// dump($exif);
			// die;
			$exif = json_encode($exif);
			$image->save($dest_name);
        } catch (Exception $e) {
            unlink($originalname);
            throw new Exception('Error save file');
        }

        // create thumb image
        $thumb_name = pathinfo($dest_name, PATHINFO_FILENAME).'_thumb.'.pathinfo($dest_name, PATHINFO_EXTENSION);
        self::thumb($dest_name, $dest_dir.'/'.$thumb_name, $img['max_thumb_width'], $img['max_thumb_height']);

        // create preview image
        if ( $img['is_preview'] ) {
            $preview_name = pathinfo($dest_name, PATHINFO_FILENAME).'_preview.'.pathinfo($dest_name, PATHINFO_EXTENSION);
            self::thumb($dest_name, $dest_dir.'/'.$preview_name, $img['max_preview_width'], $img['max_preview_height']);
        }

        return [
            'name' => $date.'/'.$nfname,
            'orig_name' => $name, // ? need sanitize?
            'size' => $fsize,
            'thumb' => $date.'/'.$thumb_name,
            'preview' => $img['is_preview'] ? $date . '/' . $preview_name : '',
            'fhash' => $sha1,
			'exif' => '', // $exif, // todo: chek if big size
        ];
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
		$pinf = pathinfo($img['fname']);
		$dirname = $pinf['dirname'];
		$ext = $pinf['extension'];
		$name = $pinf['filename'];
		// dump($img);
		// die;
        $imgs = [
			$path . '/' . $dirname . '/' . $name . '_original.' . $ext,
			$path.'/'.$img['fname'],
			$path.'/'.$img['thumb'],
			$path.'/'.$img['preview'],
		];
        foreach ( $imgs as $file ) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    public static function delOldIcon($icon)
    {
        global $modSettings;

        $path = $modSettings['elga_icons_path']; //BOARDDIR.'/files/gallery/icons';
        $file = $path.'/'.$icon;
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public static function uploadIcon()
    {
        global $modSettings;

        $file = self::createFileImage([
            'key' => 'icon',
            'path' => $modSettings['elga_icons_path'], //BOARDDIR.'/files/gallery/icons';
            'maxsize' => 1024 * 1024 * 3,
            'max_thumb_width' => $modSettings['elga_icon_max_width'] ? $modSettings['elga_icon_max_width'] : 60,
            'max_thumb_height' => $modSettings['elga_icon_max_height'] ? $modSettings['elga_icon_max_height'] : 60,
            'is_preview' => false,
        ]);

        return $file;
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
        // @TODO: $imagine = new \Imagine\Gmagick\Imagine();
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

    public static function moveAlbum($action, $current, $id)
    {
        switch ($action) {
            case 'moveToPrevSiblingOf':
            case 'moveToNextSiblingOf':
            case 'moveToFirstChildOf':
            case 'moveToLastChildOf':
                $ns = self::getNestedSetsManager();
                if ( ! method_exists($ns, $action) ) {
                    fatal_error('Unknown move method');
                }

                if ( $ns->issetNode($current) && $ns->issetNode($id) ) {
                    if ($ns->isParent($current, $id)) {
                        fatal_error('Node Is Parent!');
                    }

                    $succ = call_user_func_array([$ns, $action], [$current, $id]);
                } else {
                    $succ = false;
                }

                break;
            default:
                $succ = null;
        }

        return $succ;
    }

    /**
     * 
     * @global array $context
     * @param string $id
     * @param string $body
     */
    public static function create_editor($id, $body)
    {
        global $context;

        // Needed for the editor and message icons.
        require_once(SUBSDIR . '/Editor.subs.php');

        // Now create the editor.
        $editorOptions = [
            'id' => $id,
            'value' => $body,
            'labels' => [
                'post_button' => 'Save',
                'post_name' => 'send',
            ],
            // add height and width for the editor
            'height' => '275px',
            'width' => '100%',
            // We do XML preview here.
            'preview_type' => 0, // 2
        ];

        $context['post_box_name'] = $editorOptions['id'];

        // Initialize the editor
        create_control_richedit($editorOptions);
    }

    // This function is used to determine the camera details for a specific image. It returns an array with the parameters.
    public static function cameraUsed($imagePath)
    {
        if (empty($imagePath)) {
            throw new InvalidArgumentEsception('Path is empty!');
        }

        if (!file_exists($imagePath)) {
            throw new InvalidArgumentEsception('File not found!');
        }

        $exif_exif = null;
        $exif_ifd0 = null;
        if (function_exists('exif_read_data') && @exif_imagetype($imagePath) === 2) {
            // There are 2 arrays which contains the information we are after, so it's easier to state them both
            $exif_ifd0 = read_exif_data($imagePath ,'IFD0', 0);      
            $exif_exif = read_exif_data($imagePath ,'EXIF', 0);
        }

        //error control
        $notFound = "Unavailable";
        $ifd0_is_ary = is_array($exif_ifd0);

        // Make
        if ($ifd0_is_ary && array_key_exists('Make', $exif_ifd0)) {
            $camMake = $exif_ifd0['Make'];
        } else {
            $camMake = $notFound;
        }

        // Model
        if ($ifd0_is_ary && array_key_exists('Model', $exif_ifd0)) {
            $camModel = $exif_ifd0['Model'];
        } else {
            $camModel = $notFound;
        }

        // Exposure
        if ($ifd0_is_ary && array_key_exists('ExposureTime', $exif_ifd0)) {
            $camExposure = $exif_ifd0['ExposureTime'];
        } else {
            $camExposure = $notFound;
        }

        // Date
        if ($ifd0_is_ary && array_key_exists('DateTime', $exif_ifd0)) {
            $camDate = $exif_ifd0['DateTime'];
        } else {
            $camDate = $notFound;
        }

        // Aperture
        if ($ifd0_is_ary && is_array($exif_ifd0['COMPUTED']) && array_key_exists('ApertureFNumber', $exif_ifd0['COMPUTED'])) {
            $camAperture = $exif_ifd0['COMPUTED']['ApertureFNumber'];
        } else {
            $camAperture = $notFound;
        }

        // Heght
        if ($ifd0_is_ary && is_array($exif_ifd0['COMPUTED']) && array_key_exists('Height', $exif_ifd0['COMPUTED'])) {
            $camHeight = $exif_ifd0['COMPUTED']['Height'];
        } else {
            $camHeight = $notFound;
        }

        // Width
        if ($ifd0_is_ary && is_array($exif_ifd0['COMPUTED']) && array_key_exists('Width', $exif_ifd0['COMPUTED'])) {
            $camWidth = $exif_ifd0['COMPUTED']['Width'];
        } else {
            $camWidth = $notFound;
        }

        // Orientation
        if ($ifd0_is_ary && array_key_exists('Orientation', $exif_ifd0)) {
            $camOrientation = $exif_ifd0['Orientation'];
        } else {
            $camOrientation = $notFound;
        }

        // XResolution
        if ($ifd0_is_ary && array_key_exists('XResolution', $exif_ifd0)) {
            $camXResolution = $exif_ifd0['XResolution'];
        } else {
            $camXResolution = $notFound;
        }

        // YResolution
        if ($ifd0_is_ary && array_key_exists('YResolution', $exif_ifd0)) {
            $camYResolution = $exif_ifd0['YResolution'];
        } else {
            $camYResolution = $notFound;
        }

        // PlanarConfiguration
        if ($ifd0_is_ary && array_key_exists('PlanarConfiguration', $exif_ifd0)) {
            $camPlanarConfiguration = $exif_ifd0['PlanarConfiguration'];
        } else {
            $camPlanarConfiguration = $notFound;
        }

        // ResolutionUnit
        if ($ifd0_is_ary && array_key_exists('ResolutionUnit', $exif_ifd0)) {
            $camResolutionUnit = $exif_ifd0['ResolutionUnit'];
        } else {
            $camResolutionUnit = $notFound;
        }

        // ISO
        if (is_array($exif_exif) && array_key_exists('ISOSpeedRatings', $exif_exif)) {
            $camIso = $exif_exif['ISOSpeedRatings'];
        } else {
            $camIso = $notFound;
        }

        $return = [
            'Make' => $camMake,
            'Model' => $camModel,
            'Exposure' => $camExposure,
            'Aperture' => $camAperture,
            'Date' => $camDate,
            'ISO' => $camIso,
            'Height' =>$camHeight,
            'Width' => $camWidth,
            'Orientation' => $camOrientation,
            'XResolution' => $camXResolution,
            'YResolution' => $camYResolution,
            'PlanarConfiguration' => $camPlanarConfiguration,
            'ResolutionUnit' => $camResolutionUnit,
        ];

        return $return;
    }
}
