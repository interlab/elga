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

        $context['elga']['albums'] = $this->getAlbums();
    }

    public function getAlbums()
    {
        $db = database();

        $req = $db->query('', '
        SELECT id, name, description, icon
        FROM {db_prefix}elga_albums
        LIMIT 100', []);

        $data = [];
		if ($db->num_rows($req) > 0)
		{
			while ($album = $db->fetch_assoc($req)) {
				$data[$album['id']] = $album;
            }
		}
		$db->free_result($req);

        return $data;
    }
    
    public function action_ugli()
    {
        global $context, $scripturl;

		$context['linktree'][] = [
			'url' => $scripturl . '?action=gallery;sa=ugli',
			'name' => 'Ugli',
		];

        $context['sub_template'] = 'ugli';

        // echo 'sa - ugli';
        // 

        /*
        $context['html_headers'] = '
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css" />
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://raw.githubusercontent.com/elkarte/elkarte.net/master/elk/home.css">';
        */
    }

    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

		isAllowedTo('admin_forum');

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));
        
        // @todo ...
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
			require_once(SUBSDIR . '/Members.subs.php');

			// form validation
			require_once(SUBSDIR . '/DataValidator.class.php');
			$validator = new Data_Validator();
			$validator->sanitation_rules([
				'title' => 'trim|Util::htmlspecialchars',
				'descr' => 'trim|Util::htmlspecialchars'
			]);
			$validator->validation_rules([
				'title' => 'required', // |valid_email',
				'descr' => 'required'
			]);
			$validator->text_replacements([
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

            uploadImage();

			// No errors, then send the PM to the admins
			if (empty($context['errors']))
			{
                /*
				$admins = admins();
				if (!empty($admins))
				{
					require_once(SUBSDIR . '/PersonalMessage.subs.php');
					sendpm(array(
                            'to' => array_keys($admins),
                            'bcc' => []
                        ),
                        $txt['contact_subject'],
                        $_REQUEST['descr'],
                        false,
                        array('id' => 0, 'name' => $validator->title, 'username' => $validator->title)
                    );
				}

				redirectexit('action=gallery;sa=done');
                
                */
                
			}
			else
			{
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
        $context['html_headers'] = '
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.css" />
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://raw.githubusercontent.com/elkarte/elkarte.net/master/elk/home.css">';
        */

        $albums = $this->getAlbums();
        foreach ($albums as &$row) {
            $row['selected'] = false;
        }
        $context['elga_albums'] = $albums;
    }
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

    $loader = require_once EXTDIR . '/elga_lib/vendor/autoload.php';
    // $loader->add('Elga', dirname(__DIR__));
    
    // Simple validation (max file size 2MB and only two allowed mime types)
    $validator = new FileUpload\Validator\Simple(1024 * 1024 * 2, ['image/png', 'image/jpg', 'image/gif', 'image/jpeg']);

    // Simple path resolver, where uploads will be put
    $pathresolver = new FileUpload\PathResolver\Simple(BOARDDIR . '/files/gallery');

    // The machine's filesystem
    $filesystem = new FileUpload\FileSystem\Simple();

    // FileUploader itself
    $fileupload = new FileUpload\FileUpload($_FILES['image'], $_SERVER);

    // Adding it all together. Note that you can use multiple validators or none at all
    $fileupload->setPathResolver($pathresolver);
    $fileupload->setFileSystem($filesystem);
    $fileupload->addValidator($validator);

    // Doing the deed
    list($files, $headers) = $fileupload->processAll();

    // elga hack
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === 0) {
        if (!empty($files->error))
            $context['errors'][] = $files->error;
    }
    
    return;

    // return $files;

    // Outputting it, for example like this
    foreach($headers as $header => $value) {
      header($header . ': ' . $value);
    }

    echo json_encode(array('files' => $files));
}

function createTempScreenImage($fkey, $idtor = 0) /*, $uploaddir) */
{
    global $modSettings, $user_info;

    # delete screen image
    if (!empty($_POST[$fkey . '_del']) && !empty($idtor)) /* && empty($_SESSION['st_temp_screens']) */
    {
        // unset($_POST[$fkey . '_del']);
        delScreenImage($_POST[$fkey . '_del'], $idtor);
    }

    if (!isset($_FILES[$fkey]))
        return false;

    if (!$modSettings['st_screens_enable'])
        return false;

    $attachID = 'tmp_' . $fkey . '_' . $user_info['id'];
    $destName = $modSettings['st_screens_directory'] . '/' . $attachID;

    if (!empty($_FILES[$fkey]) && $_FILES[$fkey]['error'] === 0) {

        $fname = pathinfo($_FILES[$fkey]['name'], PATHINFO_FILENAME);
        $ext = pathinfo($_FILES[$fkey]['name'], PATHINFO_EXTENSION);

        if (!is_dir($modSettings['st_screens_directory']))
            fatal_error('Директория постеров указана неверно!', false);

        if (!is_uploaded_file($_FILES[$fkey]['tmp_name']))
            fatal_error('Ошибка загрузки файла на сервер. Попробуйте заново закачать файл.', false);

        if (filesize($_FILES[$fkey]['tmp_name']) > $modSettings['st_max_screen_size'])
            fatal_error('Файл превышает максимально допустимый размер!', false);

        if (preg_match('~\\/:\*\?"<>|\\0~', $_FILES[$fkey]['name']))
            fatal_error(ST::h($_FILES[$fkey]['name']) . 'Недопустимые символы в имени постер-файла!', false);

        if (!preg_match('~png|gif|jpg|jpeg~i', $ext))
            fatal_error('Расширение постера должно быть <strong>png, gif, jpg, jpeg</strong>.', false);

        // $fileName = $order . '.' . $ext;
        $fileName = sha1_file($_FILES[$fkey]['tmp_name']) . '.' . $ext;

        $_SESSION['st_temp_screens'][$attachID] = $fileName;

        if (!empty($_FILES[$fkey])) {
            if (!move_uploaded_file($_FILES[$fkey]['tmp_name'], $destName)) {
                unset($_SESSION['st_temp_screens'][$attachID]);
                fatal_error('Ошибка копирования временного файла постера!', false);
            }
        }
    }

    if (!empty($_SESSION['st_temp_screens'])) {
        # clean folder for temporary screens
        $dir = @opendir($modSettings['st_screens_directory']) or fatal_error('Нет доступа к папке скриншотов!', false);
        while ($file = readdir($dir)) {
            if ($file == '.' || $file == '..')
                continue;

            if (preg_match('~^tmp_st_screen\d+_\d+$~', $file) != 0) {
                # Temp file is more than 5 hours old!
                if (filemtime($modSettings['st_screens_directory'] . '/' . $file) < time() - 18000)
                    @unlink($modSettings['st_screens_directory'] . '/' . $file);
                continue;
            }
        }
        closedir($dir);

        # Удаляем сессионный screen
        if (!empty($_POST[$fkey . '_del']) && $_SESSION['st_temp_screens'][$attachID] === $_POST[$fkey . '_del']) {
            unset($_POST[$fkey . '_del']);
            unset($_SESSION['st_temp_screens'][$attachID]);
            @unlink($destName);
        }

        if (isset($_FILES[$fkey]))
            unset($_FILES[$fkey]);

        return true;
    }
}

function saveScreenImage($fkey, $idtor)
{
    global $modSettings, $user_info, $smcFunc;

    if (!$modSettings['st_screens_enable'] || empty($_SESSION['st_temp_screens'])) {
        return false;
    }

    $attachID = 'tmp_' . $fkey . '_' . $user_info['id'];
    $destName = $modSettings['st_screens_directory'] . '/' . $attachID;

    if (!file_exists($destName)) {
        return false;
    }

    $date = date('Y/m/d', time());
    $maxWidth = $modSettings['st_max_width_screen'];
    $maxHeight = $modSettings['st_max_height_screen'];

    // if (empty($fileName))
        // $fileName = $idtor . '.' . $ext;

    $dir = $modSettings['st_screens_directory'] . '/' . $date . '/' . $idtor;
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0777, true))
            fatal_error('Не получается создать директорию ' . $dir);
    }

    $new_name = $_SESSION['st_temp_screens'][$attachID];
    $prev_name = pathinfo($new_name, PATHINFO_FILENAME) . '_thumb.' . pathinfo($new_name, PATHINFO_EXTENSION);

    if (file_exists($dir . '/' . $new_name)) {
        unset($_SESSION['st_temp_screens'][$fkey]);
        return false;
    }

    # Переименовываем временный скрин в настоящий
    rename($destName, $dir . '/' . $new_name);

    # Создаем мини-постер
    ST::thumb(
        $dir . '/' . $new_name,
        $dir . '/' . $prev_name,
        $maxWidth,
        $maxHeight
    );

    $smcFunc['db_insert']('insert',
        '{db_prefix}st_screens',
        [ 'id_torrent' => 'int', 'name' => 'string-255', 'thumb' => 'string-255' ],
        [ $idtor, ($date . '/' . $idtor . '/' . $new_name), ($date . '/' . $idtor . '/' . $prev_name) ],
        [ 'id_torrent' ]
    );

    // $id_new = $smcFunc['db_insert_id']('{db_prefix}st_screens', 'id_torrent');

    unset($_SESSION['st_temp_screens'][$fkey]);
}
