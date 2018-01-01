export PATH="$PATH:/usr/sbin:/sbin:/bin:/usr/bin:";
virsh destroy {$vps_vzid};
rm -f /etc/xinetd.d/{$vps_vzid};
/etc/init.d/xinetd restart;
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;