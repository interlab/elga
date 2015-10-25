<?php

if (!defined('ELK')) {
    die('No access...');
}

// @todo: comments -> add edit delete

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
            'url' => $scripturl.'?action=gallery',
            'name' => 'Галерея',
        ];

        loadCSSFile('elga.css');
        loadTemplate('Elga');

        loadJavascriptFile('jscroll-2.3.4/jquery.jscroll.js');
        // JavaScriptEscape(...)
        addInlineJavascript('
$(document).ready(function(){
    var elgaimgload = new Image();
    elgaimgload.src = elk_images_url + "/elga_loading.gif";
    // var i = 0;
    // console.log(elgaimgload.src);
    $(\'.elga_scroll\').jscroll({
        loadingHtml: \'<img src="\' + elgaimgload.src + \'" alt="Loading" /> Loading...\',
        padding: 20,
        nextSelector: \'a.jscroll-next:last\',
        contentSelector: \'\',
        // callback: function(){
            // i++;
            // console.log(i + \'test jscroll\')
        // }
    });
});
        ');

        $context['sub_template'] = 'home';

        if (isset($_REQUEST['sa'])) {
            $sa = 'action_'.$_REQUEST['sa'];
            if (method_exists($this, $sa)) {
                $this->$sa();
            }
        }

        $context['elga_albums'] = getAlbums();
    }

    public function action_album()
    {
        global $context, $scripturl, $boardurl;

        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $albums = getAlbums();
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
        } else {
            $totalfiles = $db->fetch_row($req)[0];
        }
        $db->free_result($req);

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

        $req = $db->query('', '
            SELECT f.id, f.orig_name, f.fname, f.thumb, f.fsize, f.title, f.description, f.views, f.id_member, f.member_name
            FROM {db_prefix}elga_files as f
            WHERE f.id_album = {int:album}
            ORDER BY f.id DESC
            LIMIT {int:start}, {int:per_page}',
            [
                'album' => $album['id'],
                'start' => $context['start'],
                'per_page' => $per_page,
            ]
        );

        $url = $modSettings['elga_icons_url'];
        $context['elga_files'] = [];
        if ($db->num_rows($req) > 0) {
            while ($row = $db->fetch_assoc($req)) {
                $row['thumb'] = $url.'/'.$row['thumb'];
                $row['icon'] = $url.'/'.$row['fname'];
                $context['elga_files'][$row['id']] = $row;
            }
        }
        $db->free_result($req);
    }

    public function action_add_album()
    {
        // @todo
    }

    public function action_edit_album()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = getAlbums();
        $context['elga_albums'] = & $albums;
        $context['elga_sa'] = 'edit_album';

        if (isset($_REQUEST['send'])) {
            checkSession('post');
            validateToken('edit_album');
            spamProtection('edit_album');

            if (empty($_POST['id'])) {
                redirectexit('action=gallery');
            }
            $id = _uint($_POST['id']);
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
            if ('' !== $_FILES['image']['name']) {
                $icon = uploadIcon(); // @todo
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
                if ($db->affected_rows() && '' !== $a['icon'] && $img && $a['icon'] !== $icon) {
                    delOldIcon($a);
                }

                redirectexit('action=gallery;sa=album;id='.$id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;
                $context['elga_id'] = $id;

                $context['sub_template'] = 'add_album';

                $context['linktree'][] = [
                    'url' => $scripturl.'?action=gallery;sa=edit_album;id='.$id,
                    'name' => 'Edit album '.$title,
                ];

                $context['page_title'] = 'Edit album '.$title;

                _createChecks('edit_album');
            }

            return;
        }

        // GET
        if (empty($_GET['id'])) {
            redirectexit('action=gallery');
        }

        $id = _uint($_GET['id']);
        $a = getAlbum($id);
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

        $context['linktree'][] = [
            'url' => $scripturl.'?action=gallery;sa=edit_album;id='.$id,
            'name' => 'Edit album '.$a['name'],
        ];

        $context['page_title'] = 'Edit album '.$a['name'];

        _createChecks('edit_album');

        $context['elga_id'] = $id;
    }

    public function action_remove_album()
    {
        
    }

    // @todo: parse bbc ?
    public function action_add_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        // if (!allowedTo('moderate_forum') && !allowedTo('admin_forum'))
            // fatal_error('Не хватает прав!', false);

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = getAlbums();
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

            $img = uploadImage();

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
                    [ 'orig_name' => 'string', 'fname' => 'string', 'fsize' => 'raw', 'thumb' => 'string', 'id_album' => 'int',
                      'title' => 'string', 'description' => 'string', 'id_member' => 'int', 'member_name' => 'string', 'exif' => 'string', ],
                    [ $img['orig_name'], $img['name'], $img['size'], $img['thumb'], $validator->album, $title,
                      $descr, $user_info['id'], $user_info['name'], '', ],
                    [ 'id_member', 'id_topic' ]
                );
                $insert_id = $db->insert_id('{db_prefix}elga_files', 'id');

                redirectexit('action=gallery;sa=file;id='.$insert_id);
            } else {
                $context['elga_album'] = $validator->album;
                $context['elga_title'] = $title;
                $context['elga_descr'] = $descr;

                _createChecks('add_file');

                return;
            }
        }

        _createChecks('add_file');

        $context['elga_album'] = isset($_GET['album']) ? (int) $_GET['album'] : 0;
    }

    // @todo: parse bbc ?
    public function action_edit_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        $context['require_verification'] = !$user_info['is_mod'] && !$user_info['is_admin'] &&
            !empty($modSettings['posts_require_captcha']) && ($user_info['posts'] < $modSettings['posts_require_captcha'] ||
            ($user_info['is_guest'] && $modSettings['posts_require_captcha'] == -1));

        $albums = getAlbums();
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
                $img = uploadImage();
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
                        thumb = {string:thumb},' : '').'
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
                        'title' => $title,
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

                _createChecks('edit_file');
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

        _createChecks('edit_file');

        $context['elga_album'] = (int) $file['id_album'];
    }

    public function action_remove_file()
    {
        global $context, $txt, $user_info, $modSettings, $scripturl;

        is_not_guest();

        checkSession('get');

        if (!is_numeric($_GET['id'])) {
            fatal_error('Bad id value. Required int type.', false);
        }

        $id = $_REQUEST['id'] = _uint($_GET['id']);

        $file = getFile($id);
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

        $url = $modSettings['elga_files_url'];
        $file = $db->fetch_assoc($req);
        $context['elga_file'] = & $file;
        $db->free_result($req);
        $context['elga_file']['icon'] = $url.'/'.$context['elga_file']['fname'];
        require_once SUBSDIR.'/Post.subs.php';
        censorText($file['description']);
        $file['description'] = parse_bbc($file['description']);

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
}
