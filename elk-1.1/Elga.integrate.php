<?php

if (!defined('ELK')) {
    die('No access...');
}

class Elga_Integrate
{
    public function pre_dispatch()
    {
        // global $modSettings;
        // $modSettings['require_font-awesome'] = true;
    }

    public static function integrate_actions(&$actions)
    {
        // die(__FUNCTION__);

        $actions['gallery'] = ['Elga_Controller', 'action_index'];
        require_once SUBSDIR.'/Elga.subs.php';
        // loadLanguage('Elga');

        // echo '<pre>';
        // print_r($actions);
        // die;
    }

    public static function integrate_menu_buttons(&$buttons, &$menu_count)
    {
        global $txt, $scripturl, $user_info, $modSettings;

        loadLanguage('Elga');
        // loadCSSFile('elga.css');

        $buttons = elk_array_insert($buttons, 'home', [
            'gallery' => [
                'title' => $txt['elga_title'],
                'href' => $scripturl . '?action=gallery',
                // 'data-icon' => '&#xf03e;',
                'data-icon' => 'elga-icon-menu',
                'show' => $modSettings['elga_enabled'], // true, // allowedTo('admin_forum'),
                'sub_buttons' => [
                    'add_file' => [
                        'title' => $txt['elga_create_file'],
                        'href' => $scripturl . '?action=gallery;sa=add_file',
                        'show' => true,
                    ],
                    'admin' => [
                        'title' => $txt['elga_admin'],
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
    // public static function integrate_current_action(&$current_action)
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
    // public static function integrate_pre_log_stats(&$no_stat_actions)
    // {
        // // Don't track who actions for the gallery
        // if (isset($_REQUEST['action']) && ($_REQUEST['action'] === 'gallery' && isset($_GET['xml'])))
            // $no_stat_actions[] = 'gallery';
    // }

    // integrate_whos_online
    public static function integrate_whos_online($actions)
    {
        global $scripturl, $txt;

        loadLanguage('Elga');

        if (empty($actions) || empty($actions['action'])) {
            $action = $txt['who_unknown'];

            return $action;
        }

        if ('gallery' === $actions['action']) {
            $action = sprintf($txt['who_gallery'], $scripturl . '?action=gallery');
        }

        if (!empty($actions['sa']) && 'gallery' === $actions['action']) {
            switch ($actions['sa']) {
                case 'search':
                    $action = sprintf($txt['who_gallery_search'], $scripturl . '?action=gallery;sa=search');
                break;

                case 'album':
                    if (!isset($actions['id']) || !is_numeric($actions['id'])) {
                        $action = $txt['who_gallery'];
                    } else {
                        $db = database();
                        $req = $db->query('', '
                            SELECT a.id, a.name
                            FROM {db_prefix}elga_albums AS a
                            WHERE a.id = {int:id}
                            LIMIT 1',
                            [
                                'id' => (int) $actions['id'],
                            ]
                        );
                        if (!$db->num_rows($req)) {
                            $action = $txt['who_gallery'];
                        } else {
                            $row = $db->fetch_assoc($req);
                            $action = sprintf($txt['who_gallery_album'],
                                $scripturl . '?action=gallery;sa=album;id='.$row['id'],
                                censorText($row['name'])
                            );
                        }
                        $db->free_result($req);
                    }
                break;

                case 'file':
                    if (!isset($actions['id']) || !is_numeric($actions['id'])) {
                        $action = $txt['who_gallery'];
                    } else {
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
                            $action = sprintf($txt['who_gallery_file'],
                                $scripturl . '?action=gallery;sa=file;id='.$row['id'],
                                censorText($row['title'])
                            );
                        }
                        $db->free_result($req);
                    }
                break;

                default:
                    $action = sprintf($txt['who_gallery'], $scripturl . '?action=gallery');
            }
        }

        if (!empty($action)) {
            return $action;
        }
    }

    // Menu.subs.php - 87
    public static function integrate_admin_areas(&$admin_areas)
    {
        global $txt;

        loadLanguage('Elga');
        // loadLanguage('AdminElga');
        // loadLanguage('HelpElga');

        // $admin_areas['config']['areas']['modsettings']['subsections']['elga'] = [$txt['elga_title']];
        $admin_areas['config']['areas']['addonsettings']['subsections']['elga'] = [$txt['elga_title']];
    }

    public static function integrate_sa_modify_modifications(&$subActions)
    {
        $subActions['elga'] = [
            'dir' => SUBSDIR,
            'file' => 'Elga.integrate.php',
            'controller' => 'Elga_Integrate',
            'function' => 'addon_settings',
            'permission' => 'admin_forum',
        ];
    }

    // self - 143
    public static function addon_settings()
    {
        global $txt, $context, $scripturl, $modSettings, $boardurl;

        $context['valid_elga_files_path'] = is_dir($modSettings['elga_files_path']);
        $context['valid_elga_files_url'] = filter_var($modSettings['elga_files_url'], FILTER_VALIDATE_URL);
        $context['valid_elga_icons_path'] = is_dir($modSettings['elga_icons_path']);
        $context['valid_elga_icons_url'] = filter_var($modSettings['elga_icons_url'], FILTER_VALIDATE_URL);

        $context[$context['admin_menu_name']]['tab_data']['tabs']['elga']['description'] = $txt['elga_settings_desc'];

        // Lets build a settings form
        require_once(SUBSDIR . '/SettingsForm.class.php');

        // Instantiate the form
        $elgaSettings = new Settings_Form();

        // All the options, well at least some of them!
        $config_vars = [
          ['title', 'elga_basic_settings'],
            ['check', 'elga_enabled', 'postinput' => $txt['elga_enabled_desc']],
            [ 'text', 'elga_files_path', 'invalid' => !$context['valid_elga_files_path'], 'label' => $txt['elga_files_path'], 'subtext' => $txt['elga_example'].' '.BOARDDIR.'/elga_files/upload'],
            [ 'text', 'elga_files_url', 'invalid' => !$context['valid_elga_files_url'], 'label' => $txt['elga_files_url'], 'subtext' => $txt['elga_example'].' '.$boardurl.'/elga_files/upload'],
            [ 'text', 'elga_icons_path', 'invalid' => !$context['valid_elga_icons_path'], 'label' => $txt['elga_icons_path'], 'subtext' => $txt['elga_example'].' ' . BOARDDIR.'/elga_files/icons'],
            [ 'text', 'elga_icons_url', 'invalid' => !$context['valid_elga_icons_url'], 'label' => $txt['elga_icons_url'], 'subtext' => $txt['elga_example'].' '.$boardurl.'/elga_files/icons'],
            '',
            [ 'int', 'elga_img_max_width', ],
            [ 'int', 'elga_img_max_height', ],
            [ 'int', 'elga_icon_max_width', ],
            [ 'int', 'elga_icon_max_height', ],
          ['title', 'elga_preview_settings'],
            [ 'int', 'elga_imgthumb_max_width', ],
            [ 'int', 'elga_imgthumb_max_height', ],
            '',
            [ 'int', 'elga_imgpreview_max_width', ],
            [ 'int', 'elga_imgpreview_max_height', ],
          ['title', 'elga_comments_settings'],
            // https://disqus.com/admin/universalcode/
            [ 'check', 'elga_disquz_enable', ],
            [
                'text', 'elga_disquz_embed',
                'label' => 'Embed js',
                'subtext' => $txt['elga_example'] . ' EXAMPLE.disqus.com/embed.js<br>IMPORTANT: Insert only EXAMPLE with your forum shortname!',
            ],
        ];

        // Load the settings to the form class
        $elgaSettings->settings($config_vars);

        // Saving?
        if (isset($_GET['save']))
        {
            checkSession();

            // Some defaults are good to have
            if (empty($_POST['elga_imgthumb_max_width']))
                $_POST['elga_imgthumb_max_width'] = 200;
            if (empty($_POST['elga_imgthumb_max_height']))
                $_POST['elga_imgthumb_max_height'] = 200;

            if (empty($_POST['elga_imgpreview_max_width']))
                $_POST['elga_imgpreview_max_width'] = 500;
            if (empty($_POST['elga_imgpreview_max_height']))
                $_POST['elga_imgpreview_max_height'] = 500;

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
        $context['page_title'] = $txt['elga_title'];
        // $context['settings_title'] = $txt['elga_title'];
        $context['post_url'] = $scripturl . '?action=admin;area=addonsettings;sa=elga;save';

        // if (!empty($modSettings['ююю']))
            // updateSettings(array('ююю' => 'ююю'));

        Settings_Form::prepare_db($config_vars);
    }

    public static function integrate_load_illegal_guest_permissions()
    {
        global $context;

        // Guests shouldn't be able to have any portal specific permissions.
        $context['non_guest_permissions'] = array_merge($context['non_guest_permissions'], [
            // 'elga_manage_albums',
            // 'elga_manage_files',
            'elga_create_albums',
            'elga_edit_albums',
            'elga_delete_albums',
            'elga_edit_files',
            'elga_delete_files',
        ]);
    }

    //  - ManagePermissions.subs.php
    public static function integrate_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups,
        &$hiddenPermissions, &$relabelPermissions)
    {
        global $txt;

        $permissionList['membergroup'] = array_merge($permissionList['membergroup'], [
            // files
            'elga_view_files' => [false, 'elga'],
            'elga_create_files' => [false, 'elga'],
            'elga_edit_files' => [true, 'elga'],
            'elga_delete_files' => [true, 'elga'],
            // albums
            'elga_create_albums' => [false, 'elga'],
            'elga_edit_albums' => [true, 'elga'],
            'elga_delete_albums' => [true, 'elga'],
        ]);

        // $loader = require_once EXTDIR.'/elga_lib/vendor/autoload.php';
        // dump($permissionGroups, $permissionList, $leftPermissionGroups, $hiddenPermissions, $relabelPermissions);
    }
}
