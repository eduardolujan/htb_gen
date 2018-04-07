# htb_gen
Summary 

* Description
* Download
* Requirements
* Quick Install
* Web front-end Install
* How does it work
* Where it works 
* Some Tecnical details

##Description
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
The packet clasification is done by iptables
  
##Download
 http://www.freshmeat.net/projects/htb-gen/

##Requirements
* bash
* QoS htb kernel support
* iproute2 tc 
* iptables

## Quick Install
 * copy htb-gen anywhere in your PATH (ie: /usr/local/bin)
 * mkdir /etc/htb-gen/
 * cp htb-gen.conf /etc/htb-gen/
 * cp htb-gen-rates.conf /etc/htb-gen/
 * edit this 2 files to fit your needs, self documented
 * run as root 'htb-gen all'
 * yo're done
 * You can watch results with tc stats command, it should show 
  some movement
 * watch -n1 -d tc -s class show dev $iface
  
Or you can download jjo's htb-stats.sh script. It will show 
real time bandwidth usage per class/host in a very fancy way.
http://www.lugmen.org.ar/~jjo/jjotip/htb/htb-stats.sh
 
After this you can think on how to made this changes permanent.
The most simple way is calling 'htb-gen tc' from an init.d or
a local.rc script, this will load tc rules and iptables rules as 
well. If you whant other options keep reading.
 
#####Other options to make changes permanent:
* for iptables rules you can:
	- save the rules as part of the active ruleset
	- Or call 'htb-gen iptables' from your firwall script
	- Or output iptables commands 'htb-gen iptables_stdout' and add them to your own script
	- for tc settings you can:
	- invoke 'htb-gen tc' from an init.d script
	- Or output the tc commands 'htb-gen tc_stdout' and add them 
	to your own tc script

### Web-frontend Install
  See the specific README under web-frontend/ directory  

### How does it work
#####The bw that you assign for each host is divided like this (this can be easy addapted if you know a litle bit of iptables):
* Prio traffic
    - packets smallest than 100bytes (tcp ACKs, most icmp messages)
    - user defined priotary tcp ports 
    - user defined priotary udp ports
    - user defined priotary protocols 
    - user defined protcols aimed by netfilter helpers
-Default traffic (junk traffic)
    - all traffic that do not mach any of the above (ie:emule, torrent,
      kazaa, gnutella...and so on)

 By thefault the host bandwidth is shared betwen this two kind of traffic, but
 the script grants that anytime that i use "prio traffic" it will climb up to
 90%, till that 'prio traffic' ends. This % can be modified if you want, see
 the 'rate_dfl_percet' value in conf. Also is posible to save junk bandwith 
 assigning only a % of host ceil to the dfl traffic, see 'ceil_dfl_percet'.
 This will help to have several host sharing bw without almost any complaint.

Where it works 
 This script is instalable in a Linux Firewall(NAT/Router) (even on embebed 
 ones) that connects two or more networks. 
 In general there are two networks: Internet and a LAN or a set of public IPs. 

Some tecnicals details
 -The iptables point of entrance of htb-gen is just one_per_iface rule in the 
 FORWARD and OUTPUT chain of the 'mangle' table.
 So is very simple to hack your own mangle rules, you can INSERT rules before 
 this ones, or you can APPEND rules after this ones. If you have previous rules
 in the mangle table, they will be preserverd and the htb-gen rules will be 
 appended, so keep this in mind if your packets don't get MARKed.
 All packets that match a given htb-gen host are ACCEPTed, so they do _not_ 
 continue traveling mangle, this is to grant that the correct MARK will be 
 preserved.
 
 -All bandwith that not fit in any class (ie: bw from a host that is not in 
 the htb-gen-rates.conf file) is not managed, and bypass htb sheip.
 This includes the upload of the firewall itself. So services in the server 
 will continue working without bw limitations. 
 
