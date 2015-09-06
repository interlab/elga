<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as ELK\'s index.php.');

$hooks = [
    'integrate_pre_include' => 'SUBSDIR/Elga.integrate.php',
    'integrate_actions' => 'elga_actions',
    'integrate_menu_buttons' => 'elga_menu_buttons',
    'integrate_whos_online' => 'elga_whos_online',
    'integrate_admin_areas' => 'elga_admin_areas',
    'integrate_sa_modify_modifications' => 'elga_sa_modify_modifications',
    'integrate_load_illegal_guest_permissions' => 'elga_load_illegal_guest_permissions',
    'integrate_load_permissions' => 'elga_load_permissions',
];

if (!empty($context['uninstalling']))
	$call = 'remove_integration_function';
else
	$call = 'add_integration_function';

foreach ($hooks as $hook => $function)
{
	$call($hook, $function);
}
