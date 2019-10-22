<?php

# This file is a part of RackTables, a datacenter and server room management
# framework. See accompanying file "COPYING" for the full copyright and
# licensing information.

function ios15ReadLLDPStatus ($input)
{
	$ret = array();
	foreach (explode ("\n", $input) as $line)
	{
		$matches = array();
		switch (TRUE)
		{
		case preg_match ('/^Local Intf: (.+)$/', $line, $matches):
			$ret['current']['local_port'] = shortenIfName ($matches[1]);
			break;
		case preg_match ('/^[Pp]ort [Ii][Dd]: (.+)$/', $line, $matches):
			$ret['current']['remote_port'] = $matches[1];
			break;
		case preg_match ('/^[Pp]ort [Dd]escription: (.+)$/', $line, $matches):
			$ret['current']['port_descr'] = $matches[1];
			break;
		case preg_match ('/^[Ss]ystem [Nn]ame: (.+)$/', $line, $matches):
			$ret['current']['sys_name'] = $matches[1];
			if
			(
				array_key_exists ('current', $ret) &&
				array_key_exists ('local_port', $ret['current']) &&
				array_key_exists ('port_descr', $ret['current']) &&
				array_key_exists ('sys_name', $ret['current']) &&
				array_key_exists ('remote_port', $ret['current'])
			)
			{
				$port = NULL;
				if (preg_match ('/^[a-f0-9]{4}.[a-f0-9]{4}.[a-f0-9]{4}$/',$ret['current']['remote_port'], $matches))
					$port = $ret['current']['port_descr'];
				else
					$port = $ret['current']['remote_port'];
				if (isset ($port))
					$ret[$ret['current']['local_port']][] = array
					(
						'device' => $ret['current']['sys_name'],
						'port' => $port,
					);
			}
			unset ($ret['current']);
			break;
		default:
		}
	}
	unset ($ret['current']);
	return $ret;
}

// most of the commands are compatible with IOS12, so are generated by ios12TranslatePushQueue
// Only ios15-specific commands are generated here (eg., lldp)
function ios15TranslatePushQueue ($dummy_object_id, $queue, $dummy_vlan_names)
{
	$ret = '';
	foreach ($queue as $cmd)
		switch ($cmd['opcode'])
		{
		case 'getlldpstatus':
			$ret .= "show lldp neighbors detail | i Local Intf:|Chassis id:|Port id:|Port Description:|System Name:\n";
			break;
		default:
			$ret .= ios12TranslatePushQueue ($dummy_object_id, array ($cmd), $dummy_vlan_names);
			break;
		}
	return $ret;
}

function ios15ShortenIfName_real ($ifname)
{
	$ifname = preg_replace ('@^FastEthernet(.+)$@', 'fa\\1', $ifname);
	$ifname = preg_replace ('@^GigabitEthernet(.+)$@', 'gi\\1', $ifname);
	$ifname = preg_replace ('@^TenGigabitEthernet(.+)$@', 'te\\1', $ifname);
	$ifname = preg_replace ('@^po([0-9]+)$@i', 'port-channel\\1', $ifname);
	$ifname = strtolower ($ifname);
	$ifname = preg_replace ('/^(fa|gi|te|po)\s+(\d.*)/', '$1$2', $ifname);
	return $ifname;
}
