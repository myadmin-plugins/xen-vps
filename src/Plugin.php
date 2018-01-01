<?php

namespace Detain\MyAdminXen;

use Detain\Xen\Xen;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminXen
 */
class Plugin {

	public static $name = 'Xen VPS';
	public static $description = 'Allows selling of Xen VPS Types.  The Xen Project hypervisor is an open-source type-1 or baremetal hypervisor, which makes it possible to run many instances of an operating system or indeed different operating systems in parallel on a single machine (or host). The Xen Project hypervisor is the only type-1 hypervisor that is available as open source. It is used as the basis for a number of different commercial and open source applications, such as: server virtualization, Infrastructure as a Service (IaaS), desktop virtualization, security applications, embedded and hardware appliances. The Xen Project hypervisor is powering the largest clouds in production today.  More info at https://www.xenproject.org/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.queue_backup' => [__CLASS__, 'getQueueBackup'],
			self::$module.'.queue_restore' => [__CLASS__, 'getQueueRestore'],
			self::$module.'.queue_enable' => [__CLASS__, 'getQueueEnable'],
			self::$module.'.queue_destroy' => [__CLASS__, 'getQueueDestroy'],
			self::$module.'.queue_delete' => [__CLASS__, 'getQueueDelete'],
			self::$module.'.queue_reinstall_os' => [__CLASS__, 'getQueueReinstallOs'],
			self::$module.'.queue_update_hdsize' => [__CLASS__, 'getQueueUpdateHdsize'],
			self::$module.'.queue_enable_cd' => [__CLASS__, 'getQueueEnableCd'],
			self::$module.'.queue_disable_cd' => [__CLASS__, 'getQueueDisableCd'],
			self::$module.'.queue_insert_cd' => [__CLASS__, 'getQueueInsertCd'],
			self::$module.'.queue_eject_cd' => [__CLASS__, 'getQueueEjectCd'],
			self::$module.'.queue_start' => [__CLASS__, 'getQueueStart'],
			self::$module.'.queue_stop' => [__CLASS__, 'getQueueStop'],
			self::$module.'.queue_restart' => [__CLASS__, 'getQueueRestart'],
			self::$module.'.queue_setup_vnc' => [__CLASS__, 'getQueueSetupVnc'],
			self::$module.'.queue_reset_password' => [__CLASS__, 'getQueueResetPassword'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', 'Xen Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$xen = new Xen(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
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

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_xen', 'images/icons/database_warning_48.png', 'ReUsable Xen Licenses');
			$menu->add_link(self::$module, 'choice=none.xen_list', 'images/icons/database_warning_48.png', 'Xen Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.xen_licenses_list', '/images/whm/createacct.gif', 'List all Xen Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('crud_xen_list', '/../vendor/detain/crud/src/crud/crud_xen_list.php');
		$loader->add_page_requirement('crud_reusable_xen', '/../vendor/detain/crud/src/crud/crud_reusable_xen.php');
		$loader->add_requirement('get_xen_licenses', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('get_xen_list', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_page_requirement('xen_licenses_list', '/../vendor/detain/myadmin-xen-vps/src/xen_licenses_list.php');
		$loader->add_page_requirement('xen_list', '/../vendor/detain/myadmin-xen-vps/src/xen_list.php');
		$loader->add_requirement('get_available_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('activate_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_requirement('get_reusable_xen', '/../vendor/detain/myadmin-xen-vps/src/xen.inc.php');
		$loader->add_page_requirement('reusable_xen', '/../vendor/detain/myadmin-xen-vps/src/reusable_xen.php');
		$loader->add_requirement('class.Xen', '/../vendor/detain/xen-vps/src/Xen.php');
		$loader->add_page_requirement('vps_add_xen', '/vps/addons/vps_add_xen.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_xen_cost', 'XEN VPS Cost Per Slice:', 'XEN VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_XEN_COST'));
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_xen_server', 'Xen NJ Server', (defined('NEW_VPS_XEN_SERVER') ? NEW_VPS_XEN_SERVER : ''), 8, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_xen', 'Out Of Stock Xen Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_XEN'), ['0', '1'], ['No', 'Yes']);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueBackup(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Backup', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/backup.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestore(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restore', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restore.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnable(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDestroy(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Destroy', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/destroy.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDelete(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Delete', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/delete.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueReinstallOsupdateHdsize(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reinstall Osupdate Hdsize', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reinstall_osupdate_hdsize.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDisableCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Disable Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/disable_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueInsertCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Insert Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/insert_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEjectCd(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Eject Cd', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/eject_cd.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Start', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/start.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStop(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Stop', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/stop.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restart', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restart.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueSetupVnc(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Setup Vnc', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/setup_vnc.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueResetPassword(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('XEN_WINDOWS')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reset Password', __LINE__, __FILE__);
			$vps = $event->getSubject();
			$server_info = $vps['server_info'];
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $vps['vps_id'],
				'vps_vzid' => is_numeric($vps['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$vps['vps_vzid'] : 'linux'.$vps['vps_vzid']) : $vps['vps_vzid'],
				'email' => $GLOBALS['tf']->accounts->cross_reference($vps['vps_custid']),
				'domain' => $event['domain'],
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reset_password.sh.tpl');
			$event->stopPropagation();
		}
	}

}
