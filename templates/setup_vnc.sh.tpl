bash /root/cpaneldirect/vps_kvm_setup_vnc.sh {if $vps_vzid == "0"}{$vps_id}{else}{$vps_vzid}{/if} {$param|escapeshellarg};
