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
        $action = sprintf($txt['st_who_torrents'], $scripturl . '?action=gallery');

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



