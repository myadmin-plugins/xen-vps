export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
echo "    <disk type='file' device='cdrom'>
	  <driver name='qemu' type='raw'/>
	  <source file='/tmp/cd{$vps_vzid}.iso'/>
	  <target dev='hdc' bus='ide'/>
	  <readonly/>
	  <address type='drive' controller='0' bus='1' target='0' unit='0'/>
	</disk>" > /tmp/cd{$vps_vzid}.xml;
virsh detach-device {$prefix}{$vps_vzid} /tmp/cd{$vps_vzid}.xml --config
virsh shutdown {$prefix}{$vps_vzid};
echo "Waiting up to {$max} Seconds for graceful shutdown";
start="\$(date +%s)";
while [ \$((\$(date +%s) - \$start)) -le {$max} ] && [ "$\(virsh list |grep {$prefix}{$vps_vzid})" != "" ]; do
	sleep 5s;
done;
virsh destroy {$prefix}{$vps_vzid};
virsh start {$prefix}{$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
rm -f /tmp/cd{$vps_vzid}.iso;
rm -f /tmp/cd{$vps_vzid}.xml;
/root/cpaneldirect/vps_refresh_vnc.sh {$prefix}{$vps_vzid};