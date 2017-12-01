<?php

if (!defined('ELK')) {
    die('No access...');
}

// @todo: comments -> add edit delete

class Elga_Controller extends Action_Controller
{
    public function __construct()
    {
        $loader = require_once EXTDIR . '/elga_lib/vendor/autoload.php';
    }

    public function action_index()
    {
        global $txt, $context, $scripturl, $modSettings;

        $context['page_title'] = $txt['elga_home'];

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery',
            'name' => $txt['elga_gallery'],
        ];

        loadCSSFile('elga.css');
        loadTemplate('Elga');

        loadJavascriptFile('elga/elga.js');
        loadJavascriptFile('elga/jscroll-2.3.4/jquery.jscroll.js');

        if ( ! $modSettings['elga_enabled'] ) {
            $context['sub_template'] = 'gallery_off';

            return;
        }

        // Actions here
        require_once(SUBSDIR . '/Action.class.php');
        // All we know
        $subActions = [
            'home' => [ $this, 'action_home', 'permission' => 'elga_view_files' ],
            'ajax' => [ $this, 'action_ajax', 'permission' => 'elga_view_files' ],
            'search' => [ $this, 'action_search', 'permission' => 'elga_view_files' ],
            'show' => [ $this, 'action_show', 'permission' => 'elga_view_files' ],
            'browse' => [ $this, 'action_browse', 'permission' => 'elga_view_files' ],

            'album' => [ $this, 'action_album', 'permission' => 'elga_view_files' ],
            'add_album' => [ $this, 'action_add_album', 'permission' => 'elga_create_albums' ],
            'edit_album' => [ $this, 'action_edit_album', 'permission' => 'elga_edit_albums' ],
            'managealbums' => [ $this, 'action_managealbums', 'permission' => 'elga_edit_albums' ],
            'remove_album' => [ $this, 'action_remove_album', 'permission' => 'elga_delete_albums' ],

            'file' => [ $this, 'action_file', 'permission' => 'elga_view_files' ],
            'add_file' => [ $this, 'action_add_file', 'permission' => 'elga_create_files' ],
            'edit_file' => [ $this, 'action_edit_file', 'permission' => 'elga_edit_files_own' ],
            'remove_file' => [ $this, 'action_remove_file', 'permission' => 'elga_delete_files_own' ],
            'reloadthumbs' => [ $this, 'action_reloadthumbs', 'permission' => 'admin_forum' ],
        ];
        // Your bookmark activity will end here if you don't have permission.
        $action = new Action();
        // Default to sub-action 'main' if they have asked for somethign odd
        $subAction = $action->initialize($subActions, 'home');
        $context['sub_action'] = $subAction;
        // Call the right function
        $action->dispatch($subAction);
    }

    public function action_home()
    {
        global $context;

        $context['sub_template'] = 'home';
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

    public function action_search()
    {
        global $context, $scripturl, $txt;

        $context['sub_template'] = 'search';

        $context['elga_albums'] = ElgaSubs::getAlbums();
        
        // dump($context['elga_albums']);
        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=search',
            'name' => 'Поиск',
        ];
    }

    public function action_show()
    {
        global $modSettings;

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

        if (isset($_GET['mode'])) {
            switch ($_GET['mode']) {
                case 'preview':
                    $fpath = $path . '/' . $file['preview'];
                break;

                case 'thumb':
                    $fpath = $path . '/' . $file['thumb'];
                break;

                case 'download':
                    header('Content-disposition: attachment; filename=' . $file['orig_name']);
                    header('Content-type: application/octet-stream');
                    readfile($path . '/' . $file['fname']);
                    exit(0);
                    break;

                case 'exif':
                    $fpath = $path . '/' . $file['fname'];
                    $fext = pathinfo($fpath, PATHINFO_EXTENSION);

                    /*
                    try {
                        $image = new \Imagine\Imagick\Imagine();
                    } catch (\Imagine\Exception\RuntimeException $e) {
                        $image = new \Imagine\Gd\Imagine();
                    }

                    $image = $image
                        ->setMetadataReader(new \Imagine\Image\Metadata\ExifMetadataReader())
                        ->open($fpath);
                    $exif = $image->metadata()->toArray();
                    unset($exif['filepath']);
                    unset($exif['uri']);
                    // dump($exif);
                    */

                    # http://php.net/manual/ru/function.exif-read-data.php
                    // $exif = exif_read_data($fpath, 'IFD0');
                    // if ($exif === false) {
                        // echo "Не найдено данных заголовка.<br />";
                    // } else {
                        // echo "Изображение содержит заголовки<br />";
                    // }
                    // dump($exif);

                    // echo $fpath;

                    $arr = ElgaSubs::cameraUsed($fpath);
                    // dump($arr);
                    if ($arr) {
                        foreach ($arr as $key => $val) {
                            echo htmlspecialchars($key), ': ', htmlspecialchars($val), "<br />\n";
                        }
                    }

                    die;

                    $exif = exif_read_data($fpath, 0, true);
                    if ($exif) {
                        foreach ($exif as $key => $section) {
                            foreach ($section as $name => $val) {
                                echo "$key.$name: $val<br />\n";
                            }
                        }
                    }

                    // dump('<script>alert("XSS!")</script>');

                    die;

                    $exif = json_encode($exif);
                    die;

                break;

                default:
                    $fpath = $path . '/' . $file['fname'];
                    ElgaSubs::updateFile($id, 'views = views + 1');
            }
        } else {
            $fpath = $path . '/' . $file['fname'];
        }

        // $fpath = isset($_GET['preview']) ? $path . '/' . $file['preview'] :
            // ( isset($_GET['thumb']) ? $path . '/' . $file['thumb'] : $path . '/' . $file['fname'] );
        $fext = pathinfo($fpath, PATHINFO_EXTENSION);

        try {
            $imagine = new \Imagine\Imagick\Imagine();
        } catch (\Imagine\Exception\RuntimeException $e) {
            $imagine = new \Imagine\Gd\Imagine();
        }
        $imagine->open($fpath)
           ->show($fext);
        die();
    }

    public function action_browse()
    {
        global $context, $scripturl, $boardurl, $modSettings, $txt, $user_info;

        is_not_guest();

        $context['elga_sort'] = $sort = ( empty($_GET['sort']) ? '' : $_GET['sort'] );

        $id_album = isset($_GET['album']) ? (int) $_GET['album'] : 0;
        $id_user = isset($_GET['user']) ? (int) $_GET['user'] : 0;

        $context['page_title'] = 'Browse files';
        $url = $scripturl.'?action=gallery;sa=browse';

        $context['linktree'][] = [
            'url' => $url,
            'name' => 'Browse files',
        ];

        $context['sub_template'] = 'browse';

        $url_js = '';
        if (isset($_GET['type']) && $_GET['type'] === 'js') {
            Template_Layers::getInstance()->removeAll();
            $context['sub_template'] = 'browse_js';
        }

        $per_page = 20;

        $totalfiles = ElgaSubs::countFiles(['album' => $id_album, 'user' => $id_user,]);
        if (!$totalfiles) {
            return;
        }

        $url .= $id_album ? ';album=' . $id_album : '';
        $url .= $id_user ? ';user=' . $id_album : '';
        $url .= $sort ? ';sort=' . $sort : '';
        
        $context['elga_total'] = $totalfiles;
        $context['elga_per_page'] = $per_page;
        $context['elga_is_next_start'] = intval($_REQUEST['start']) + $per_page < $totalfiles;
        $context['page_index'] = constructPageIndex(
            $url . ';start=%1$d',
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

        $context['elga_url_js'] = $url . ';type=js';
        $context['elga_files'] = ElgaSubs::getFiles($context['start'], $per_page, ['sort' => $sort, 'album' => $id_album, 'user' => $id_user,]);
    }

    public function action_album()
    {
        global $context, $scripturl, $boardurl, $modSettings, $txt;

        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $context['elga_sort'] = $sort = ( empty($_GET['sort']) ? '' : $_GET['sort'] );
        $context['elga_req_user'] = $id_user = isset($_REQUEST['user']) ? (int) $_REQUEST['user'] : 0;

        $album = ElgaSubs::getAlbum($_GET['id'], true);
        if (empty($album)) {
            fatal_error('Album not found!', false);
        }
        $context['elga_album'] = $album;

        ElgaSubs::loadAlbumsLinkTree($album['id'], true);

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=album;id='.$album['id'],
            'name' => $album['name'],
        ];

        $context['page_title'] = sprintf($txt['elga_galleryfmt'], $album['name']);

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

        $totalfiles = ElgaSubs::countFiles([ 'album' => $album['id'], 'user' => $id_user, ]);
        if (!$totalfiles) {
            return;
        }

        $context['elga_total'] = $totalfiles;
        $context['elga_per_page'] = $per_page;
        $context['elga_is_next_start'] = intval($_REQUEST['start']) + $per_page < $totalfiles;
        $context['page_index'] = constructPageIndex(
            $scripturl.'?action=gallery;sa=album;id='.$album['id'].($id_user ? ';user=' . $id_user : '') . ';start=%1$d',
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

        $context['elga_files'] = ElgaSubs::getFiles($context['start'], $per_page, ['sort' => $sort, 'album' => $album['id'], 'user' => $id_user, ]);
    }

    public function action_add_album()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $context['sub_template'] = 'add_album';
        $context['elga_sa'] = 'add_album';
        $context['elga_form_dest'] = $scripturl . '?action=gallery;sa=' . $context['elga_sa'];
        $context['elga_contr'] = 'add';
        $context['page_title'] = $txt['elga_new_album'];
        $context['elga_id'] = 0;
        $context['elga_albums'] = ElgaSubs::getAlbums();
        $context['elga_albums2'] = & $context['elga_albums'];

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=add_album',
            'name' => $txt['elga_new_album'],
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
                'location' => 'string',
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'location' => 'required|alpha',
                'album' => 'required|numeric',
                'title' => 'required',
                'descr' => 'required',
            ]);
            $validator->text_replacements([
                'location' => 'Location not selected!',
                'album' => $txt['elga_album_not_selected'],
                'title' => $txt['elga_empty_title'],
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

                $lastalbum = ElgaSubs::getNestedSetsManager()->getLastParent();
                if (null === $lastalbum) {
                    $leftkey = 1;
                    $rightkey = 2;
                }
                else {
                    $leftkey = $lastalbum->right + 1;
                    $rightkey = $lastalbum->right + 2;
                }

                $db->insert('', '{db_prefix}elga_albums',
                    [
                        'name' => 'string',
                        'icon_orig_name' => 'string',
                        'icon_name' => 'string',
                        'icon_thumb' => 'string',
                        'icon_fhash' => 'string',
                        'description' => 'string',
                        'leftkey' => 'int',
                        'rightkey' => 'int',
                    ],
                    [
                        $title,
                        ($icon ? $icon['orig_name'] : ''),
                        ($icon ? $icon['name'] : ''),
                        ($icon ? $icon['thumb'] : ''),
                        ($icon ? $icon['fhash'] : ''),
                        $descr,
                        $leftkey,
                        $rightkey,
                    ],
                    []
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

        if (!empty($_REQUEST['album'])) {
            $context['elga_id'] = (int) $_REQUEST['album'];
        }
    }

    public function action_edit_album()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = ElgaSubs::getAlbums();
        $context['elga_albums'] = & $albums;

        $context['errors'] = [];
        loadLanguage('Errors');

        // skip childs
        $id = isset($_REQUEST['id']) ? ElgaSubs::uint($_REQUEST['id']) : 0;
        if ($id) {
            $a = ElgaSubs::getAlbum($id);
            $context['elga_albums2'] = [];
            foreach ($albums as $key => $row) {
                if ($a['leftkey'] < $row['leftkey'] && $a['rightkey'] > $row['rightkey']) {
                    continue;
                }
                $context['elga_albums2'][$key] = $row;
            }
        }

        $context['elga_sa'] = 'edit_album';
        $context['elga_form_dest'] = $scripturl . '?action=gallery;sa=' . $context['elga_sa'];
        $context['elga_contr'] = 'edit';

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

            // Could they get the right send topic verification code?
            require_once SUBSDIR.'/VerificationControls.class.php';

            // form validation
            require_once SUBSDIR.'/DataValidator.class.php';
            $validator = new Data_Validator();
            $validator->sanitation_rules([
                'location' => 'string',
                'id' => 'int',
                'album' => 'int',
                'title' => 'trim|Util::htmlspecialchars',
                'descr' => 'trim|Util::htmlspecialchars',
            ]);
            $validator->validation_rules([
                'location' => 'required|alpha',
                'id' => 'required|numeric',
                'album' => 'required|numeric',
                'title' => 'required',
                // 'descr' => 'required',
            ]);
            $validator->text_replacements([
                'location' => 'Location not selected!',
                'id' => 'Id album not selected!',
                'album' => 'Album field not selected!',
                'title' => 'Title is empty!',
                // 'descr' => $txt['error_message'],
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

            // if (!isset($albums[$_POST['album']])) {
                // $context['errors'][] = 'Album not exists!';
            // }

            $icon = 0;
            if ('' !== $_FILES['icon']['name']) {
                $icon = ElgaSubs::uploadIcon(); // @todo
            }

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            // move album
            ElgaSubs::moveAlbum($validator->location, $validator->id, $validator->album);

            if (empty($context['errors'])) {
                $db = database();
                $db->query('', '
                    UPDATE {db_prefix}elga_albums
                    SET 
                        name = {string:name},'.($icon ? '
                        icon_orig_name = {string:orig_name},
                        icon_name = {string:icon_name},
                        icon_thumb = {string:icon_thumb},
                        icon_fhash = {string:icon_fhash},' : '').'
                        description = {string:descr}
                    WHERE id = {int:id}',
                    [
                        'orig_name' => ($icon ? $icon['orig_name'] : ''),
                        'icon_name' => ($icon ? $icon['name'] : ''),
                        'icon_thumb' => ($icon ? $icon['thumb'] : ''),
                        'icon_fhash' => ($icon ? $icon['fhash'] : ''),
                        'name' => $title,
                        'descr' => $descr,
                        'id' => $id,
                    ]
                );

                // delete old icon
                if ($db->affected_rows() && '' !== $a['icon'] && $a['icon'] !== $icon) {
                    ElgaSubs::delOldIcon($a['icon']);
                }

                redirectexit('action=gallery;sa=album;id='.$id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;
                $context['elga_id'] = $id;

                $context['sub_template'] = 'add_album';
                $atitle = sprintf($txt['elga_edit_album'], $title);

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
        $atitle = sprintf($txt['elga_edit_album'], $a['name']);

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=edit_album;id='.$id,
            'name' => $atitle,
        ];

        $context['page_title'] = $atitle;

        ElgaSubs::createChecks('edit_album');

        $context['elga_id'] = $id;
    }

    public function action_managealbums()
    {
        global $txt, $context, $scripturl, $boardurl, $modSettings;

        $context['elga_albums'] = ElgaSubs::getAlbums();

        $context['page_title'] = $txt['elga_managealbums'];
        $context['sub_template'] = 'managealbums';

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=managealbums',
            'name' => $txt['elga_managealbums'],
        ];

        // move album
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
                        $ns->issetNode($_REQUEST['current'])
                    ) {
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

    // @TODO
    public function action_remove_album()
    {
        isAllowedTo('elga_delete_albums');
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
        if (!$file) {
            fatal_error('File not found.', false);
        }

        $file['prev_id'] = ElgaSubs::getPrevId($id, $file['id_album']);
        $file['next_id'] = ElgaSubs::getNextId($id, $file['id_album']);

        $url = $modSettings['elga_files_url'];
        $context['elga_file'] = & $file;

        require_once SUBSDIR.'/Post.subs.php';
        censorText($file['description']);

        ElgaSubs::loadAlbumsLinkTree($file['id_album'], false, true);

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=file;id='.$id,
            'name' => $file['title'],
        ];

        $context['page_title'] = $file['title'];

        $context['sub_template'] = 'file';

        $context['elga_is_author'] = $user_info['id'] == $file['id_member'] || allowedTo('moderate_forum') || allowedTo('admin_forum');

        if (empty($_SESSION['elga_lastreadfile']) || $_SESSION['elga_lastreadfile'] != $id) {
            ElgaSubs::updateFile($id, 'views = views + 1');
            $_SESSION['elga_lastreadfile'] = $id;
        }

        // jQuery UI
        $modSettings['jquery_include_ui'] = true;
        //loadCSSFile('jquery.ui.slider.css');
        //loadCSSFile('jquery.ui.theme.css');
    }

    // @todo: parse bbc ?
    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        // is_not_guest();

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

        $context['errors'] = [];
        loadLanguage('Errors');

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('add_file');
            spamProtection('add_file');

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

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            $title = Util::strlen($title) > 100 ? Util::substr($title, 0, 100) : $title;
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            $img = ElgaSubs::createFileImage();

            // No errors, then send the PM to the admins
            if (empty($context['errors'])) {
                $db = database();

                // dump($img);
                // die();

                $db->insert('', '{db_prefix}elga_files',
                    [ 'orig_name' => 'string', 'fname' => 'string', 'fsize' => 'raw', 'fhash' => 'string',
                      'thumb' => 'string', 'preview' => 'string', 'id_album' => 'int',
                      'title' => 'string', 'description' => 'string', 'id_member' => 'int', 'member_name' => 'string',
                      'time_added' => 'int', 'exif' => 'string',
                    ],
                    [ $img['orig_name'], $img['name'], $img['size'], $img['fhash'], $img['thumb'], $img['preview'],
                      $validator->album, $title, $descr, $user_info['id'], $user_info['name'], time(), $img['exif'],
                    ],
                    [ 'id_member', 'id_topic' ]
                );
                $insert_id = $db->insert_id('{db_prefix}elga_files', 'id');

                redirectexit('action=gallery;sa=file;id='.$insert_id);
            }
            // If errors
            else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = un_preparsecode($descr);

                censorText($context['elga_title']);
                censorText($context['elga_descr']);

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

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = ElgaSubs::getAlbums();
        $context['elga_albums'] = & $albums;
        $context['elga_sa'] = 'edit_file';

        $context['errors'] = [];
        loadLanguage('Errors');

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('edit_file');
            spamProtection('edit_file');

            if (empty($_POST['id'])) {
                redirectexit('action=gallery');
            }
            $id = (int) $_POST['id'];

            $file = ElgaSubs::getFile($id);
            if ( ! $file ) {
                fatal_error('File not found!', false);
            }
            $context['elga_file'] =& $file;
            $file['id_member'] = (int) $file['id_member'];

            // Check permissions
            if ($user_info['id'] === $file['id_member']) {
                isAllowedTo('elga_edit_files_own');
            } else {
                isAllowedTo('elga_edit_files_any');
            }

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
                $img = ElgaSubs::createFileImage();
            }

            $title = strtr($validator->title, ["\r" => '', "\n" => '', "\t" => '']);
            require_once SUBSDIR.'/Post.subs.php';
            $descr = $validator->descr;
            preparsecode($descr);

            // No errors, then send the PM to the admins
            if (empty($context['errors'])) {
                $db = database();
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
                        member_name = {string:mem_name},
                        exif = {string:exif}
                    WHERE id = {int:id}',
                    [
                        'oname' => $img ? $img['orig_name'] : '',
                        'fname' => $img ? $img['name'] : '',
                        'fsize' => $img ? $img['size'] : '',
                        'fhash' => $img ? $img['fhash'] : '',
                        'thumb' => $img ? $img['thumb'] : '',
                        'preview' => $img ? $img['preview'] : '',
                        'album' => $validator->album,
                        'title' => $title,
                        'descr' => $descr,
                        'mem_id' => $user_info['id'],
                        'mem_name' => $user_info['name'],
                        'id' => $id,
                        'exif' => $img ? $img['exif'] : '',
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
            'url' => $scripturl.'?action=gallery;sa=file;id='.$file['id'],
            'name' => 'Edit '.$file['title'],
        ];

        $context['page_title'] = 'Edit '.$file['title'];

        ElgaSubs::createChecks('edit_file');

        $context['elga_album'] = (int) $file['id_album'];
    }

    public function action_remove_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        checkSession('get');

        if (!is_numeric($_GET['id'])) {
            fatal_error('Bad id value. Required int type.', false);
        }

        ElgaSubs::removeFile($_GET['id']);

        redirectexit('action=gallery');
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

