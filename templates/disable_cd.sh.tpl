export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
export cd_max=60;
echo "    <disk type='file' device='cdrom'>
      <driver name='qemu' type='raw'/>
      <source file='/tmp/cd{$vps_vzid}.iso'/>
      <target dev='hdc' bus='ide'/>
      <readonly/>
      <address type='drive' controller='0' bus='1' target='0' unit='0'/>
    </disk>" > /tmp/cd{$vps_vzid}.xml;
virsh detach-device {$vps_vzid} /tmp/cd{$vps_vzid}.xml --config
virsh shutdown {$vps_vzid};
echo "Waiting up to $cd_max Seconds for graceful shutdown";
start="\$(date +%s)";
while [ \$((\$(date +%s) - \$start)) -le $cd_max ] && [ "$\(virsh list |grep {$vps_vzid})" != "" ]; do
    sleep 5s;
done;
virsh destroy {$vps_vzid};
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
rm -f /tmp/cd{$vps_vzid}.iso;
rm -f /tmp/cd{$vps_vzid}.xml;
/root/cpaneldirect/vps_refresh_vnc.sh {$vps_vzid};