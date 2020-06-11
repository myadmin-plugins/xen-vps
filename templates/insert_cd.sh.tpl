export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
disk="$(virsh qemu-monitor-command {$vps_vzid} --hmp --cmd "info block"|grep -e "not inserted" -e "/tmp/cd{$vps_vzid}.iso" | cut -d: -f1)";
virsh change-media {$vps_vzid} hdc --eject --live;
if [ {$param} != "" ]; then
    wget -O /tmp/cd{$vps_vzid}.iso {$param};
    virsh change-media {$vps_vzid} hdc /tmp/cd{$vps_vzid}.iso --update --live --config
fi;