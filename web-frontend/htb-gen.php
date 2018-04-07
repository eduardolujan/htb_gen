<?php
//dl("rrdtool.so");
require_once 'HTML/QuickForm.php';
require_once 'HTML/Table.php';

$conf_file = "/etc/htb-gen/htb-gen-rates.conf";
do_head();
$action = $_REQUEST['action'];
$ip_edit = $_REQUEST['ip_edit'];
$hosts = parse_conf($conf_file); 

switch($action) {
case "reload":
	system("sudo /etc/init.d/htb-gen restart");
	echo "<script laguage=javascript>\n window.location = 'index.php?action=show';\n</script>";
	break;
case "edit":
case "new":
	$host = $hosts[$ip_edit];
	$form = getForm(); 
	if ($action = "edit") {
		$form->setDefaults($host);
	}
	function myProcess($values)
	{
		global $hosts;
		global $conf_file;
		$hosts[$values['ip']] = $values;
		write_conf($conf_file, $hosts);
		//echo '<pre>';
		//var_dump($values);
		//echo '</pre>';
	}
	if ($form->validate()) {
	    $form->freeze();
	    $form->process('myProcess', false);
		echo "<script laguage=javascript>\n window.location = 'index.php?action=show';\n</script>";
	}
	$form->display();
	break;
case "delete":
	unset($hosts[$ip_edit]);
	write_conf($conf_file, $hosts);
case "show":
default:
	$table = new HTML_Table('border=1');
	$boton_nuevo = "<input type=button  value=nuevo onClick=\"getElementById('action').value = 'new'; getElementById('f').submit();\">";
	//$boton_aplicar = "<input type=button  value=aplicar onClick=\"getElementById('action').value = 'reload'; getElementById('f').submit();\">";
	$table->addRow(array('Name','IP','Down', 'Down MAX', 'Up','Up Max', 'Junk %', 'TCP Prio', 'UDP Prio', 'Proto Prio', 'Helper Prio', 'Enabled', $boton_nuevo, $boton_aplicar), 'align=right', 'TH');
	foreach($hosts as $key => $host) { 
		$graph = rrd_graph_mini($host);
		$table->addRow(array($host['name_client'], 
				     $host['ip'], 
				     $host['rate_down'], 
				     $host['ceil_down'], 
				     $host['rate_up'], 
				     $host['ceil_up'], 
				     $host['ceil_dfl_percent'], 
				     $host['tcp_prio_ports']=="0"?'<span style="color:green">defaults</span>':$host['tcp_prio_ports'], 
				     $host['udp_prio_ports']=="0"?'<span style="color:green">defaults</span>':$host['udp_prio_ports'], 
				     $host['prio_protos']=="0"?'<span style="color:green">defaults</span>':$host['prio_protos'], 
				     $host['prio_helpers']=="0"?'<span style="color:green">defaults</span>':$host['prio_helpers'], 
				     $host['enabled']?'<span style="color:green">yes</span>':'<span style="color:red">no</span>',
				     "<input type=button value=editar onClick=\"getElementById('ip_edit').value = '$host[ip]';getElementById('action').value = 'edit'; getElementById('f').submit();\">",
				     "<input type=button value=borrar onClick=\"getElementById('ip_edit').value = '$host[ip]';getElementById('action').value = 'delete'; getElementById('f').submit();\">",
				     '<a href="/cgi-bin/traffic.pl?show=one&ip='.str_replace("/","_",$host['ip']).'">'.$graph.'</a>'
				     //'<a href="/cgi-bin/htb-gen/traffic.pl?show=one&ip='.$host['ip'].'">'.$graph['calcpr'][0].'</a>'
				      ), 'align=right');
	}
	echo '<form id=f>';
	echo '<input type=hidden id=ip_edit name=ip_edit value="" >';
	echo '<input type=hidden id=action name=action value="show" >';
	echo $table->toHtml();
	echo '</form>';
	break;
}



do_tail();
//##########END############
function rrd_graph_mini ($host) {
		$host=str_replace("/","_",$host);
		$height = 32;
		$width = 150;
		//$x_grid = "MINUTE:10:MINUTE:30:MINUTE:30:0:\%H:\%M";
		$start = '-4h';
		$rrdir = "/var/lib/rrd";
		$rrdir_img = "/var/lib/rrd/img";
		$rrd_file_d = "$rrdir/${host['iface_down']}-${host['ip']}.rrd";
		$rrd_file_d_prio = "$rrdir/${host['iface_down']}-${host['ip']}.prio.rrd";
		$rrd_file_d_dfl = "$rrdir/${host['iface_down']}-${host['ip']}.dfl.rrd";
		$rrd_file_u = "$rrdir/${host['iface_up']}-${host['ip']}.rrd";
		$rrd_file_u_prio = "$rrdir/${host['iface_up']}-${host['ip']}.prio.rrd";
		$rrd_file_u_dfl = "$rrdir/${host['iface_up']}-${host['ip']}.dfl.rrd";
		$opts = array("-s", "$start", 
			//"-E",
			#"--lazy", 
			"DEF:in=${rrd_file_d}:sent:AVERAGE", 
			"DEF:in_prio=${rrd_file_d_prio}:sent:AVERAGE", 
			"DEF:in_dfl=${rrd_file_d_dfl}:sent:AVERAGE", 
			"DEF:out=${rrd_file_u}:sent:AVERAGE", 
			"DEF:out_prio=${rrd_file_u_prio}:sent:AVERAGE", 
			"DEF:out_dfl=${rrd_file_u_dfl}:sent:AVERAGE", 
			"CDEF:in_prio_=in_prio,8,*",
			"CDEF:in_dfl_=in_dfl,8,*",
			"CDEF:out_=out,8,*",
			"AREA:in_prio_#00AA00:down", 
			"STACK:in_dfl_#00EE00:down_junk:STACK", 
			"LINE1:out_#FF0000:up", 
			"--interlaced",
			"--upper-limit=10000",
			"--lower-limit=0",
			"--no-legend",
			//"--x-grid", "$x_grid", 
			"--alt-y-grid",
			"--height", "$height", 
			"--width", "$width", 
			"--only-graph",
			"--imginfo", "'<img src=\"$httpdir/img/%s\" width=\%lu height=\%lu alt=\"grafic ${host['ip']}\">'", 
			"--imgformat", "PNG");
		foreach ($opts as $opt) { 
			$string_opts .= $opt." ";
		}
		//$ret = rrd_graph ("$rrdir_img/${host['ip']}-hour-${width}x${height}.png", $opts, count($opts));
		//echo "rrdtool graph ".$rrdir_img."/".$host['ip']."-hour-".$width."x".$height.".png ".$string_opts;
		//$tag = system("rrdtool graph ".$rrdir_img."/".$host['ip']."-hour-".$width."x".$height.".png ".$string_opts." 2>&1", $ret);
		exec("rrdtool graph ".$rrdir_img."/".$host['ip']."-hour-".$width."x".$height.".png ".$string_opts." 2>&1", $tag, $ret);
		//echo print_r($tag);
		if($ret != 0) { 
			//$err = rrd_error();
			echo "rrd_graph error n: $ret - $tag\n";
		}else{
		return $tag[1];
		}
}
function check_ip($ip) {
	global $hosts;
	global $action;
	global $ip_edit;
	echo "edit $ip_edit ip $ip";
	if(isset($hosts[$ip])) {
		if ($ip_edit != $ip) return false;
	}
	return true;
}
function getForm() { 
	global $action;
	global $ip_edit;
	$form = new HTML_QuickForm('hostedit', 'post');
	$form->addElement('header', '', 'Informacion del Usuario');
	$form->addElement('hidden', 'action', $action);
	$form->addElement('hidden', 'ip_edit', $ip_edit);
	$form->addElement('hidden', 'iface_down', 'eth1');
	$form->addElement('hidden', 'iface_up', 'eth0');
	$form->addElement('text', 'name_client', 'Nombre: ');
	$form->addRule('name_client', 'solo letras, numeros y guiones', 'regex', '/^[A-Za-z0-9_-]+$/', 'server');
	$form->addRule('name_client', 'Valor requerido', 'required');
	$form->addRule('name_client', 'Solo 30 caracteres', 'maxlength', 30, 'server');
	$form->addElement('text', 'ip', 'IP: ');
	$form->addRule('ip', 'valor no valido', 'regex', '/^([12]{0,1}[0-9]{0,1}[0-9]{1}\.){3}[12]{0,1}[0-9]{0,1}[0-9]{1}(\/[123]{0,1}[0-9]{1}){0,1}$/', 'server');
	$form->addRule('ip', 'valor requerido', 'required');
	$form->addRule('ip', 'IP duplicada', 'callback', 'check_ip');
	$form->addElement('text', 'ceil_dfl_percent', 'Junk traffic percent: ');
	$form->addRule('ceil_dfl_percent', 'campo numerico ', 'numeric', null, 'server');
	function cero_a_100 ($value) { return ($value >= 0 && $value <= 100);}; 
	$form->addRule('ceil_dfl_percent', 'de 0 a 100', 'callback', 'cero_a_100', 'server');
	$form->addRule('ceil_dfl_percent', 'valor requerido', 'required');
	$form->addElement('text', 'rate_down', 'Granted download rate: ');
	$form->addRule('rate_down', 'campo numerico ', 'numeric', null, 'server');
	$form->addRule('rate_down', 'Solo 5 digitos', 'maxlength', 5, 'server');
	$form->addRule('rate_down', 'valor requerido', 'required');
	$form->addElement('text', 'ceil_down', 'Max download rate: ');
	$form->addRule('ceil_down', 'campo numerico ', 'numeric', null, 'server');
	$form->addRule('ceil_down', 'Solo 5 digitos', 'maxlength', 5, 'server');
	$form->addRule('ceil_down', 'valor requerido', 'required');
	$form->addElement('text', 'rate_up', 'Granted upload rate: ');
	$form->addRule('rate_up', 'campo numerico', 'numeric', null, 'server');
	$form->addRule('rate_up', 'valor requerido', 'required');
	$form->addRule('rate_up', 'Solo 5 digitos', 'maxlength', 5, 'server');
	$form->addElement('text', 'ceil_up', 'Max upload rate: ');
	$form->addRule('ceil_up', 'campo numerico', 'numeric', null, 'server');
	$form->addRule('ceil_up', 'valor requerido', 'required');
	$form->addRule('ceil_up', 'Solo 5 digitos', 'maxlength', 5, 'server');
	$form->addElement('text', 'tcp_prio_ports', 'TCP prio ports: ', 'title="numbers or names as in /etc/services, ie: 22,http,110,imap"');
	$form->addRule('tcp_prio_ports', 'valor no valido', 'regex', '/^([0-9a-z]+,)*[0-9a-z]+$/', 'server');
	$form->addRule('tcp_prio_ports', 'valor requerido', 'required');
	$form->addElement('text', 'udp_prio_ports', 'UDP prio ports: ', 'title="numbers or names as in /etc/services, ie: 53,isakmp"');
	$form->addRule('udp_prio_ports', 'valor no valido', 'regex', '/^([0-9a-z]+,)*[0-9a-z]+$/', 'server');
	$form->addRule('udp_prio_ports', 'valor requerido', 'required');
	$form->addElement('text', 'prio_protos', 'Prio protocols: ', 'title="numbers o names as in /etc/protocols, ie: icmp,gre,50"');
	$form->addRule('prio_protos', 'valor no valido', 'regex', '/^([0-9a-z]+,)*[0-9a-z]+$/', 'server');
	$form->addRule('prio_protos', 'valor requerido', 'required');
	$form->addElement('text', 'prio_helpers', 'Prio helpers: ','title="netfilter helpers, ie: ftp,pptp,irc"');
	$form->addRule('prio_helpers', 'valor no valido', 'regex', '/^([0-9a-z]+,)*[0-9a-z]+$/', 'server');
	$form->addRule('prio_helpers', 'valor requerido', 'required');
	
	$form->addElement('advcheckbox', 'enabled', 'Enabled:', null, null, array('0', '1'));
	//$form->addElement('text', 'mac_address', 'MAC Address: ');
	//$form->addRule('mac_address', 'valor no valido', 'regex', '/^([0-9A-Fa-f]{2}\:){5}[0-9A-Fa-f]{2}$/', 'server');
	//$form->addRule('mac_address', 'valor requerido', 'required');
	$form->addElement('submit', 'submit_hostedit', 'Aceptar');
	return $form;
}

function do_head() {
echo <<<HTML
<html>
<head>
	<title>Cubika Sistema de gestion acceso a Internet</title>
	<style type="text/css">
    table tr td {
        font-size:13px;
		padding-left: 2px ;
		padding-right: 2px ;
		background:#fff;
        }
    table th {
        font-size:13px;
        font-weight: bold;
		}
    input {
        font-size:12px;
        }
	</style>
</head>
<body>
HTML;
}
function do_tail() {
echo <<<HTML
</body>
</html>
HTML;
}

function write_conf($conf_file, $hosts) {
	//echo '<pre>';
	//var_dump($hosts);
	//echo '</pre>';
	$handle = fopen($conf_file, 'w');
	if ($handle) { 
		foreach($hosts as $host) {
			$line = sprintf("%-19s %5s %5s %5s %5s %6s %6s %3s %s %s %s %s %3s %-30s\n", 
							$host['ip'],  
							$host['rate_down'],
							$host['ceil_down'],
							$host['rate_up'],
							$host['ceil_up'],
							$host['iface_down'],
							$host['iface_up'],
							$host['ceil_dfl_percent'],
							$host['tcp_prio_ports'],
							$host['udp_prio_ports'],
							$host['prio_protos'],
							$host['prio_helpers'],
							$host['enabled'],
							$host['name_client']
							);
			fwrite($handle, $line);
		}
		fclose($handle);
		system("sudo /etc/init.d/htb-gen restart");
	} else {
		echo "Error al intentar abrir el archivo de configuracion: $conf_file";
		return false;
	}
}
function parse_conf($conf_file) {
	$handle = fopen($conf_file, 'r+');
	$hosts = array();
	
	if ($handle) { 
		while (!feof($handle)) {
			$line = fgets($handle);
			if (empty($line)) continue;
			if (preg_match('/^\s*#/', $line)) continue;
			if (preg_match('/^\s*$/', $line)) continue;
			$vars = preg_split('/\s+/',$line, -1, PREG_SPLIT_NO_EMPTY);
			//echo var_dump($vars);
			$hosts[$vars[0]] =  array('ip' => $vars[0], 
						  'rate_down' => $vars[1], 
						  'ceil_down' => $vars[2], 
						  'rate_up' => $vars[3], 
						  'ceil_up' => $vars[4], 
						  'iface_down' => $vars[5], 
						  'iface_up' => $vars[6], 
						  'ceil_dfl_percent' => $vars[7], 
						  'tcp_prio_ports' => $vars[8],
						  'udp_prio_ports' => $vars[9],
						  'prio_protos' => $vars[10],
						  'prio_helpers' => $vars[11],
						  'enabled' => $vars[12],
						  'name_client' => $vars[13],
						  );
		}
		fclose($handle);
		return $hosts;
	} else {
		echo "Error al intentar abrir el archivo de configuración: $conf_file";
		return false;
	}
}
?>
