<?php

if (!defined('ELK'))
	die('No access...');

// integrate_actions
function elga_actions(&$actions, &$adminActions)
{
	$actions['gallery'] = ['Elga.controller.php', 'Elga_Controller', 'action_index'];
}

// integrate_menu_buttons
function elga_menu_buttons(&$buttons, &$menu_count)
{
    global $txt, $scripturl, $user_info;

    $buttons = elk_array_insert($buttons, 'home', [
        'gallery' => [
            'title' => '<i class="fa fa-camera-retro fa-lg"></i> Gallery',
            'href' => $scripturl . '?action=gallery',
            'data-icon' => '&#xf03e;',
            'show' => true, // allowedTo('admin_forum'),
            'sub_buttons' => [
                'add_file' => [
                    'title' => 'Add file',
                    'href' => $scripturl . '?action=gallery;sa=add_file',
                    'show' => true,
                ],
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
		'file' => 'Elga.subs.php',
		'function' => 'elga_addon_settings',
		'permission' => 'admin_forum',
	];
}

function elga_addon_settings()
{
	global $txt, $context, $scripturl, $modSettings;

	// loadlanguage('Elga');
    $txt['elga_title'] = 'Gallery Settings';
    $txt['elga_desc'] = 'This addon adds a images gallery.';
    $txt['elga_enabled'] = 'Enable Gallery';
    $txt['elga_enabled_desc'] = '';
    $txt['elga_max_width_img'] = 'Максимальная ширина изображения';
    $txt['elga_max_height_img'] = 'Максимальная высота изображения';
    
	$context[$context['admin_menu_name']]['tab_data']['tabs']['elga']['description'] = $txt['elga_desc'];

	// Lets build a settings form
	require_once(SUBSDIR . '/SettingsForm.class.php');

	// Instantiate the form
	$elgaSettings = new Settings_Form();

	// All the options, well at least some of them!
	$config_vars = [
		['check', 'elga_enabled', 'postinput' => $txt['fancybox_enabled_desc']],
        [ 'int', 'elga_max_width_img', ],
        [ 'int', 'elga_max_height_img', ],
	];

	// Load the settings to the form class
	$elgaSettings->settings($config_vars);

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		// Some defaults are good to have
		if (empty($_POST['elga_max_width_img']))
			$_POST['elga_max_width_img'] = 350;
		if (empty($_POST['elga_max_height_img']))
			$_POST['elga_max_height_img'] = 350;

		Settings_Form::save_db($config_vars);
		redirectexit('action=admin;area=addonsettings;sa=elga');
	}

	// Continue on to the settings template
	$context['page_title'] = $context['settings_title'] = $txt['elga_title'];
	$context['post_url'] = $scripturl . '?action=admin;area=addonsettings;sa=elga;save';

	// if (!empty($modSettings['ююю']))
		// updateSettings(array('ююю' => 'ююю'));

	Settings_Form::prepare_db($config_vars);
}



