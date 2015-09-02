<?php

if (!defined('ELK'))
	die('No access...');

// integrate_actions
function elga_actions(&$actions, &$adminActions)
{
	$actions['gallery'] = ['Elga.controller.php', 'Elga_Controller', 'action_index'],
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



