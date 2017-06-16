<?php

namespace Detain\MyAdminXen;

use Detain\Xen\Xen;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Xen Vps';
	public static $description = 'Allows selling of Xen Server and VPS License Types.  More info at https://www.netenberg.com/xen.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a xen license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function Hooks() {
		return [
			'vps.settings' => [__CLASS__, 'Settings'],
		];
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Xen Activation', __LINE__, __FILE__);
			function_requirements('activate_xen');
			activate_xen($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$xen = new Xen(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $xen->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Xen editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_xen', 'icons/database_warning_48.png', 'ReUsable Xen Licenses');
			$menu->add_link($module, 'choice=none.xen_list', 'icons/database_warning_48.png', 'Xen Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.xen_licenses_list', 'whm/createacct.gif', 'List all Xen Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
		$loader = $event->getSubject();
		$loader->add_requirement('crud_xen_list', '/../vendor/detain/crud/src/crud/crud_xen_list.php');
		$loader->add_requirement('crud_reusable_xen', '/../vendor/detain/crud/src/crud/crud_reusable_xen.php');
		$loader->add_requirement('get_xen_licenses', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('get_xen_list', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('xen_licenses_list', '/../vendor/detain/myadmin-xen-vps/src/xen_licenses_list.php');
		$loader->add_requirement('xen_list', '/../vendor/detain/myadmin-xen-vps/src/xen_list.php');
		$loader->add_requirement('get_available_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('activate_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('get_reusable_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('reusable_xen', '/../vendor/detain/myadmin-xen-vps/src/reusable_xen.php');
		$loader->add_requirement('class.Xen', '/../vendor/detain/xen-vps/src/Xen.php');
		$loader->add_requirement('vps_add_xen', '/vps/addons/vps_add_xen.php');
	}

	public static function Settings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_xen_cost', 'XEN VPS Cost Per Slice:', 'XEN VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_XEN_COST'));
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_xen_server', 'Xen NJ Server', (defined('NEW_VPS_XEN_SERVER') ? NEW_VPS_XEN_SERVER : ''), 8, 1);
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_xen', 'Out Of Stock Xen Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_XEN'), array('0', '1'), array('No', 'Yes', ));
	}

}
