<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_xen define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Xen Vps',
	'description' => 'Allows selling of Xen Server and VPS License Types.  More info at https://www.netenberg.com/xen.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a xen license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-xen-vps',
	'repo' => 'https://github.com/detain/myadmin-xen-vps',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminXen\Plugin', 'Requirements'],
		'vps.settings' => ['Detain\MyAdminXen\Plugin', 'Settings'],
		'vps.activate' => ['Detain\MyAdminXen\Plugin', 'Activate'],
		'vps.change_ip' => ['Detain\MyAdminXen\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminXen\Plugin', 'Menu'] */
	],
];
