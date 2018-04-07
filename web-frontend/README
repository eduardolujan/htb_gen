Description
 The web-frontend allow to change the htb-gen-rates.conf file from
 the web. Such kind of frontend allows that a non network expert
 can still manage bandwidth assignation. But to install the web
 interface may requires some knowledge. 
 From the web-frontend you can assign bw portions, create and delete
 new hosts, and reload htb-gen to make all changes efective. 

Quick Install
 There is no quick install, so read carefully all this file if you
 want to activate a web based config to htb-gen
 
Install
 I choose to code a bash-based cgi to make posible to port it  to 
 a minimal enviroment where bash fits, but we aware that bash-cgi 
 colud be a real security risk, if you  do'nt take some measures.
 The only aproach that i'will explain is to use host based access 
 _and_ digest authentication, it takes at least the minimun cares
 to have a tool like htb-gen accesible from the web. To have the 
 web inteface without this security measures is absolutly INSANE!

 -mkdir /path/to/cgi-bin/htb-gen/
 -cp web-htb-gen /path/to/cgi-bin/htb-gen/
 -You need this apache directive for the /cgi-bin/htb-gen directory:
     AllowOverride AuthConfig Limit
  And this apache modules loaded:
     mod_auth_digest mod_acces
 -cp htaccess to /path/to/cgi-bin/htb-gen/.htaccess
 -By default only localhost is allowed to login to the web-frontend
  to change this, edit the .htaccess file
 -Create the passwd file, password will be asked 
  htdigest -c /path/to/cgi-bin/htb-gen/.htpasswd htb-gen admin
 -Make the configuration file writeable for the web-server user
  (ie: www-data)
  chown www-data /etc/htb-gen/htb-gen-rates.conf 
 -ok, you're done, you can edit per client rates now, from the web.

Reloading htb-gen from the web 
 By default change the config by the web front end is poible, but, to make 
 the changes efective you need to reload htb-gen by hand. You can put
 a cron script to reload htb-gen every nigth, so your clients bw will be
 stay updateted, and your host will stay secure. 
 
 If you want to reload htb-gen rules from the web interface there are some 
 SECURITY RISKS that you must know: to reload htb-gen (directly or 
 indirectly) root permisions are needed, because tc and iptables are admin 
 commands that interacts directly with the kernel. Your webserver user can't
 run this commands, neither your script, and extra setup will be needed.
 
 If you really know what are you doing, and the security implications
 you can add this lines to /etc/suders with the command visudo: 
  www-data        ALL = NOPASSWD: /sbin/iptables
  www-data        ALL = NOPASSWD: /sbin/iptables-save
  www-data        ALL = NOPASSWD: /sbin/iptables-restore
  www-data        ALL = NOPASSWD: /sbin/tc
 
 Then you can edit your 'htb-gen' and change the iptables and tc path like this:
  iptables_command="sudo /sbin/iptables"
  iptables_save_command="sudo /sbin/iptables-save"
  iptables_restore_command="sudo /sbin/iptables-restore"
  tc_command="sudo /sbin/tc"  
 
