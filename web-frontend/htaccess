# Example setup of Digest (no plain passwd) setup on Apache
# put this file as .htaccess in the same directory
# of the web-cgi script
AuthType Digest
 AuthName "htb-gen"
 #this is the web uri of the htb-gen cgi directory
 AuthDigestDomain /cgi-bin/htb-gen
 #this is full path, relatives paths not accepted
 AuthDigestFile /usr/lib/cgi-bin/htb-gen/.htpasswd
 Order deny,allow
 Deny from all
 #put here any host/net you want allow
 Allow from 127.0.0.1
 Require valid-user
 Satisfy All
