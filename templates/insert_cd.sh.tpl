export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
disk="$(virsh qemu-monitor-command {$vps_vzid} --hmp --cmd "info block"|grep -e "not inserted" -e "/tmp/cd{$vps_vzid}.iso" | cut -d: -f1)";
virsh change-media {$vps_vzid} hdc --eject --live;
if [ {$param|escapeshellarg} != "" ]; then
    rm -f /tmp/cd{$vps_vzid}.iso;
    curl --fail --silent --show-error --proto =https --connect-timeout 30 --max-time 7200 {$param|escapeshellarg} | head -c 17179869185 > /tmp/cd{$vps_vzid}.iso;
    if [ -s /tmp/cd{$vps_vzid}.iso ] && [ "$(stat -c %s /tmp/cd{$vps_vzid}.iso)" -le 17179869184 ]; then
        virsh change-media {$vps_vzid} hdc /tmp/cd{$vps_vzid}.iso --update --live --config
    else
        rm -f /tmp/cd{$vps_vzid}.iso;
        echo "CD image download failed, timed out or was over 16GB";
    fi;
fi;