<?php

require_once(__DIR__.'/portageq.class.php');
require_once(__DIR__.'/global.php');

$_HELP=false;
$_FULLTREE=false;
$_DEPTH=0;
$_COLOR=true;
$_VERBOSE=false;
$_DESC=false;
$_EXEC=false;
$_NEWFILE=false;
$_ONLYPK=false;
$_MASK=false;
$_EMERGE='';
$_EMERGE_TEST='--unordered-display --color=y --autounmask=y';
$_EMERGE_SETUP='--color=y --autounmask=y';

$_args=getopt('hfd::cvnp', array('help','full','depth::','nocolor','verbose','desc','exec','new','pk','mask','test','emerge::','emerge-test::','emerge-setup::'));
foreach ($_args as $arg => $val)
{
	switch ($arg)
	{
		case 'h':
		case 'help':
			$_HELP=true; break;
		case 'f':
		case 'full':
			$_FULLTREE=true; break;
		case 'd':
		case 'depth':
			$_DEPTH=intval($val); break;
		case 'c':
		case 'nocolor':
			$_COLOR=false; break;
		case 'v':
		case 'verbose':
			$_VERBOSE=true; break;
		case 'desc':
			$_DESC=true; break;
		case 'exec':
			$_EXEC=true; break;
		case 'n':
		case 'new':
			$_NEWFILE=true; break;
		case 'p':
		case 'pk':
			$_ONLYPK=true; break;
		case 'mask':
			$_MASK=true; break;
		case 'test':
			$_ONLYPK=true; $_EXEC=true; break;
		case 'emerge':
			if (is_array($val)) $_EMERGE=implode(' ', $val);
			else $_EMERGE=$val;
			break;
		case 'emerge-test':
			if (is_array($val)) $_EMERGE_TEST=implode(' ', $val);
			else $_EMERGE_TEST=$val;
			break;
		case 'emerge-setup':
			if (is_array($val)) $_EMERGE_SETUP=implode(' ', $val);
			else $_EMERGE_SETUP=$val;
			break;
	}
}

if ($_HELP)
{
$help=<<<HELP
    -h --help    this help
    -f --full    show all depends
    -d --depth   depth (need number)
    -c --nocolor no colors
    -v --verbose show flags and etc.
       --desc    show descriptions.
    -n --new     new script from this
    -p --pk      only \$pk for Emerge
       --exec    run Emerge
       --mask    add masks in /etc/portage/package.mask

       --test    run Emerge for test (-p --exec)

Emerge:
run `emerge -p EMERGE_TEST EMERGE atoms` for test
run `emerge    EMERGE_SETUP EMERGE atoms` for setup
    --emerge=""
    --emerge-test="{$_EMERGE_TEST}"
    --emerge-setup="{$_EMERGE_SETUP}"

HELP;
	die($help);
}

$_result=array();
function result($level=1, $start=0)
{
	global $pk, $_pkgs_all, $_result;
	$y=$start;
	$root_status=0; // 0 - default, 1 - setup, 2 - error, 3 - exclude
	for (; $y<count($_pkgs_all); $y++)
	{
		if ($level < $_pkgs_all[$y][0])
		{
			list($_y,$_status) = result($level+1, $y);
			if ($_status>1 && $root_status<2) $root_status=2;
			if (!isset($_result[$y-1]) || $_result[$y-1]<$_status) $_result[$y-1]=$_status;
			$y=$_y-1;
			continue;
		}
		else if ($level > $_pkgs_all[$y][0]) break;
		else
		{
			foreach ($pk as $_p)
			{
				if (isset($_p['S']) && $_p['S'] == $_pkgs_all[$y][1])
				{
					$_result[$y]=1;
					//if ($root_status<2) $root_status=1;
					break;
				}
				else if (isset($_p['M']) && $_p['M'] == $_pkgs_all[$y][1])
				{
					$_result[$y]=3;
					$root_status=2;
					break;
				}
			}
		}
	}
	return array($y,$root_status);
}

function text($level=0, $start=0)
{
	global $_pkgs_all, $_result, $_FULLTREE, $_DEPTH, $_COLOR, $_VERBOSE, $_DESC;
	$y=$start;
	$status=0; // 0 - default, 1 - setup, 2 - error, 3 - exclude
	if (isset($_result[$start-1])) $status=$_result[$start-1];
	for (; $y<count($_pkgs_all); $y++)
	{
		if ($level < $_pkgs_all[$y][0])
		{
			$y = text($level+1, $y) - 1;
			continue;
		}
		else if ($level > $_pkgs_all[$y][0]) break;
		else
		{
			$_p=$_pkgs_all[$y];
			if (isset($_result[$y]) && $_result[$y]>0)
			{
				if ($_result[$y]==3) print $_p[0].str_repeat('.', $_p[0]-strlen($_p[0])).($_COLOR?"\033[31m":'').'# '.$_p[1].($_VERBOSE && $_p[0]>1?($_COLOR?" \033[36m":' ').$_pkgs_all[$start-1][1].($_COLOR?" \033[33m":' ').findDepens($_pkgs_all[$start-1][1],$_p[1]):'').($_DESC?($_COLOR?" \033[35m":'').pmetadata($_p[1],'DESCRIPTION'):'').($_COLOR?"\033[0m":'')."\n";
				else if ($_result[$y]==2) print $_p[0].str_repeat('.', $_p[0]-strlen($_p[0])).($_COLOR?"\033[31m":'').'! '.$_p[1].($_VERBOSE && $_p[0]>1?($_COLOR?" \033[36m":' ').$_pkgs_all[$start-1][1].($_COLOR?" \033[33m":' ').findDepens($_pkgs_all[$start-1][1],$_p[1]):'').($_DESC?($_COLOR?" \033[35m":'').pmetadata($_p[1],'DESCRIPTION'):'').($_COLOR?"\033[0m":'')."\n";
				else if ($_result[$y]==1) print $_p[0].str_repeat('.', $_p[0]-strlen($_p[0])).($_COLOR?"\033[32m":'').'+ '.$_p[1].($_VERBOSE && $_p[0]>1?($_COLOR?" \033[36m":' ').$_pkgs_all[$start-1][1].($_COLOR?" \033[33m":' ').findDepens($_pkgs_all[$start-1][1],$_p[1]):'').($_DESC?($_COLOR?" \033[35m":'').pmetadata($_p[1],'DESCRIPTION'):'').($_COLOR?"\033[0m":'')."\n";
			}
			else if ($status==2) print $_p[0].str_repeat('.', $_p[0]-strlen($_p[0])).($_COLOR?"\033[32m":'').'@ '.$_p[1].($_VERBOSE && $_p[0]>1?($_COLOR?" \033[36m":' ').$_pkgs_all[$start-1][1].($_COLOR?" \033[33m":' ').findDepens($_pkgs_all[$start-1][1],$_p[1]):'').($_DESC?($_COLOR?" \033[35m":' ').pmetadata($_p[1],'DESCRIPTION'):'').($_COLOR?"\033[0m":'')."\n";
			else if ($_FULLTREE && ($_DEPTH==0 || ($_DEPTH>0 && $_DEPTH>=$_p[0]))) print $_p[0].str_repeat('.', $_p[0]-strlen($_p[0])).'- '.$_p[1]."\n";
		}
	}
	return $y;
}

$_command='';
$_command_ex='';
function command($level=0, $start=0)
{
	global $_pkgs_all, $_result, $_command, $_command_ex;
	$y=$start;
	$status=0; // 0 - default, 1 - setup, 2 - error, 3 - exclude
	if (isset($_result[$start-1])) $status=$_result[$start-1];
	for (; $y<count($_pkgs_all); $y++)
	{
		if ($level < $_pkgs_all[$y][0])
		{
			$y = command($level+1, $y) - 1;
			continue;
		}
		else if ($level > $_pkgs_all[$y][0]) break;
		else
		{
			$_p=$_pkgs_all[$y];
			if (isset($_result[$y]) && $_result[$y]>0)
			{
				if ($_result[$y]==3)
				{
					$info=0;
					if (($info = atomInfo($_p[1])) !== false)
						$_command_ex.=' --exclude '.$info['category'].'/'.$info['name'];
					else print "Warning: info for {$_p[1]} not found.\n";
				}
				else if ($_result[$y]==1) $_command.=' ='.$_p[1];
			}
			else if ($status==2) $_command.=' ='.$_p[1];
		}
	}
	return $y;
}

if ($_MASK)
{
	@mkdir('/etc/portage', 755);
	foreach ($pk as $_pk)
		foreach ($_pk as $k => $_p)
	{
		if ($k == 'M') system("echo \">=$_p\" >> /etc/portage/package.mask");
	}
}

if ($_NEWFILE)
{
	if (empty($pkmerge)) die("Not set \$pkmerge\n");
	$_pkmerge = array();
	exec($pkmerge, $_pkmerge);
	$pathscript = trim(substr($_pkmerge[count($_pkmerge)-1],5));
	if (!file_exists($pathscript)) die("File $pathscript not found!\n");
	if (count($pk)==0) die("Nothing \$pk.\nNew $pathscript\n");
	$fscrin=fopen($pathscript,'r') or die("File $pathscript not open!\n");;
	$fscrout=fopen($pathscript.'_new','w') or die("File {$pathscript}_new not create!\n");
	while (($buf=fgets($fscrin)) !== false)
	{
		$found=false;
		foreach ($pk as $_pk)
			foreach ($_pk as $k => $_p)
		{
			if (preg_match('/(\$pk\[\]\[)[^\]]+(\][^\"]+\")'.preg_quote($_p,'/').'(\";.*)$/', $buf, $match))
			{
				fwrite($fscrout, ' '.$match[1].$k.$match[2].$_p.$match[3]."\n");
				$found=true;
				break;
			}
		}
		if (!$found) fwrite($fscrout, $buf);
	}
	fclose($fscrin);
	fclose($fscrout);
	if (!rename($pathscript.'_new',$pathscript)) die("Can't rename {$pathscript}_new to $pathscript\n");
	chmod($pathscript, 0777);
	die("New script $pathscript\n");
}

foreach ($pk as $_pk)
	foreach ($_pk as $k => $_p)
{
	print "$k $_p\n";
}
print "\n";

result();

if ($_ONLYPK)
{
	command();
	$_command='emerge -p '.$_EMERGE_TEST.' '.$_command.$_command_ex;
	print $_command."\n\n";
	if ($_EXEC) system($_command);
	die();
}

if ($_EXEC)
{
	command();
	$_command='emerge '.$_EMERGE_SETUP.' '.$_command.$_command_ex;
	system($_command);
	die();
}

text();
