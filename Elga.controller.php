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

        $context['sub_template'] = 'home';

        if (isset($_REQUEST['sa'])) {
            $sa = 'action_' . $_REQUEST['sa'];
            if (method_exists($this, $sa)) {
                $this->$sa();
            }
        }

        $context['elga_albums'] = getAlbums();
    }

    // @todo: parse bbc
    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

		isAllowedTo('admin_forum');

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
            preparsecode($validator->descr);
            
			// No errors, then send the PM to the admins
			if (empty($context['errors']))
			{
                $db = database();

                $db->insert('', '{db_prefix}elga_files',
                    [ 'orig_name' => 'string', 'fname' => 'string', 'fsize' => 'raw', 'id_album' => 'int',
                      'title' => 'string', 'description' => 'string', 'id_member' => 'int', 'member_name' => 'string', ],
                    [ $img['orig_name'], $img['name'], $img['size'], $validator->album, $validator->title, 
                      $validator->descr, $user_info['id'], $user_info['name'], ],
                    [ 'id_member', 'id_topic' ]
                );
                $insert_id = $db->insert_id('{db_prefix}elga_files', 'id');

                redirectexit('action=gallery;sa=file;id=' . $insert_id);
			}
			else
			{
                $context['elga_album'] = $validator->album;
				$context['elga_title'] = $validator->title;
				$context['elga_descr'] = $validator->descr;
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

        // @todo limit
        $req = $db->query('', '
        SELECT f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.title, f.description, f.views, f.id_member, f.member_name
        FROM {db_prefix}elga_files as f
        WHERE f.id_album = {int:album}
        ORDER BY f.id DESC
        LIMIT 100', [
            'album' => $album['id'],
        ]);

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
    }

    // @todo: parse bbc
    public function action_edit_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

		isAllowedTo('admin_forum');

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
            
            // @todo: perms
            
			// No errors, yet.
			$context['errors'] = [];
			loadLanguage('Errors');

			// Could they get the right send topic verification code?
			require_once(SUBSDIR . '/VerificationControls.class.php');
			// require_once(SUBSDIR . '/Members.subs.php');

			// form validation
			require_once(SUBSDIR . '/DataValidator.class.php');
			$validator = new Data_Validator();
            require_once(SUBSDIR . '/Post.subs.php');
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

            $img = 0;
            if ('' !== $_FILES['image']['name']) {
                $img = uploadImage();
            }
            
            // @todo: del old image

            require_once(SUBSDIR . '/Post.subs.php');
            preparsecode($validator->descr);

			// No errors, then send the PM to the admins
			if (empty($context['errors']))
			{
                $db->query('', '
                    UPDATE {db_prefix}elga_files
                    SET ' . ($img ? '
                        orig_name = {string:oname},
                        fname = {string:fname},
                        fsize = {raw:fsize},' : '') . '
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
                        'album' => $validator->album,
                        'title' => $validator->title, 
                        'descr' => $validator->descr,
                        'mem_id' => $user_info['id'],
                        'mem_name' => $user_info['name'],
                        'id' => $id,
                    ]
                );

                redirectexit('action=gallery;sa=file;id=' . $id);
			}
			else
			{
                $context['elga_album'] = $validator->album;
				$context['elga_title'] = $validator->title;
				$context['elga_descr'] = $validator->descr;
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
        
        // @todo: perms
        
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
        $file['description'] = censorText(parse_bbc(un_preparsecode($file['description'])));

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

    $data = new Foo();
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
            return [
    'name' => $date . '/' . $nfname,
    'orig_name' => $_FILES['image']['name'], // ? need sanitize?
    'size' => $fsize,
            ];
        }
    }

    return false;
}

class Foo extends ArrayObject
{
    
}
