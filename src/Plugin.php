<?php

namespace Detain\MyAdminXen;

use Detain\Xen\Xen;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminXen
 */
class Plugin
{
    public static $name = 'Xen VPS';
    public static $description = 'Allows selling of Xen VPS Types.  The Xen Project hypervisor is an open-source type-1 or baremetal hypervisor, which makes it possible to run many instances of an operating system or indeed different operating systems in parallel on a single machine (or host). The Xen Project hypervisor is the only type-1 hypervisor that is available as open source. It is used as the basis for a number of different commercial and open source applications, such as: server virtualization, Infrastructure as a Service (IaaS), desktop virtualization, security applications, embedded and hardware appliances. The Xen Project hypervisor is powering the largest clouds in production today.  More info at https://www.xenproject.org/';
    public static $help = '';
    public static $module = 'vps';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            //self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.queue' => [__CLASS__, 'getQueue'],
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getActivate(GenericEvent $event)
    {
        $serviceClass = $event->getSubject();
        if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
            myadmin_log(self::$module, 'info', 'Xen Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getDeactivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
            myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId(), true, false, $serviceClass->getCustid());
            $serviceClass = $event->getSubject();
            $GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->add_text_setting(self::$module, _('Slice Costs'), 'vps_slice_xen_cost', _('XEN VPS Cost Per Slice'), _('XEN VPS will cost this much for 1 slice.'), $settings->get_setting('VPS_SLICE_XEN_COST'));
        $settings->setTarget('module');
        $settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_vps_xen_server', _('Xen NJ Server'), (defined('NEW_VPS_XEN_SERVER') ? NEW_VPS_XEN_SERVER : ''), 8, 1);
        $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_xen', _('Out Of Stock Xen Secaucus'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_XEN'), ['0', '1'], ['No', 'Yes']);
        $settings->setTarget('global');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getQueue(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('XEN_LINUX'), get_service_define('XEN_WINDOWS')])) {
            $serviceInfo = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $server_info = $serviceInfo['server_info'];
            if (!file_exists(__DIR__.'/../templates/'.$serviceInfo['action'].'.sh.tpl')) {
                myadmin_log(self::$module, 'error', 'Call '.$serviceInfo['action'].' for VPS '.$serviceInfo['vps_hostname'].'(#'.$serviceInfo['vps_id'].'/'.$serviceInfo['vps_vzid'].') Does not Exist for '.self::$name, __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id'], true, false, $serviceInfo[$settings['PREFIX'].'_custid']);
            } else {
                $smarty = new \TFSmarty();
                $smarty->assign($serviceInfo);
                $smarty->assign('vps_vzid', is_numeric($serviceInfo['vps_vzid']) ? (in_array($event['type'], [get_service_define('XEN_WINDOWS')]) ? 'windows'.$serviceInfo['vps_vzid'] : 'linux'.$serviceInfo['vps_vzid']) : $serviceInfo['vps_vzid']);
                $output = $smarty->fetch(__DIR__.'/../templates/'.$serviceInfo['action'].'.sh.tpl');
                myadmin_log(self::$module, 'info', 'Queue '.$server_info[$settings['PREFIX'].'_name'].' '.$output, __LINE__, __FILE__, self::$module, $serviceInfo['vps_id'], true, false, $serviceInfo['vps_custid']);
                $event['output'] = $event['output'].$output;
            }
            $event->stopPropagation();
        }
    }
}
