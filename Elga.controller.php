<?php

if (!defined('ELK'))
	die('No access...');

class Elga_Controller extends Action_Controller
{
    public function __construct()
    {
        
    }

    public function action_index()
    {
        global $context, $scripturl;

        $context['page_title'] = 'Галерея - Дом';

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery',
			'name' => 'Галерея',
		];

        loadCSSFile('elga.css');
        loadTemplate('Elga');

        loadJavascriptFile('jscroll-2.3.4/jquery.jscroll.js');
        // JavaScriptEscape(...)
        addInlineJavascript('
$(document).ready(function(){
    $(\'.elga_scroll\').jscroll({
        loadingHtml: \'<img src="loading.gif" alt="Loading" /> Loading...\',
        padding: 20,
        nextSelector: \'a.jscroll-next:last\',
        contentSelector: \'\'
    });
});
        ');

        $context['sub_template'] = 'home';

        if (isset($_REQUEST['sa'])) {
            $sa = 'action_' . $_REQUEST['sa'];
            if (method_exists($this, $sa)) {
                $this->$sa();
            }
        }

        $context['elga_albums'] = getAlbums();
    }

    // @todo: parse bbc ?
    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        if (!allowedTo('moderate_forum') && !allowedTo('admin_forum'))
            fatal_error('Не хватает прав!', false);

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = getAlbums();

		if (isset($_REQUEST['send']))
		{
			checkSession('post');
			validateToken('add_file');
			spamProtection('add_file');

			// No errors, yet.
			$context['errors'] = [];
			loadLanguage('Errors');

			// Could they get the right send topic verification code?
			require_once(SUBSDIR . '/VerificationControls.class.php');
			// require_once(SUBSDIR . '/Members.subs.php');

			// form validation
			require_once(SUBSDIR . '/DataValidator.class.php');
			$validator = new Data_Validator();
			$validator->sanitation_rules([
                'album' => 'int',
				'title' => 'trim|Util::htmlspecialchars',
				'descr' => 'trim|Util::htmlspecialchars'
			]);
			$validator->validation_rules([
                'album' => 'required|numeric',
				'title' => 'required', // |valid_email',
				'descr' => 'required'
			]);
			$validator->text_replacements([
                'album' => 'Album not selected!',
				'title' => 'Title is empty!',
				'descr' => $txt['error_message']
			]);

			// Any form errors
			if (!$validator->validate($_POST))
				$context['errors'] = $validator->validation_errors();

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'add_file',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification']))
                {
                    foreach ($context['require_verification'] as $error)
                        $context['errors'][] = $txt['error_' . $error];
                }
            }

            if (!isset($albums[$_POST['album']]))
                $context['errors'][] = 'Album not exists!';

            $img = uploadImage();

            require_once(SUBSDIR . '/Post.subs.php');
            $descr = $validator->descr;
            preparsecode($descr);

			// No errors, then send the PM to the admins
			if (empty($context['errors']))
			{
                $db = database();

                $db->insert('', '{db_prefix}elga_files',
                    [ 'orig_name' => 'string', 'fname' => 'string', 'fsize' => 'raw', 'thumb' => 'string', 'id_album' => 'int',
                      'title' => 'string', 'description' => 'string', 'id_member' => 'int', 'member_name' => 'string', 'exif' => 'string' ],
                    [ $img['orig_name'], $img['name'], $img['size'], $img['thumb'], $validator->album, $validator->title, 
                      $descr, $user_info['id'], $user_info['name'], '' ],
                    [ 'id_member', 'id_topic' ]
                );
                $insert_id = $db->insert_id('{db_prefix}elga_files', 'id');

                redirectexit('action=gallery;sa=file;id=' . $insert_id);
			}
			else
			{
                $context['elga_album'] = $validator->album;
				$context['elga_title'] = $validator->title;
				$context['elga_descr'] = $descr;
			}
		}

        $context['sub_template'] = 'add_file';

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=add_file',
			'name' => 'New File',
		];

        $context['page_title'] = 'New File';

		if ($context['require_verification']) {
            require_once(SUBSDIR . '/VerificationControls.class.php');
            $verificationOptions = [
                'id' => 'add_file',
            ];
            $context['require_verification'] = create_control_verification($verificationOptions);
            $context['visual_verification_id'] = $verificationOptions['id'];
        }
		createToken('add_file');

        /*
        foreach ($albums as &$row) {
            $row['selected'] = false;
        }
        */
        $context['elga_album'] = isset($_GET['album']) ? (int) $_GET['album'] : 0;
        $context['elga_albums'] =& $albums;
        $context['elga_sa'] = 'add_file';
    }

    public function action_album()
    {
        global $context, $scripturl, $boardurl;

        if (empty($_GET['id']))
            redirectexit('action=gallery');

        $albums = getAlbums();
        if (empty($albums[$_GET['id']]))
            fatal_error('Album not found!', false);
        $context['elga_album'] = $album = $albums[$_GET['id']];

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=album;id=' . $album['id'],
			'name' => $album['name'],
		];

        $context['page_title'] = 'Галерея - ' . $album['name'];

        $context['sub_template'] = 'album';

        $db = database();

        // $limit = 20;
        $per_page = 4;

        $req = $db->query('', '
            SELECT COUNT(*)
            FROM {db_prefix}elga_files
            WHERE id_album = {int:id}
            LIMIT 1',
            ['id' => $album['id']]);
        if (!$db->num_rows($req)) {
            $totalfiles = 0;
        }
        else {
            $totalfiles = $db->fetch_row($req)[0];
        }
        $db->free_result($req);

        $context['elga']['total'] = $totalfiles;
        $context['elga']['per_page'] = $per_page;
        $context['elga']['is_next_start'] = intval($_REQUEST['start']) + $per_page < $totalfiles;
        // echo $context['elga']['is_next_start'], ' ', $_REQUEST['start'], ' ', $totalfiles;

		$context['page_index'] = constructPageIndex(
            $scripturl . '?action=gallery;sa=album;id=' . $album['id'],
            $_REQUEST['start'],
            $totalfiles,
            $per_page,
            true
        );
		$context['start'] = $_REQUEST['start'];

        $context['elga']['next_start'] = $context['start'] + $per_page;
        //echo $context['elga']['next_start'], ' из ', $totalfiles;

		// This is information about which page is current, and which page we're on - in case you don't like the constructed page index. (again, wireles..)
		$context['page_info'] = array(
			'current_page' => $_REQUEST['start'] / $per_page + 1,
			'num_pages' => floor(($totalfiles - 1) / $per_page) + 1,
		);

        // echo ' LIMIT ' . $context['start'] . ', ' . $per_page;

        $req = $db->query('', '
            SELECT f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.title, f.description, f.views, f.id_member, f.member_name
            FROM {db_prefix}elga_files as f
            WHERE f.id_album = {int:album}
            ORDER BY f.id DESC
            LIMIT ' . $context['start'] . ', ' . $per_page,
            [ 'album' => $album['id'], ]
        );

        $dir = $boardurl . '/files/gallery';
        $context['elga_files'] = [];
        if ($db->num_rows($req) > 0)
        {
            while ($row = $db->fetch_assoc($req)) {
                $row['icon'] = $dir . '/' . $row['fname'];
                $context['elga_files'][$row['id']] = $row;
            }
        }
        $db->free_result($req);
        // print_r($context['elga_files']);

        if ($_GET['type'] === 'js') {
            // Clear the templates
            Template_Layers::getInstance()->removeAll();
            $context['sub_template'] = 'album_js';
            // sleep(1);
            // $context['json_data'] = [];
            // loadTemplate('Json');
            // $context['sub_template'] = 'send_json';
        }
    }

    // @todo: parse bbc ?
    public function action_edit_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = getAlbums();
        $context['elga_albums'] =& $albums;

		if (isset($_REQUEST['send']))
		{
			checkSession('post');
			validateToken('add_file');
			spamProtection('add_file');

            if (empty($_POST['id']))
                redirectexit('action=gallery');
            $id = (int) $_POST['id'];
            $db = database();
            $req = $db->query('', '
            SELECT
                f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.id_album, f.title, f.description, f.views, f.id_member, f.member_name,
                a.name AS album_name
            FROM {db_prefix}elga_files as f
                INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
            WHERE f.id = {int:id}
            LIMIT 1', [
                'id' => $id,
            ]);
            if (!$db->num_rows($req)) {
                $db->free_result($req);
                fatal_error('File not found!', false);
            }
            $context['elga_file'] = $file = $db->fetch_assoc($req);
            $db->free_result($req);

            // perms
            if ($user_info['id'] != $file['id_member'] && !allowedTo('moderate_forum') && !allowedTo('admin_forum'))
                fatal_error('Вы не можете редактировать эту запись! Не хватает прав!', false);

			// No errors, yet.
			$context['errors'] = [];
			loadLanguage('Errors');

			// Could they get the right send topic verification code?
			require_once(SUBSDIR . '/VerificationControls.class.php');

			// form validation
			require_once(SUBSDIR . '/DataValidator.class.php');
			$validator = new Data_Validator();
			$validator->sanitation_rules([
                'album' => 'int',
				'title' => 'trim|Util::htmlspecialchars',
				'descr' => 'trim|Util::htmlspecialchars'
			]);
			$validator->validation_rules([
                'album' => 'required|numeric',
				'title' => 'required',
				'descr' => 'required'
			]);
			$validator->text_replacements([
                'album' => 'Album not selected!',
				'title' => 'Title is empty!',
				'descr' => $txt['error_message']
			]);

			// Any form errors
			if (!$validator->validate($_POST))
				$context['errors'] = $validator->validation_errors();

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'add_file',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification']))
                {
                    foreach ($context['require_verification'] as $error)
                        $context['errors'][] = $txt['error_' . $error];
                }
            }

            if (!isset($albums[$_POST['album']]))
                $context['errors'][] = 'Album not exists!';

            $img = 0;
            if ('' !== $_FILES['image']['name']) {
                $img = uploadImage();
            }

            require_once(SUBSDIR . '/Post.subs.php');
            $descr = $validator->descr;
            preparsecode($descr);

			// No errors, then send the PM to the admins
			if (empty($context['errors']))
			{
                $db->query('', '
                    UPDATE {db_prefix}elga_files
                    SET ' . ($img ? '
                        orig_name = {string:oname},
                        fname = {string:fname},
                        fsize = {raw:fsize},
                        thumb = {string:thumb},' : '') . '
                        id_album = {int:album},
                        title = {string:title},
                        description = {string:descr},
                        id_member = {int:mem_id},
                        member_name = {string:mem_name}
                    WHERE id = {int:id}',
                    [
                        'oname' => $img ? $img['orig_name'] : '',
                        'fname' => $img ? $img['name'] : '',
                        'fsize' => $img ? $img['size'] : '',
                        'thumb' => $img ? 'thumb' : '',
                        'album' => $validator->album,
                        'title' => $validator->title, 
                        'descr' => $descr,
                        'mem_id' => $user_info['id'],
                        'mem_name' => $user_info['name'],
                        'id' => $id,
                    ]
                );

                // del old image
                if ($db->affected_rows() && '' !== $file['fname'] && $img && $file['fname'] !== $img['name']) {
                    delOldImage($file);
                }

                redirectexit('action=gallery;sa=file;id=' . $id);
			}
			else
			{
                $context['elga_album'] = $validator->album;
				$context['elga_title'] = $validator->title;
				$context['elga_descr'] = $descr;
			}
		}

        // GET
        if (empty($_GET['id']))
            redirectexit('action=gallery');

        $id = (int) $_GET['id'];

        $db = database();
        $req = $db->query('', '
        SELECT
            f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.id_album, f.title, f.description, f.views, f.id_member, f.member_name,
            a.name AS album_name
        FROM {db_prefix}elga_files as f
            INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
        WHERE f.id = {int:id}
        LIMIT 1', [
            'id' => $id,
        ]);
        if (!$db->num_rows($req)) {
            $db->free_result($req);
            fatal_error('File not found!', false);
        }
        $context['elga_file'] = $file = $db->fetch_assoc($req);
        $db->free_result($req);

        // perms
        if ($user_info['id'] != $file['id_member'] && !allowedTo('moderate_forum') && !allowedTo('admin_forum'))
            fatal_error('Вы не можете редактировать эту запись! Не хватает прав!', false);

        $context['elga_album'] = $file['id_album'];
        $context['elga_title'] = $file['title'];
        $context['elga_descr'] = $file['description'];

        $context['sub_template'] = 'add_file';

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=edit_file;id=' . $file['id'],
			'name' => 'Edit ' . $file['title'],
		];

        $context['page_title'] = 'Edit ' . $file['title'];

		if ($context['require_verification']) {
            require_once(SUBSDIR . '/VerificationControls.class.php');
            $verificationOptions = [
                'id' => 'add_file',
            ];
            $context['require_verification'] = create_control_verification($verificationOptions);
            $context['visual_verification_id'] = $verificationOptions['id'];
        }
		createToken('add_file');

        /*
        foreach ($albums as &$row) {
            $row['selected'] = false;
        }
        */
        $context['elga_album'] = (int) $file['id_album'];
        $context['elga_sa'] = 'edit_file';
    }

    public function action_file()
    {
        global $context, $scripturl, $boardurl;

        if (empty($_GET['id']))
            redirectexit('action=gallery');

        //$albums = getAlbums();

        $id = (int) $_GET['id'];

        $db = database();
        $req = $db->query('', '
        SELECT
            f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.id_album, f.title, f.description, f.views, f.id_member, f.member_name,
            a.name AS album_name
        FROM {db_prefix}elga_files as f
            INNER JOIN {db_prefix}elga_albums AS a ON (a.id = f.id_album)
        WHERE f.id = {int:id}
        LIMIT 1', [
            'id' => $id,
        ]);
        if (!$db->num_rows($req)) {
            $db->free_result($req);
            fatal_error('File not found!', false);
        }

        $dir = $boardurl . '/files/gallery';
        $file = $db->fetch_assoc($req);
        $context['elga_file'] =& $file;
        $db->free_result($req);
        $context['elga_file']['icon'] = $dir . '/' .  $context['elga_file']['fname'];
        require_once(SUBSDIR . '/Post.subs.php');
        $file['description'] = parse_bbc(un_preparsecode($file['description']));
        censorText($file['description']);

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=album;id=' . $file['id_album'],
			'name' => $file['album_name'],
		];

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=file;id=' . $file['id'],
			'name' => $file['title'],
		];

        $context['page_title'] = '' . $file['title'];

        $context['sub_template'] = 'file';

        return;
    }
}

function getAlbums()
{
    global $boardurl;

    $db = database();

    // @todo: limit
    $req = $db->query('', '
    SELECT id, name, description, icon
    FROM {db_prefix}elga_albums
    LIMIT 100', []);

    // $data = new Foo();
    $data = [];
    if ($db->num_rows($req) > 0)
    {
        while ($row = $db->fetch_assoc($req)) {
            $row['icon'] = filter_var($row['icon'], FILTER_VALIDATE_URL) ? $row['icon'] : $boardurl . '/files/gallery/icons/' . $row['icon'];
            $data[$row['id']] = $row;
        }
    }
    $db->free_result($req);

    return $data;
}

function uploadImage()
{
    global $context;

    # http://www.php.net/manual/ru/features.file-upload.errors.php
    if (UPLOAD_ERR_OK !== $_FILES['image']['error'])
    {
        switch($_FILES['image']['error'])
        {
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

    if (!empty($_FILES['image']) && $_FILES['image']['error'] === 0)
    {
        $fname = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $directory = BOARDDIR . '/files/gallery';
        $max_size = 1024 * 1024 * 3;

        if (!is_dir($directory))
            fatal_error('Директория постеров указана неверно!', false);

        if (!is_uploaded_file($_FILES['image']['tmp_name']))
            fatal_error('Ошибка загрузки файла на сервер. Попробуйте заново закачать файл.', false);

        $fsize = filesize($_FILES['image']['tmp_name']);
        if ($fsize > $max_size)
            fatal_error('Файл превышает максимально допустимый размер!', false);

        if (preg_match('~\\/:\*\?"<>|\\0~', $_FILES['image']['name']))
            fatal_error(Util::htmlspecialchars($_FILES['image']['name']) . 'Недопустимые символы в имени постер-файла!', false);

        if (!preg_match('~png|gif|jpg|jpeg~i', $ext))
            fatal_error('Расширение постера должно быть <strong>png, gif, jpg, jpeg</strong>.', false);

        $nfname = sha1_file($_FILES['image']['tmp_name']) . '.' . $ext;
        $date = date('Y/m/d', time());
        $dest_dir = $directory . '/' . $date;
        if (!is_dir($dest_dir)) {
            if (!mkdir($dest_dir, 0777, true))
                fatal_error('Не получается создать директорию ' . $dest_dir);
        }
        $dest_name = $dest_dir . '/' . $nfname;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest_name)) {
            fatal_error('Ошибка копирования временного файла!', false);
        } else {

            // create thumb image
            $thumb_name = pathinfo($dest_name, PATHINFO_FILENAME) . '_thumb.' . pathinfo($dest_name, PATHINFO_EXTENSION);
            $width = 350;
            $height = 350;
            thumb($dest_name, $dest_dir . '/' . $thumb_name, $width, $height);

            return [
    'name' => $date . '/' . $nfname,
    'orig_name' => $_FILES['image']['name'], // ? need sanitize?
    'size' => $fsize,
    'thumb' => $date . '/' . $thumb_name,
            ];
        }
    }

    return false;
}

function delOldImage($img)
{
    $path = BOARDDIR . '/files/gallery';
    $orig = $path . '/' . $img['fname'];
    $thumb = $path . '/' . $img['thumb'];
    foreach ([$orig, $thumb] as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
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

    $loader = require_once(EXTDIR . '/elga_lib/vendor/autoload.php');

    $imagine = new \Imagine\Gd\Imagine();
    $mode = \Imagine\Image\ImageInterface::THUMBNAIL_INSET; # THUMBNAIL_OUTBOUND
    $image = $imagine->open($img);
    $size = $image->getSize();

    # Если размеры меньше, то масштабирования не нужно
    if ($size->getWidth() <= $width && $size->getHeight() <= $height)
        $image->copy()
              ->save($thumb);
    else
        $image->thumbnail(new \Imagine\Image\Box($width, $height), $mode)
              ->save($thumb);

    return true;
}

class Foo extends ArrayObject
{
    
}
