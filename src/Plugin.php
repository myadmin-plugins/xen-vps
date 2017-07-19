<?php

namespace Detain\MyAdminXen;

use Detain\Xen\Xen;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Xen VPS';
	public static $description = 'Allows selling of Xen VPS Types.  The Xen Project hypervisor is an open-source type-1 or baremetal hypervisor, which makes it possible to run many instances of an operating system or indeed different operating systems in parallel on a single machine (or host). The Xen Project hypervisor is the only type-1 hypervisor that is available as open source. It is used as the basis for a number of different commercial and open source applications, such as: server virtualization, Infrastructure as a Service (IaaS), desktop virtualization, security applications, embedded and hardware appliances. The Xen Project hypervisor is powering the largest clouds in production today.  More info at https://www.xenproject.org/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', 'Xen Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	public static function getDeactivate(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$xen = new Xen(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $xen->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Xen editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_xen', 'icons/database_warning_48.png', 'ReUsable Xen Licenses');
			$menu->add_link(self::$module, 'choice=none.xen_list', 'icons/database_warning_48.png', 'Xen Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.xen_licenses_list', 'whm/createacct.gif', 'List all Xen Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
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

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_xen_cost', 'XEN VPS Cost Per Slice:', 'XEN VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_XEN_COST'));
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_xen_server', 'Xen NJ Server', (defined('NEW_VPS_XEN_SERVER') ? NEW_VPS_XEN_SERVER : ''), 8, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_xen', 'Out Of Stock Xen Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_XEN'), ['0', '1'], ['No', 'Yes']);
	}

}
