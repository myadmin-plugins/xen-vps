export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
cd /etc/libvirt/qemu;
virsh define {$vps_vzid};
virsh autostart {$vps_vzid};
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;