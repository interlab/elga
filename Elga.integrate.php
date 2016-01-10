<?php

if (!defined('ELK')) {
    die('No access...');
}

// integrate_actions
function elga_actions(&$actions, &$adminActions)
{
	$actions['gallery'] = ['Elga.controller.php', 'ElgaController', 'action_index'];
    require_once SUBSDIR.'/Elga.subs.php';
}

// integrate_menu_buttons
function elga_menu_buttons(&$buttons, &$menu_count)
{
    global $txt, $scripturl, $user_info, $modSettings;

    $buttons = elk_array_insert($buttons, 'home', [
        'gallery' => [
            'title' => 'Gallery',
            'href' => $scripturl . '?action=gallery',
            'data-icon' => '&#xf03e;',
            'show' => $modSettings['elga_enabled'], // true, // allowedTo('admin_forum'),
            'sub_buttons' => [
                'add_file' => [
                    'title' => 'Add file',
                    'href' => $scripturl . '?action=gallery;sa=add_file',
                    'show' => true,
                ],
                'admin' => [
                    'title' => 'Admin',
                    'href' => $scripturl.'?action=admin;area=addonsettings;sa=elga',
                    'show' => $user_info['is_admin'],
                ]
                /*
                'search' => [
                    'title' => $txt['search'],
                    'href' => $scripturl . '?action=gallery;sa=search',
                    'show' => $context['allow_search'],
                ],
                */
            ],
        ]
    ], 'after');
}

// integrate_current_action
// function elga_current_action(&$current_action)
// {
    // if ($current_action === 'home')
    // {
        // if (empty($_REQUEST['action']))
            // $current_action = 'base';
    // }
// }

/**
 * Add items to the not stat action array to prevent logging in some cases
 */
// function elga_pre_log_stats(&$no_stat_actions)
// {
	// // Don't track who actions for the gallery
	// if (isset($_REQUEST['action']) && ($_REQUEST['action'] === 'gallery' && isset($_GET['xml'])))
		// $no_stat_actions[] = 'gallery';
// }

// integrate_whos_online
function elga_whos_online($actions)
{
    global $scripturl, $txt;

    if (empty($actions) || empty($actions['action'])) {
        $action = $txt['who_unknown'];

        return $action;
    }

    $txt['who_gallery'] = 'Просматривает <a href="%s">галерею</a>';
    $txt['who_gallery_search'] = 'Выполняет поиск в <a href="%s">галерее</a>';
    $txt['who_gallery_file'] = 'Просматривает файл <a href="%s">%s</a>';

    if ('gallery' === $actions['action'])
        $action = sprintf($txt['who_gallery'], $scripturl . '?action=gallery');

    if (!empty($actions['sa']) and 'gallery' === $actions['action']) {
        switch ($actions['sa']) {
            case 'search':
                $action = sprintf($txt['who_gallery_search'], $scripturl . '?action=gallery;sa=search');
            break;

            // @todo: album
            // case 'album':

            case 'file':
                if (isset($actions['id']) and is_numeric($actions['id'])) {
                    $db = database();
                    $req = $db->query('', '
                        SELECT f.id, f.title
                        FROM {db_prefix}elga_files AS f
                        WHERE f.id = {int:id}
                        LIMIT 1',
                        [
                            'id' => (int) $actions['id'],
                        ]
                    );
                    if (!$db->num_rows($req)) {
                        $action = $txt['who_gallery'];
                    } else {
                        $row = $db->fetch_assoc($req);
                        $action = sprintf($txt['who_gallery_file'], $scripturl . '?action=gallery;sa=file;id='.$row['id'], censorText($row['name']));
                    }
                    $db->free_result($req);
                }
            break;

            default:
                $action = sprintf($txt['who_gallery'], $scripturl . '?action=gallery');
        }
    }

    if (!empty($action))
        return $action; # !important
}

// integrate_admin_areas
// Menu.subs.php - 87
function elga_admin_areas(&$admin_areas)
{
    global $txt;

    // loadLanguage('AdminElga');
    // loadLanguage('HelpElga');
    $txt['gallery_title'] = 'Галерея';

    // $admin_areas['config']['areas']['modsettings']['subsections']['elga'] = [$txt['gallery_title']];
	$admin_areas['config']['areas']['addonsettings']['subsections']['elga'] = [$txt['gallery_title']];
}

// integrate_sa_modify_modifications
function elga_sa_modify_modifications(&$subActions)
{
    $subActions['elga'] = [
		'dir' => SUBSDIR,
		// 'file' => 'Elga.subs.php',
		'function' => 'elga_addon_settings',
		'permission' => 'admin_forum',
	];
}

function elga_addon_settings()
{
	global $txt, $context, $scripturl, $modSettings, $boardurl;

    $context['valid_elga_files_path'] = is_dir($modSettings['elga_files_path']);
    $context['valid_elga_files_url'] = filter_var($modSettings['elga_files_url'], FILTER_VALIDATE_URL);
    $context['valid_elga_icons_path'] = is_dir($modSettings['elga_icons_path']);
    $context['valid_elga_icons_url'] = filter_var($modSettings['elga_icons_url'], FILTER_VALIDATE_URL);

	// loadlanguage('Elga');
    $txt['elga_title'] = 'Gallery Settings';
    $txt['elga_desc'] = 'This addon adds a images gallery.';
    $txt['elga_enabled'] = 'Enable Gallery';
    $txt['elga_enabled_desc'] = '';
    $txt['elga_files_path'] = 'Путь к папке с файлами';
    $txt['elga_files_url'] = 'URL адрес к папке с изображениями';
    $txt['elga_icons_path'] = 'Путь к папке с иконками альбомов';
    $txt['elga_icons_url'] = 'URL адрес к папке с иконками';
    $txt['elga_img_max_width'] = 'Максимальная ширина изображения';
    $txt['elga_img_max_height'] = 'Максимальная высота изображения';
    $txt['elga_icon_max_width'] = 'Максимальная ширина иконки альбома';
    $txt['elga_icon_max_height'] = 'Максимальная высота иконки альбома';
    
	$context[$context['admin_menu_name']]['tab_data']['tabs']['elga']['description'] = $txt['elga_desc'];

	// Lets build a settings form
	require_once(SUBSDIR . '/SettingsForm.class.php');

	// Instantiate the form
	$elgaSettings = new Settings_Form();

	// All the options, well at least some of them!
	$config_vars = [
		['check', 'elga_enabled', 'postinput' => $txt['elga_enabled_desc']],
        [ 'text', 'elga_files_path', 'invalid' => !$context['valid_elga_files_path'], 'label' => $txt['elga_files_path'], 'subtext' => 'Например: ' . BOARDDIR.'/elga_files/upload'],
        [ 'text', 'elga_files_url', 'invalid' => !$context['valid_elga_files_url'], 'label' => $txt['elga_files_url'], 'subtext' => 'Например: ' . $boardurl.'/elga_files/upload'],
        [ 'text', 'elga_icons_path', 'invalid' => !$context['valid_elga_icons_path'], 'label' => $txt['elga_icons_path'], 'subtext' => 'Например: ' . BOARDDIR.'/elga_files/icons'],
        [ 'text', 'elga_icons_url', 'invalid' => !$context['valid_elga_icons_url'], 'label' => $txt['elga_icons_url'], 'subtext' => 'Например: '.$boardurl.'/elga_files/icons'],
        [ 'int', 'elga_img_max_width', ],
        [ 'int', 'elga_img_max_height', ],
        [ 'int', 'elga_icon_max_width', ],
        [ 'int', 'elga_icon_max_height', ],
	];

	// Load the settings to the form class
	$elgaSettings->settings($config_vars);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Some defaults are good to have
		if (empty($_POST['elga_img_max_width']))
			$_POST['elga_img_max_width'] = 350;
		if (empty($_POST['elga_img_max_height']))
			$_POST['elga_img_max_height'] = 350;
		if (empty($_POST['elga_icon_max_width']))
			$_POST['elga_icon_max_width'] = 60;
		if (empty($_POST['elga_icon_max_height']))
			$_POST['elga_icon_max_height'] = 60;

        $_POST['elga_files_path'] = rtrim($_POST['elga_files_path'], '/');
        $_POST['elga_files_url'] = rtrim($_POST['elga_files_url'], '/');
        $_POST['elga_icons_path'] = rtrim($_POST['elga_icons_path'], '/');
        $_POST['elga_icons_url'] = rtrim($_POST['elga_icons_url'], '/');

		Settings_Form::save_db($config_vars);

        if (!is_dir($modSettings['elga_files_path'])) {
            mkdir($_POST['elga_files_path'], 0777, true);
        }

        if (!is_dir($modSettings['elga_icons_path'])) {
            mkdir($_POST['elga_icons_path'], 0777, true);
        }

		redirectexit('action=admin;area=addonsettings;sa=elga');
	}

	// Continue on to the settings template
	$context['page_title'] = $context['settings_title'] = $txt['elga_title'];
	$context['post_url'] = $scripturl . '?action=admin;area=addonsettings;sa=elga;save';

	// if (!empty($modSettings['ююю']))
		// updateSettings(array('ююю' => 'ююю'));

	Settings_Form::prepare_db($config_vars);
}

// integrate_load_illegal_guest_permissions
function elga_load_illegal_guest_permissions()
{
	global $context;

	// Guests shouldn't be able to have any portal specific permissions.
	$context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], [
        'elga_manage_albums',
        'elga_manage_files',
    ]);
}

// integrate_load_permissions - ManagePermissions.subs.php
function elga_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups,
    &$hiddenPermissions, &$relabelPermissions)
{
    global $txt;

    $txt['permissiongroup_elga'] = 'Галерея';

    // $txt['permissionname_elga_manage_albums'] = 'Управлять альбомами';
    // $txt['permissionname_elga_manage_albums_own'] = 'Управлять своими альбомами';
    // $txt['permissionname_elga_manage_albums_any'] = 'Управлять любыми альбомами';
    // $txt['cannot_elga_manage_albums'] = 'Вы не можете управлять альбомами';
    // $txt['cannot_elga_manage_albums_own'] = 'Вы не можете управлять своими альбомами';
    // $txt['cannot_elga_manage_albums_any'] = 'Вы не можете управлять чужими альбомами';

    // $txt['permissionname_elga_manage_files'] = 'Управлять файлами';
    // $txt['permissionname_elga_manage_files_own'] = 'Управлять своими файлами';
    // $txt['permissionname_elga_manage_files_any'] = 'Управлять любыми файлами';
    // $txt['cannot_elga_manage_files'] = 'Доступ запрещён!';
    // $txt['cannot_elga_manage_files_own'] = 'Доступ запрещён!';
    // $txt['cannot_elga_manage_files_any'] = 'Доступ запрещён!';

    // Albums
    $txt['permissionname_elga_create_albums'] = 'Создавать альбомы';
    $txt['cannot_elga_create_albums'] = 'Вы не можете создавать альбомы! Не хватает прав!';

    $txt['permissionname_elga_edit_albums'] = 'Редактировать альбомы';
    $txt['permissionname_elga_edit_albums_own'] = 'Редактировать свои альбомы';
    $txt['permissionname_elga_edit_albums_any'] = 'Редактировать любые альбомы';
    $txt['cannot_elga_edit_albums'] = 'Вы не можете редактировать альбомы';
    $txt['cannot_elga_edit_albums_own'] = 'Вы не можете редактировать свои альбомы';
    $txt['cannot_elga_edit_albums_any'] = 'Вы не можете редактировать чужие альбомы';

    $txt['permissionname_elga_delete_albums'] = 'Удалять альбомы';
    $txt['permissionname_elga_delete_albums_own'] = 'Удалять свои альбомы';
    $txt['permissionname_elga_delete_albums_any'] = 'Удалять любые альбомы';
    $txt['cannot_elga_delete_albums'] = 'Вы не можете удалять альбомы';
    $txt['cannot_elga_delete_albums_own'] = 'Вы не можете удалять свои альбомы';
    $txt['cannot_elga_delete_albums_any'] = 'Вы не можете удалять чужие альбомы';

    // Files
    $txt['permissionname_elga_create_files'] = 'Создавать файлы';
    $txt['cannot_elga_create_files'] = 'Вы не можете создавать файлы! Не хватает прав!';

    $txt['permissionname_elga_edit_files'] = 'Редактировать файлы';
    $txt['permissionname_elga_edit_files_own'] = 'Редактировать свои файлы';
    $txt['permissionname_elga_edit_files_any'] = 'Редактировать любые файлы';
    $txt['cannot_elga_edit_files'] = 'Вы не можете редактировать файлы';
    $txt['cannot_elga_edit_files_own'] = 'Вы не можете редактировать свои файлы';
    $txt['cannot_elga_edit_files_any'] = 'Вы не можете редактировать чужие файлы';

    $txt['permissionname_elga_delete_files'] = 'Удалять файлы';
    $txt['permissionname_elga_delete_files_own'] = 'Удалять свои файлы';
    $txt['permissionname_elga_delete_files_any'] = 'Удалять любые файлы';
    $txt['cannot_elga_delete_files'] = 'Вы не можете удалять файлы';
    $txt['cannot_elga_delete_files_own'] = 'Вы не можете удалять свои файлы';
    $txt['cannot_elga_delete_files_any'] = 'Вы не можете удалять чужие файлы';

    $permissionList['membergroup'] = array_merge($permissionList['membergroup'], [
        // 'elga_manage_albums' => [true, 'elga'],
        // 'elga_manage_files' => [true, 'elga'],
        'elga_create_albums' => [false, 'elga'],
        'elga_edit_albums' => [true, 'elga'],
        'elga_delete_albums' => [true, 'elga'],
        'elga_create_files' => [false, 'elga'],
        'elga_edit_files' => [true, 'elga'],
        'elga_delete_files' => [true, 'elga'],
    ]);

    // $loader = require_once EXTDIR.'/elga_lib/vendor/autoload.php';
    // dump($permissionGroups, $permissionList, $leftPermissionGroups, $hiddenPermissions, $relabelPermissions);
}


