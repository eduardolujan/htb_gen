#!/bin/bash
#TODO gzip docs
debdir=/tmp/htb-gen-debian
version=${1?Arg pckg-version missing}

function put_file () {
local orig=$1 dest=$2 
test -r "${debdir}${dest}" || mkdir -p "${debdir}${dest}"
cp "$orig" "${debdir}/${dest}"
}

rm -rf $debdir

put_file htb-gen			/usr/bin
put_file htb-gen.conf			/etc/htb-gen
put_file htb-gen-rates.conf		/etc/htb-gen
put_file README   			/usr/share/doc/htb-gen
put_file FAQ      			/usr/share/doc/htb-gen
put_file TODO     			/usr/share/doc/htb-gen
put_file changelog			/usr/share/doc/htb-gen
put_file web-frontend/web-htb-gen	/usr/share/doc/htb-gen/web-frontend
put_file web-frontend/htaccess   	/usr/share/doc/htb-gen/web-frontend
put_file web-frontend/README     	/usr/share/doc/htb-gen/web-frontend

mkdir $debdir/DEBIAN/
cat > $debdir/DEBIAN/conffiles <<-EOF
/etc/htb-gen/htb-gen.conf
/etc/htb-gen/htb-gen-rates.conf
EOF
cat > $debdir/DEBIAN/control <<-EOF
Package: htb-gen
Version: $version
Section: net
Priority: optional
Architecture: all
Depends: iptables (>= 1.2.0), iproute (>= 20010824)
Maintainer: Luciano Ruete <luciano@praga.org.ar>
Description: Bandwidth and QoS managment tool based on htb
 htb-gen is meant to be an easy, scalable, yet powerfull,  bandwidth 
 management tool. You can set up/down portions of bandwith for each 
 host or network, that goes trough your router/firewall. 
 Prioritary traffic(web, mail, gaming, ftp, voip, streaming) is 
 preferred over Junk traffic(kazaa, emule, etc).  Also dynamic 
 bandwith borrow and re-assignation is done betwen host thanks to 
 htb boundaries.  
 A web-frontend for config is avaible as well, so remote management 
 is possible. 
 All bash based so it can be used in embedded routers/firewalls 
 (wired/wireless). 
 Two backend are aviable: 
  -generates raw tc commands
  -generates htb-init conf files (util for integration)
 The packet clasification is done by iptables
 More info on http://www.freshmeat.net/projects/htb-gen/
EOF
cat > $debdir/usr/share/doc/README.Debian <<-EOF
Steps to get htb-gen up & running
 -setup the ifaces and total up/down bandwith
     vi /etc/htb-gen/htb-gen.conf 
 -setup per host bandwidth  assignations
     vi /etc/htb-gen/htb-gen-rates.conf
 -run as root 'htb-gen tc_all'
 -yo're done
 -You can watch results with tc stats command, it should show 
  some movement
  watch -n1 -d tc -s class show dev \$iface
 
 After this you can think on how to made this changes permanent.
 The most simple way is calling 'htb-gen tc_all' from an init.d or
 a local.rc script, this will load tc rules and iptables rules as 
 well. If you whant other options keep reading.
 
 Other options to make changes permanent:
  -for iptables rules you can:
     -save the rules as part of the active ruleset
     -Or call 'htb-gen iptables_only' from your firwall script
     -Or output iptables commands 'htb-gen iptables_stdout' and
      add them to your own script
  -for tc settings you can:
    -if tc-backend: 
       -invoke 'htb-gen tc_only' from an init.d script
       -Or output the tc commands 'htb-gen tc_stdout' and add them 
       to your own tc script
    -if htbinit-backend: 
       -gen the htbinit files 'htb-gen htbinit_only' every time you 
        change the conf and call your own htb-init init.d script 
        at boot time
 
Web front-end Install
  See the specific README under /usr/sahre/doc/htb-gen/web-frontend/   
EOF

find $debdir -type d | xargs chmod 755
fakeroot dpkg-deb --build $debdir packages/
