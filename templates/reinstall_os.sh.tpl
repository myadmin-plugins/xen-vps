export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
{if isset($extra['vnc']) && (int)$extra['vnc'] > 1000}
/root/cpaneldirect/vps_kvm_screenshot_swift.sh {$extra['vnc'] - 5900} {$vps_vzid};
{/if}
virsh destroy {$vps_vzid};
rm -f /etc/xinetd.d/{$vps_vzid};
/etc/init.d/xinetd restart;
virsh autostart --disable {$vps_vzid};
virsh managedsave-remove {$vps_vzid};
virsh undefine {$vps_vzid};
kpartx -dv  /dev/vz/{$vps_vzid};
echo y | lvremove /dev/vz/{$vps_vzid};
