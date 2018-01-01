export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
echo "    <disk type='file' device='cdrom'>
	  <driver name='qemu' type='raw'/>" > /tmp/cd{$vps_vzid}.xml;
if [ {$url} != "" ]; then
	wget -O /tmp/cd{$vps_vzid}.iso {$url};
	echo "	  <source file='/tmp/cd{$vps_vzid}.iso'/>" >> /tmp/cd{$vps_vzid}.xml;
fi;
echo "	  <target dev='hdc' bus='ide'/>
	  <readonly/>
	  <address type='drive' controller='0' bus='1' target='0' unit='0'/>
	</disk>" >> /tmp/cd{$vps_vzid}.xml;
virsh attach-device {$prefix}{$vps_vzid} /tmp/cd{$vps_vzid}.xml --config
virsh shutdown {$prefix}{$vps_vzid};
echo "Waiting up to {$max} Seconds for graceful shutdown";
start="$(date +%s)";
while [ $(($(date +%s) - $start)) -le {$max} ] && [ "$(virsh list |grep {$prefix}{$vps_vzid})" != "" ]; do
	sleep 5s;
done;
virsh destroy {$prefix}{$vps_vzid};
virsh start {$prefix}{$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
/root/cpaneldirect/vps_refresh_vnc.sh {$prefix}{$vps_vzid};