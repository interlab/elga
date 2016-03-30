<?php

if (!defined('ELK')) {
    die('No access...');
}

// @todo: comments -> add edit delete

class ElgaController extends Action_Controller
{
    public function __construct()
    {
        $loader = require_once EXTDIR . '/elga_lib/vendor/autoload.php';
    }

    public function action_index()
    {
        global $context, $scripturl, $modSettings;

        $context['page_title'] = 'Галерея - Дом';

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery',
            'name' => 'Галерея',
        ];

        loadCSSFile('elga.css');
        loadTemplate('Elga');

        loadJavascriptFile('elga/elga.js');
        loadJavascriptFile('elga/jscroll-2.3.4/jquery.jscroll.js');

        if ( ! $modSettings['elga_enabled'] ) {
            $context['sub_template'] = 'gallery_off';

            return;
        }

        isAllowedTo('elga_view_files');

        $context['sub_template'] = 'home';

        if (isset($_REQUEST['sa'])) {
            $sa = 'action_'.$_REQUEST['sa'];
            if (method_exists($this, $sa)) {
                $this->$sa();

                return;
            }
        }

        $context['elga_albums'] = ElgaSubs::getAlbums();
        $context['elga_last_files'] = ElgaSubs::getLastFiles(20);
    }

    public function action_ajax()
    {
        global $context, $scripturl, $boardurl, $modSettings;

        Template_Layers::getInstance()->removeAll();
        $context['sub_template'] = 'empty';

        $res = ['status' => 'error', 'result' => []];

        if (empty($_REQUEST['m'])) {
            return ElgaSubs::json_response($res);
        }

        switch ($_REQUEST['m']) {
            case 'loadcats':
                return ElgaSubs::json_response(['status' => 'ok', 'result' => ElgaSubs::getAlbumsSimple()]);
            default:
                return ElgaSubs::json_response($res);
        }
    }

    public function action_show()
    {
        global $modSettings;

        die();
        if (empty($_GET['id'])) {
            header("HTTP/1.0 404 Not Found");
            die('<h1>Not Found</h1>');
        }

        $id = (int) $_GET['id'];
        $file = ElgaSubs::getFile($id);
        if (!$file) {
            header("HTTP/1.0 404 Not Found");
            die('<h1>Not Found</h1>');
        }

        $path = $modSettings['elga_files_path'];
        $fpath = isset($_GET['preview']) ? $path . '/' . $file['preview'] :
            ( isset($_GET['thumb']) ? $path . '/' . $file['thumb'] : $path . '/' . $file['fname'] );
        $fext = pathinfo($fpath, PATHINFO_EXTENSION);

        try {
            $imagine = new Imagine\Imagick\Imagine();
        } catch (\Imagine\Exception\RuntimeException $e) {
            $imagine = new \Imagine\Gd\Imagine();
        }
        $imagine->open($fpath)
           ->show($fext);
        die();
    }

    public function action_managealbums()
    {
        global $context, $scripturl, $boardurl, $modSettings;

        $context['elga_albums'] = ElgaSubs::getAlbums();

        $context['page_title'] = 'Manage albums';
        $context['sub_template'] = 'managealbums';

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=managealbums',
            'name' => 'Manage albums',
        ];

        if (isset($_REQUEST['m'])) {
            switch ($_REQUEST['m']) {
                case 'move':
                    if (ElgaSubs::getAlbum($_REQUEST['id'])) {
                        $context['elga_move_id'] = $_REQUEST['id'];
                    }
                    break;
                case 'moveToPrevSiblingOf':
                case 'moveToNextSiblingOf':
                case 'moveToFirstChildOf':
                case 'moveToLastChildOf':
                    checkSession('get');
                    $ns = ElgaSubs::getNestedSetsManager();
                    if (isset($_REQUEST['id'], $_REQUEST['current']) &&
                        $ns->issetNode($_REQUEST['id']) &&
                        $ns->issetNode($_REQUEST['current'])) {
                            if (call_user_func_array([$ns, $_REQUEST['m']], [$_REQUEST['current'], $_REQUEST['id']])) {
                                $context['elga_flashdata'] = [$_REQUEST['m'], 'success', 'Узел успешно перемещён!'];
                                $context['elga_albums'] = ElgaSubs::getAlbums();
                            } else {
                                $context['elga_flashdata'] = [$_REQUEST['m'], 'error', 'Ошибка! Unknown Error Type. #' . __LINE__];
                            }
                    }
                    break;
                default:
                    die('unknown m');
            }
        }

        // if (isset($_REQUEST['m']) && ElgaSubs::getAlbum($_REQUEST['move'])) {
            // $context['elga_move_id'] = $_REQUEST['move'];
        // }
    }

    public function action_album()
    {
        global $context, $scripturl, $boardurl, $modSettings;

        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $albums = ElgaSubs::getAlbums();
        if (empty($albums[$_GET['id']])) {
            fatal_error('Album not found!', false);
        }
        $context['elga_album'] = $album = $albums[$_GET['id']];

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=album;id='.$album['id'],
            'name' => $album['name'],
        ];

        $context['page_title'] = 'Галерея - '.$album['name'];

        $context['sub_template'] = 'album';

        if (isset($_GET['type']) && $_GET['type'] === 'js') {
            // Clear the templates
            Template_Layers::getInstance()->removeAll();
            $context['sub_template'] = 'album_js';
            // sleep(1);
            // $context['json_data'] = [];
            // loadTemplate('Json');
            // $context['sub_template'] = 'send_json';
        }

        // $limit = 20;
        $per_page = 20;

        $totalfiles = ElgaSubs::countFiles($album['id']);
        if (!$totalfiles) {
            return;
        }

        $context['elga_total'] = $totalfiles;
        $context['elga_per_page'] = $per_page;
        $context['elga_is_next_start'] = intval($_REQUEST['start']) + $per_page < $totalfiles;
        $context['page_index'] = constructPageIndex(
            $scripturl.'?action=gallery;sa=album;id='.$album['id'].';start=%1$d',
            $_REQUEST['start'],
            $totalfiles,
            $per_page,
            true
        );
        $context['start'] = $_REQUEST['start'];
        $context['elga_next_start'] = $context['start'] + $per_page;
        $context['page_info'] = [
            'current_page' => $_REQUEST['start'] / $per_page + 1,
            'num_pages' => floor(($totalfiles - 1) / $per_page) + 1,
        ];

        $context['elga_files'] = ElgaSubs::getFiles($album['id'], $context['start'], $per_page);
    }

    public function action_add_album()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        $txt['cannot_elga_create_albums'] = 'Вы не можете создавать альбомы! Не хватает прав!';
        isAllowedTo('elga_create_albums');

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $context['sub_template'] = 'add_album';
        $context['elga_sa'] = 'add_album';
        $context['page_title'] = 'New album';
        $context['elga_id'] = 0;
        $context['elga_albums'] = ElgaSubs::getAlbums();

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=add_album',
            'name' => 'New album',
        ];

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('add_album');
            spamProtection('add_album');

            $context['errors'] = [];
            loadLanguage('Errors');

            // Could they get the right send topic verification code?
            require_once SUBSDIR.'/VerificationControls.class.php';

            // form validation
            require_once SUBSDIR.'/DataValidator.class.php';
            $validator = new Data_Validator();
            $validator->sanitation_rules([
                'location' => 'int',
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'location' => 'required|numeric',
                'album' => 'required|numeric',
                'title' => 'required',
                'descr' => 'required',
            ]);
            $validator->text_replacements([
                'location' => 'Location not selected!',
                'album' => 'Album not selected!',
                'title' => 'Title is empty!',
                'descr' => $txt['error_message'],
            ]);

            // Any form errors
            if (!$validator->validate($_POST)) {
                $context['errors'] = $validator->validation_errors();
            }

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'add_album',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification'])) {
                    foreach ($context['require_verification'] as $error) {
                        $context['errors'][] = $txt['error_'.$error];
                    }
                }
            }

            $icon = 0;
            if ('' !== $_FILES['icon']['name']) {
                $icon = ElgaSubs::uploadIcon(); // @todo
            }

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            if (empty($context['errors'])) {
                $db = database();

                $db->insert('', '{db_prefix}elga_albums',
                    [ 'name' => 'string', 'icon' => 'string', 'description' => 'string', ],
                    [ $title, ($icon ? $icon : ''), $descr, ],
                    [ ]
                );
                $id = $db->insert_id('{db_prefix}elga_albums', 'id');

                redirectexit('action=gallery;sa=album;id='.$id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;
                $context['elga_id'] = 0;

                ElgaSubs::createChecks('add_album');
            }
        }

        // GET
        ElgaSubs::createChecks('add_album');
    }

    public function action_edit_album()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();
        isAllowedTo('elga_edit_albums');

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = ElgaSubs::getAlbums();
        $context['elga_albums'] = & $albums;
        $context['elga_sa'] = 'edit_album';

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('edit_album');
            spamProtection('edit_album');

            if (empty($_POST['id'])) {
                redirectexit('action=gallery');
            }
            $id = ElgaSubs::uint($_POST['id']);
            $a = isset($albums[$id]) ? $albums[$id] : false;
            if (!$a) {
                fatal_error('Album not found!', false);
            }
            $context['elga_album'] = & $a;

            // perms
            // $user_info['id'] != $file['id_member'] && 
            if (!allowedTo('moderate_forum') && !allowedTo('admin_forum')) {
                fatal_error('Вы не можете редактировать этот альбом! Не хватает прав!', false);
            }

            // No errors, yet.
            $context['errors'] = [];
            loadLanguage('Errors');

            // Could they get the right send topic verification code?
            require_once SUBSDIR.'/VerificationControls.class.php';

            // form validation
            require_once SUBSDIR.'/DataValidator.class.php';
            $validator = new Data_Validator();
            $validator->sanitation_rules([
                'location' => 'int',
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'location' => 'required|numeric',
                'album' => 'required|numeric',
                'title' => 'required',
                'descr' => 'required',
            ]);
            $validator->text_replacements([
                'location' => 'Location not selected!',
                'album' => 'Album not selected!',
                'title' => 'Title is empty!',
                'descr' => $txt['error_message'],
            ]);

            // Any form errors
            if (!$validator->validate($_POST)) {
                $context['errors'] = $validator->validation_errors();
            }

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'edit_album',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification'])) {
                    foreach ($context['require_verification'] as $error) {
                        $context['errors'][] = $txt['error_'.$error];
                    }
                }
            }

            if (!isset($albums[$_POST['album']])) {
                $context['errors'][] = 'Album not exists!';
            }

            $icon = 0;
            if ('' !== $_FILES['icon']['name']) {
                $icon = ElgaSubs::uploadIcon(); // @todo
            }

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            // No errors, then send the PM to the admins
            if (empty($context['errors'])) {
                $db = database();
                $db->query('', '
                    UPDATE {db_prefix}elga_albums
                    SET 
                        name = {string:name},'.($icon ? '
                        icon = {string:icon},' : '').'
                        description = {string:descr}
                    WHERE id = {int:id}',
                    [
                        'icon' => $icon ? $icon : '',
                        'name' => $title,
                        'descr' => $descr,
                        'id' => $id,
                    ]
                );

                // del old image
                if ($db->affected_rows() && '' !== $a['icon'] && $a['icon'] !== $icon) {
                    delOldIcon($a);
                }

                redirectexit('action=gallery;sa=album;id='.$id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;
                $context['elga_id'] = $id;

                $context['sub_template'] = 'add_album';
                $atitle = sprintf($txt['edit_album'], $title);

                $context['linktree'][] = [
                    'url' => $scripturl.'?action=gallery;sa=edit_album;id='.$id,
                    'name' => $atitle,
                ];

                $context['page_title'] = $atitle;

                ElgaSubs::createChecks('edit_album');
            }

            return;
        }

        // GET
        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $id = ElgaSubs::uint($_GET['id']);
        $a = ElgaSubs::getAlbum($id);
        if (!$a) {
            fatal_error('Album not found!', false);
        }
        $context['elga_album'] = & $a;

        // perms
        // $user_info['id'] != $file['id_member'] && 
        if (!allowedTo('moderate_forum') && !allowedTo('admin_forum')) {
            fatal_error('Вы не можете редактировать этот альбом! Не хватает прав!', false);
        }

        require_once SUBSDIR.'/Post.subs.php';
        $context['elga_title'] = $a['name']; // @todo: need "title" parse?
        censorText($a['description']);
        $a['description'] = un_preparsecode($a['description']);
        $context['elga_descr'] = $a['description'];

        $context['sub_template'] = 'add_album';
        $atitle = sprintf($txt['edit_album'], $a['name']);

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=edit_album;id='.$id,
            'name' => $atitle,
        ];

        $context['page_title'] = $atitle;

        ElgaSubs::createChecks('edit_album');

        $context['elga_id'] = $id;
    }

    // @TODO
    public function action_remove_album()
    {
        isAllowedTo('elga_delete_albums');
    }

    // @todo: parse bbc ?
    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        $txt['cannot_elga_create_files'] = 'Вы не можете создавать файлы';
        isAllowedTo('elga_create_files');
        //echo allowedTo('elga_create_files');

        // if (!allowedTo('moderate_forum') && !allowedTo('admin_forum'))
            // fatal_error('Не хватает прав!', false);

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = ElgaSubs::getAlbums();
        $context['elga_albums'] = & $albums;

        $context['elga_sa'] = 'add_file';

        $context['sub_template'] = 'add_file';

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=add_file',
            'name' => 'New File',
        ];

        $context['page_title'] = 'New File';

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('add_file');
            spamProtection('add_file');

            // No errors, yet.
            $context['errors'] = [];
            loadLanguage('Errors');

            // Could they get the right send topic verification code?
            require_once SUBSDIR.'/VerificationControls.class.php';
            // require_once(SUBSDIR . '/Members.subs.php');

            // form validation
            require_once SUBSDIR.'/DataValidator.class.php';
            $validator = new Data_Validator();
            $validator->sanitation_rules([
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'album' => 'required|numeric',
                'title' => 'required', // |valid_email',
                'descr' => 'required',
            ]);
            $validator->text_replacements([
                'album' => 'Album not selected!',
                'title' => 'Title is empty!',
                'descr' => $txt['error_message'],
            ]);

            // Any form errors
            if (!$validator->validate($_POST)) {
                $context['errors'] = $validator->validation_errors();
            }

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'add_file',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification'])) {
                    foreach ($context['require_verification'] as $error) {
                        $context['errors'][] = $txt['error_'.$error];
                    }
                }
            }

            if (!isset($albums[$_POST['album']])) {
                $context['errors'][] = 'Album not exists!';
            }

            $img = ElgaSubs::uploadImage();

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            // No errors, then send the PM to the admins
            if (empty($context['errors'])) {
                $db = database();

                // dump($img);
                // die();

                $db->insert('', '{db_prefix}elga_files',
                    [ 'orig_name' => 'string', 'fname' => 'string', 'fsize' => 'raw', 'thumb' => 'string', 
                      'preview' => 'string', 'id_album' => 'int',
                      'title' => 'string', 'description' => 'string', 'id_member' => 'int', 'member_name' => 'string',
                      'time_added' => 'int', 'exif' => 'string', ],
                    [ $img['orig_name'], $img['name'], $img['size'], $img['thumb'], $img['preview'], $validator->album,
                      $title, $descr, $user_info['id'], $user_info['name'], time(), '', ],
                    [ 'id_member', 'id_topic' ]
                );
                $insert_id = $db->insert_id('{db_prefix}elga_files', 'id');

                redirectexit('action=gallery;sa=file;id='.$insert_id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;

                ElgaSubs::createChecks('add_file');

                return;
            }
        }

        ElgaSubs::createChecks('add_file');

        $context['elga_album'] = isset($_GET['album']) ? (int) $_GET['album'] : 0;
    }

    // @todo: parse bbc ?
    public function action_edit_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();
        isAllowedTo('elga_edit_files');

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = ElgaSubs::getAlbums();
        $context['elga_albums'] = & $albums;
        $context['elga_sa'] = 'edit_file';

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('edit_file');
            spamProtection('edit_file');

            if (empty($_POST['id'])) {
                redirectexit('action=gallery');
            }
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
            if ($user_info['id'] != $file['id_member'] && !allowedTo('moderate_forum') && !allowedTo('admin_forum')) {
                fatal_error('Вы не можете редактировать эту запись! Не хватает прав!', false);
            }

            // No errors, yet.
            $context['errors'] = [];
            loadLanguage('Errors');

            // Could they get the right send topic verification code?
            require_once SUBSDIR.'/VerificationControls.class.php';

            // form validation
            require_once SUBSDIR.'/DataValidator.class.php';
            $validator = new Data_Validator();
            $validator->sanitation_rules([
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'album' => 'required|numeric',
                'title' => 'required',
                'descr' => 'required',
            ]);
            $validator->text_replacements([
                'album' => 'Album not selected!',
                'title' => 'Title is empty!',
                'descr' => $txt['error_message'],
            ]);

            // Any form errors
            if (!$validator->validate($_POST)) {
                $context['errors'] = $validator->validation_errors();
            }

            if ($context['require_verification']) {
                // How about any verification errors
                $verificationOptions = [
                    'id' => 'edit_file',
                ];
                $context['require_verification'] = create_control_verification($verificationOptions, true);

                if (is_array($context['require_verification'])) {
                    foreach ($context['require_verification'] as $error) {
                        $context['errors'][] = $txt['error_'.$error];
                    }
                }
            }

            if (!isset($albums[$_POST['album']])) {
                $context['errors'][] = 'Album not exists!';
            }

            $img = 0;
            if ('' !== $_FILES['image']['name']) {
                $img = ElgaSubs::uploadImage();
            }

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            // No errors, then send the PM to the admins
            if (empty($context['errors'])) {
                $db->query('', '
                    UPDATE {db_prefix}elga_files
                    SET '.($img ? '
                        orig_name = {string:oname},
                        fname = {string:fname},
                        fsize = {raw:fsize},
                        thumb = {string:thumb},
                        preview = {string:preview},' : '').'
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
                        'thumb' => $img ? $img['thumb'] : '',
                        'preview' => $img ? $img['preview'] : '',
                        'album' => $validator->album,
                        'title' => $title,
                        'descr' => $descr,
                        'mem_id' => $user_info['id'],
                        'mem_name' => $user_info['name'],
                        'id' => $id,
                    ]
                );

                // del old image
                if ($db->affected_rows() && '' !== $file['fname'] && $img && $file['fname'] !== $img['name']) {
                    ElgaSubs::delOldImage($file);
                }

                redirectexit('action=gallery;sa=file;id='.$id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;

                $context['sub_template'] = 'add_file';

                $context['linktree'][] = [
                    'url' => $scripturl.'?action=gallery;sa=edit_file;id='.$file['id'],
                    'name' => 'Edit '.$title,
                ];

                $context['page_title'] = 'Edit '.$title;

                ElgaSubs::createChecks('edit_file');
            }

            return;
        }

        // GET
        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

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
        if ($user_info['id'] != $file['id_member'] && !allowedTo('moderate_forum') && !allowedTo('admin_forum')) {
            fatal_error('Вы не можете редактировать эту запись! Не хватает прав!', false);
        }

        require_once SUBSDIR.'/Post.subs.php';
        $context['elga_title'] = $file['title']; // @todo: need "title" parse?
        censorText($file['description']);
        $file['description'] = un_preparsecode($file['description']);
        $context['elga_descr'] = $file['description'];

        $context['sub_template'] = 'add_file';

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=edit_file;id='.$file['id'],
            'name' => 'Edit '.$file['title'],
        ];

        $context['page_title'] = 'Edit '.$file['title'];

        ElgaSubs::createChecks('edit_file');

        $context['elga_album'] = (int) $file['id_album'];
    }

    public function action_remove_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();
        isAllowedTo('elga_delete_files');

        checkSession('get');

        if (!is_numeric($_GET['id'])) {
            fatal_error('Bad id value. Required int type.', false);
        }

        $id = $_REQUEST['id'] = ElgaSubs::uint($_GET['id']);

        $file = ElgaSubs::getFile($id);
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

        $dir = $modSettings['elga_files_path'];
        $img = $dir.'/'.$file['fname'];
        $thumb = $dir.'/'.$file['thumb'];
        foreach ([$img, $thumb] as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }

        redirectexit('action=gallery');
    }

    public function action_file()
    {
        global $context, $scripturl, $boardurl, $user_info, $modSettings;

        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $id = (int) $_GET['id'];

        /*
        $pn = '';
        if (isset($_GET['prev_next'])) {
            $pn = $_GET['prev_next'] === 'prev' ? '<' : '>';
        }
        */

        $file = ElgaSubs::getFile($id);

        $file['prev_id'] = ElgaSubs::getPrevId($file['id'], $file['id_album']);
        $file['next_id'] = ElgaSubs::getNextId($file['id'], $file['id_album']);

        if (!$file) {
            fatal_error('File not found.', false);
        }
        $url = $modSettings['elga_files_url'];
        $context['elga_file'] = & $file;

        require_once SUBSDIR.'/Post.subs.php';
        censorText($file['description']);

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=album;id='.$file['id_album'],
            'name' => $file['album_name'],
        ];

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=file;id='.$file['id'],
            'name' => $file['title'],
        ];

        $context['page_title'] = ''.$file['title'];

        $context['sub_template'] = 'file';

        $context['elga_is_author'] = $user_info['id'] == $file['id_member'] || allowedTo('moderate_forum') || allowedTo('admin_forum');
    }

    // delete this function in production
    public function action_reloadthumbs()
    {
        global $user_info, $modSettings;
        // global $context, $scripturl, $boardurl;

        if ( ! $user_info['is_admin'] ) {
            redirectexit('action=gallery');
        }

        die; // delete this line if you know what you're doing
        $fp = $modSettings['elga_files_path'];

        foreach (ElgaSubs::getFilesIterator(0, 0, 1000) as $row) {
            $dir = pathinfo($row['fname'])['dirname'];

            // create thumb image
            $thumb_name = pathinfo($row['fname'], PATHINFO_FILENAME).'_thumb.'.pathinfo($row['fname'], PATHINFO_EXTENSION);
            $width = empty($modSettings['elga_imgthumb_max_width']) ? 350 : $modSettings['elga_imgthumb_max_width'];
            $height = empty($modSettings['elga_imgthumb_max_height']) ? 350 : $modSettings['elga_imgthumb_max_height'];
            $o = ElgaSubs::thumb(
                $fp . '/' . $row['fname'],
                $fp . '/' . $dir . '/' . $thumb_name,
                $width,
                $height
            );

            // create preview image
            $preview_name = pathinfo($row['fname'], PATHINFO_FILENAME).'_preview.'.pathinfo($row['fname'], PATHINFO_EXTENSION);
            $width = empty($modSettings['elga_imgpreview_max_width']) ? 350 : $modSettings['elga_imgpreview_max_width'];
            $height = empty($modSettings['elga_imgpreview_max_height']) ? 350 : $modSettings['elga_imgpreview_max_height'];
            $o2 = ElgaSubs::thumb(
                $fp . '/' . $row['fname'],
                $fp . '/' . $dir . '/' . $preview_name,
                $width,
                $height
            );

            if ( !empty($o) && !empty($o2) ) {
                $db = database();
                $db->query('', '
                    UPDATE {db_prefix}elga_files
                    SET
                        thumb = {string:thumb},
                        preview = {string:preview}
                    WHERE id = {int:id}',
                    [
                        'thumb' => $dir . '/' . $thumb_name,
                        'preview' => $dir . '/' . $preview_name,
                        'id' => $row['id'],
                    ]
                );
            }
        }

        redirectexit('action=gallery');
    }
}
