<?php

/**
 * My variant getopt()
 * @param string $options
 * @param array $longopts
 * @param array $argv =$_SERVER['argv'] without [0]
 * @param bool $combine as a standard getopt() for PHP
 * @return bool|array if error === false
 */
function _getopt($options, $longopts=null, $argv=null, $combine=true)
{
	if (!is_string($options)) return false;
	if ($longopts && !is_array($longopts)) return false;
	elseif (!$longopts) $longopts=array();
	if (!$argv)
	{
		$argv=array();
		for ($x=1;$x<count($_SERVER['argv']);$x++) $argv[]=$_SERVER['argv'][$x];
	}
	elseif (!is_array($argv)) return false;

	$_options=array();
	foreach (str_split($options) as $opt)
	{
		$last=count($_options)-1;
		if ($opt==':')
			if (!isset($_options[$last])) return false;
			elseif ($_options[$last][1] == 2) return false;
			else $_options[$last][1]++;
		else $_options[]=array($opt,0);
	}

	$_longopts=array();
	foreach ($longopts as $opt)
	{
		$colon=strpos($opt, ':');
		if ($colon !== false)
			if ($colon == 0 || $colon < strlen($opt)-2) return false;
			else $_longopts[]=array(substr($opt,0,$colon), strlen($opt)-$colon);
		else $_longopts[]=array($opt,0);
	}
	
	$result=array();
	$need_arg=array(false,0);
	for ($x=0;$x<count($argv);$x++)
	{
		if ($argv[$x] == '--')
		{
			for ($x++;$x<count($argv);$x++) $result[]=array('--', $argv[$x]);
			break;
		}
		elseif (preg_match_all("/^(\-+)([^=]+)(=.+)?$/", $argv[$x], $match))
		{
			if ($need_arg[0])
			{
				if ($need_arg[1] == 2) return false;
				$need_arg[0]=false;
			}

			if ($match[1][0] == '--')
			{
				$opt=null;
				foreach ($_longopts as $op) if ($op[0] == $match[2][0]) {$opt=$op; break;}
				if (!$opt) foreach ($_options as $op) if ($op[0] == $match[2][0]) {$opt=$op; break;}
				if (!$opt) return false;
				if ($opt[1] == 0 && strlen($match[3][0]) > 0) return false;

				if ($opt[1] == 0) $result[]=array($opt[0], false);
				else
				{
					if (strlen($match[3][0]) > 1)
					{
						$quote=$match[3][0][1];
						if ($quote == '"' || $quote == "'")
							$result[]=array($opt[0], substr($match[3][0],2,-1));
						else $result[]=array($opt[0], substr($match[3][0],1));
					}
					else
					{
						$result[]=array($opt[0], false);
						$need_arg=array(true, $opt[1]);
					}
				}
			}
			elseif ($match[1][0] == '-')
			{
				$params=str_split($match[2][0]);
				$arg=array();
				for ($y=$x;$y>=0;$y--) $arg[]=0;
				foreach ($params as $p => $param)
				{
					$opt=null;
					foreach ($_options as $op) if ($op[0] == $param) {$opt=$op; break;}
					if (!$opt) return false;
					if ($opt[1] == 0) $arg[]='--'.$opt[0];
					else
					{
						if ($p < count($params)-1)
						{
							if (strlen($match[3][0]) > 1) return false;
							$arg[]='--'.$opt[0].'="'.substr($match[2][0], $p+1).'"';
							break;
						}
						elseif (strlen($match[3][0]) > 1) $arg[]='--'.$opt[0].$match[3][0];
						else $arg[]='--'.$opt[0];
					}
				}
				for ($y=$x+1;$y<count($argv);$y++) $arg[]=$argv[$y];
				$argv=$arg;
			}
			else return false;
		}
		elseif ($need_arg[0])
		{
			$result[count($result)-1][1]=$argv[$x];
			$need_arg[0]=false;
		}
		else $result[]=array('-', $argv[$x]);
	}
	
	if ($combine)
	{
		$temp=array();
		foreach ($result as $res)
		{
			if (!isset($temp[$res[0]])) $temp[$res[0]]=array();
			$temp[$res[0]][]=$res[1];
		}
		foreach ($temp as &$tmp)
			if (is_array($tmp) && count($tmp) == 1) $tmp=$tmp[0];
		return $temp;
	}
	return $result;
}

/*
$test=array('-abc','-d','media-font/*','-bdc445','-d','--help','--desc="gnome"','--emerge','cmd-emerge','-e="new cmd"','any-atom','--','-a="fc*["','m*?s');
var_dump($test, _getopt('abcd:e::', array('help','desc:','emerge::'), $test));
 */
