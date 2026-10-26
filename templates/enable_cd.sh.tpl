export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
export cd_max=60;
echo "    <disk type='file' device='cdrom'>
      <driver name='qemu' type='raw'/>" > /tmp/cd{$vps_vzid}.xml;
if [ {$param|escapeshellarg} != "" ]; then
    rm -f /tmp/cd{$vps_vzid}.iso;
    curl --fail --silent --show-error --proto =http,https,ftp,ftps --connect-timeout 30 --max-time 7200 {$param|escapeshellarg} | head -c 17179869185 > /tmp/cd{$vps_vzid}.iso;
    if [ -s /tmp/cd{$vps_vzid}.iso ] && [ "$(stat -c %s /tmp/cd{$vps_vzid}.iso)" -le 17179869184 ]; then
        echo "	  <source file='/tmp/cd{$vps_vzid}.iso'/>" >> /tmp/cd{$vps_vzid}.xml;
    else
        rm -f /tmp/cd{$vps_vzid}.iso;
        echo "CD image download failed, timed out or was over 16GB";
    fi;
fi;
echo "	  <target dev='hdc' bus='ide'/>
      <readonly/>
      <address type='drive' controller='0' bus='1' target='0' unit='0'/>
    </disk>" >> /tmp/cd{$vps_vzid}.xml;
virsh attach-device {$vps_vzid} /tmp/cd{$vps_vzid}.xml --config
virsh shutdown {$vps_vzid};
echo "Waiting up to $cd_max Seconds for graceful shutdown";
start="$(date +%s)";
while [ $(($(date +%s) - $start)) -le $cd_max ] && [ "$(virsh list |grep {$vps_vzid})" != "" ]; do
    sleep 5s;
done;
virsh destroy {$vps_vzid};
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;
/root/cpaneldirect/vps_refresh_vnc.sh {$vps_vzid};