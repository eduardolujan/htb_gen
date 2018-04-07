#!/bin/bash
htb_gen_rates_conf="/etc/htb-gen/htb-gen-rates.conf"
#See web-frontend/README to activate the reload command
reload_command="sudo /etc/init.d/htb-gen start"

# Script begins --here!--

function loadconf () {
	local n=0 line=
	unset ip rate_down ceil_down rate_up ceil_up provider
	source htb-gen loadvars_web
	for ((n=0;n<${#ip[@]};n++)); do
		if [[ -z ${ip[n]%%[^@]*} ]]; then enabled[n]=1; else enabled[n]=0; fi 
		ip[n]=${ip[n]/@/}
		if [[ ! -z ${auto_rate_down[n]} ]];then
			auto_rate_down_v[n]=${rate_down[n]}
			rate_down[n]=0
		fi
		if [[ ! -z ${auto_rate_up[n]} ]];then
			auto_rate_up_v[n]=${rate_up[n]}
			rate_up[n]=0
		fi
	done
}

function do_head () {
cat <<EOF
content-type: text/html

<html>
<head>
	<title>htb-gen bandwidth managment tool</title>
	<style type="text/css">
    table tr td {
        font-size:13px;
		padding-left: 2px ;
		padding-right: 2px ;
		background:#fff;
        }
    input {
        font-size:12px;
        }
	</style>

</head>
<body>
<form action=./web-htb-gen method=get>
<table border=1> 
	<tr>
		<th>Cliente<a style="font-size:13px" href="http://${HTTP_HOST}/trafico">(todos)</a></td>
		<th>Min Down</td>
		<th>Max Down</td>
		<th>Min Up</td>
		<th>Max Up</td>
		<th>%P2P</td>
		<th>Host IP/Net</td>
		<th>If Local</td>
		<th>If Inet</td>
		<th>MAC</td>
		<th>IP Publica</td>
		<th>Enabled</td>
		$1
	</tr>
EOF
}
function split () {
local IFS="$1" string="$2" ; 
unset splited; splited=($string)
}
# Maing begins --here!--
#do_head && env && exit
loadconf 
QUERY_STRING=${QUERY_STRING//\\%2F/\/}
QUERY_STRING=${QUERY_STRING//\\%3A/\:}
split "&" ${QUERY_STRING:-$1} 
query=("${splited[@]}")
action=${query[0]#*=}; n=${query[0]%=*}; 
case "$action" in
	Reload*) 
			$reload_command &>/dev/null
			echo -e "location: http://${HTTP_HOST}${REQUEST_URI%\?*}\n"
			;;
	delete)	echo -n > $htb_gen_rates_conf 
			for ((i=0;i<${#ip[@]};i++)); do
				test $i -eq $n && continue
				printf "%-19s %5s %5s %5s %5s %3s %6s %6s %17s %-19s %-25s \n" ${ip[i]:-0.0.0.0} ${rate_down[i]:-0} ${ceil_down[i]:-0} ${rate_up[i]:-0} ${ceil_up[i]:-0}  ${iface_down[i]} ${iface_up[i]}  ${ceil_dfl_percent[i]} ${mac_address[i]:-00:00:00:00:00:00} ${ip_publica[i]:-0.0.0.0} ${name_client[i]:-anonimo} >> $htb_gen_rates_conf 
			done
			unset QUERY_STRING
			echo -e "location: http://${HTTP_HOST}${REQUEST_URI%\?*}\n"
			;;
	edit|new)
			if [[ ${#query[@]} == 1 ]]; then # no data, whants the form
				do_head
				cat <<-EOF
				<tr>
				<input type=hidden name=${n} value=edit>
				<td align=right><input type=text maxlength=25 size=16 name=name_client value=${name_client[n]}></td>
				<td align=right><input type=text maxlength=5 size=5 name=rate_down value=${rate_down[n]}></td>
				<td align=right><input type=text maxlength=5 size=5 name=ceil_down value=${ceil_down[n]}></td>
				<td align=right><input type=text maxlength=5 size=5 name=rate_up value=${rate_up[n]}></td>
				<td align=right><input type=text maxlength=5 size=5 name=ceil_up value=${ceil_up[n]}></td>
				<td align=right><select name="ceil_dfl_percent">
				EOF
				for ((p=30;p<101;p+=10)); do
					if [[ $p == ${ceil_dfl_percent[n]} ]] ;then
						echo "<option value=$p selected=selected>${p}%</option>"
					else
						echo "<option value=$p>${p}%</option>"
					fi
				done
				cat <<-EOF
				</td>
				<td align=right><input type=text maxlength=18 size=16 name=ip value=${ip[n]}></td>
				<td align=right><select name="iface_down">
				EOF
				for ((if=0;if<${#ifaces[@]};if++)); do
					if [[ ${ifaces[if]} == ${iface_down[n]} ]] ;then
						echo "<option value=$if selected=selected>${ifaces_name[if]}</option>"
					else
						echo "<option value=$if>${ifaces_name[if]}</option>"
					fi
				done
				cat <<-EOF
				</td>
				<td align=right><select name="iface_up">
				EOF
				for ((if=0;if<${#ifaces[@]};if++)); do
					if [[ ${ifaces[if]} == ${iface_up[n]} ]] ;then
						echo "<option value=$if selected=selected>${ifaces_name[if]}</option>"
					else
						echo "<option value=$if>${ifaces_name[if]}</option>"
					fi
				done
				cat <<-EOF
				</td>
				<td align=right><input type=text maxlength=17 size=18 name=mac_address value=${mac_address[n]}></td>
				<td align=right><input type=text maxlength=18 size=16 name=ip_publica value=${ip_publica[n]}></td>
				<td><input type=radio $(if ((${enabled[n]})); then echo checked; fi) name=enabled value=1>yes
					<input type=radio $(if ((! ${enabled[n]}));then echo checked; fi) name=enabled value=0>no
				</td>
				<td align=right><input type=submit name=ok value=ok action=submit></td>
				</tr>
				</table>
				EOF
			else # data alredy, insert it
				name_client[n]=${query[1]#*=}
				rate_down[n]=${query[2]#*=}
				ceil_down[n]=${query[3]#*=}
				rate_up[n]=${query[4]#*=}
				ceil_up[n]=${query[5]#*=}
				ceil_dfl_percent[n]=${query[6]#*=}
				ip[n]=${query[7]#*=}
				iface_down[n]=${ifaces[${query[8]#*=}]}
				iface_up[n]=${ifaces[${query[9]#*=}]}
				mac_address[n]=${query[10]#*=}
				ip_publica[n]=${query[11]#*=}
				enabled[n]=${query[12]#*=}
				
				tmp_file=$(mktemp)
				echo -n > ${tmp_file} 
				for ((i=0;i<${#ip[@]};i++)); do
					test "${enabled[i]}" == "0" && ip[i]="@${ip[i]}"
					printf "%-19s %5s %5s %5s %5s %3s %6s %6s %17s %-19s %-25s \n" ${ip[i]:-0.0.0.0} ${rate_down[i]:-0} ${ceil_down[i]:-0} ${rate_up[i]:-0} ${ceil_up[i]:-0}  ${iface_down[i]} ${iface_up[i]}  ${ceil_dfl_percent[i]} ${mac_address[i]:-00:00:00:00:00:00} ${ip_publica[i]:-0.0.0.0} ${name_client[i]:-anonimo} >> "${tmp_file}"
				done
				if (htb-gen loadvars -c ${tmp_file} &>/dev/null) ; then 
					cp ${tmp_file} ${htb_gen_rates_conf}
					echo -e "location: http://${HTTP_HOST}${REQUEST_URI%\?*}\n"
				else 
					message=$(htb-gen loadvars -c ${tmp_file})
					do_head 
					echo "<h1>EROOR</h1><h2>$message</h2><h2>Vuelva atras y corriga</h2>"	
				fi
			fi
			;;
	*)	do_head "<td align=left colspan=2><input type=submit name=${#ip[@]} value=new action=submit></td>"
		for ((n=0;n<${#ip[@]};n++)); do
			for ((if=0;if<${#ifaces[@]};if++)); do
				test ${ifaces[if]} == ${iface_down[n]} && _iface_down=${ifaces_name[if]}
				test ${ifaces[if]} == ${iface_up[n]} && _iface_up=${ifaces_name[if]}
			done
			cat<<-EOF
			<tr $(if [[ "${enabled[n]}" == "0" ]];then echo 'style="color:AAAAAA"';fi )>
			<td align=right><a href="/cgi-bin/htb-gen/traffic.pl?show=one&ip=${ip[n]}">${name_client[n]}</a>      </td>
			<td align=right><span style="color:AAAAAA">[${auto_rate_down_v[n]:--}]</span> ${rate_down[n]}</td>
			<td align=right>${ceil_down[n]}</td>
			<td align=right><span style="color:AAAAAA">[${auto_rate_up_v[n]:--}]</span> ${rate_up[n]}</td>
			<td align=right>${ceil_up[n]}  </td>
			<td align=right>${ceil_dfl_percent[n]}  </td>
			<td align=right>${ip[n]}</a>      </td>
			<td align=right>${_iface_down} </td>
			<td align=right>${_iface_up} </td>
			<td align=right>${mac_address[n]} </td>
			<td align=right>${ip_publica[n]} </td>
			<td align=right>$(if [[ "${enabled[n]}" == "0" ]];then echo '<span style="color:red">no</span>' ; else echo '<span style="color:green">yes</span>';fi )</td>
			<td align=right><input type=submit name=${n} value=edit action=submit></td>
			<td align=right><input type=submit name=${n} value=delete action=submit></td>
			</tr>
			EOF
		done
		cat <<-EOF
		</table>
		<input type=submit name=reload value="Reload htb-gen" action=submit>
		EOF
		test -z "$reload_command" && echo "The Reload function is inactive, see web-frontend/README to change it"
			;;
esac

cat 	<<EOF
</form>
</body>
</html>
EOF
