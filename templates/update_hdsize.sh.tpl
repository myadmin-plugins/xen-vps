virsh destroy {$vps_vzid};
sh /root/cpaneldirect/vps_kvm_lvmresize.sh {$vps_vzid} $mb;
virsh start {$vps_vzid};
bash /root/cpaneldirect/run_buildebtables.sh;