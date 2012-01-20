<?php

/**
 * $Portageq->Metadata()
 * @param string $category_package
 * @param string $keyS
 * @return string
 */
function pmetadata($category_package, $keyS)
{
	global $Portageq;
	return implode('', $Portageq->metadata($category_package, $keyS));
}

/**
 * Search package and parse 'category', 'name', 'version'.
 * @param string $category_package
 * @param bool $one get only first
 * @return bool|array if error === false
 */
function atomInfo($category_package, $one=true)
{
	global $Portageq;
	$category_package='"*'.str_replace('/','/*/',$category_package).'.ebuild"';
	$find=explode("\n", trim(`find {$Portageq->PORTDIR} -path "$category_package"`));
	if (empty($find[0])) return false;
	$info=array();

	if ($one)
	{
	    $atom=explode('/',substr($find[0], strlen($Portageq->PORTDIR)+1));
	    if (count($find) > 1 || count($atom) < 2) return false; // invariable =1
	    $info['category']=$atom[0];
	    $info['name']=$atom[1];
	    $info['version']=substr($atom[2], strlen($atom[1])+1, -7 /*strlen('.ebuild')*(-1)*/);
	    return $info;
	}

	$last='';
	foreach ($find as $file)
	{
	    $atom=explode('/',substr($file, strlen($Portageq->PORTDIR)+1));
	    if (count($atom) < 2 || $last == ($atom[0].'/'.$atom[1])) continue;
	    $last=($atom[0].'/'.$atom[1]);
	    $temp=array();
	    $temp['category']=$atom[0];
	    $temp['name']=$atom[1];
	    $temp['version']=substr($atom[2], strlen($atom[1])+1, -7 /*strlen('.ebuild')*(-1)*/);
	    $info[]=$temp;
	}
	return $info;
}

/**
 * Parse list *DEPEN
 * !recursive!
 * @param array &$ar for result
 * @param array &$all for matchs
 * @return count($all) always
 */
function parseDepens(&$ar, &$all, $level=0)
{
	$y=$level;
	for (; $y<count($all);$y++)
	{
		if ($all[$y]=='(')
		{
			$ar[count($ar)-1]=array($ar[count($ar)-1],array());
			$y=parseDepens($ar[count($ar)-1][1],$all,$y+1);
			continue;
		}
		else if ($all[$y]==')')
		{
			break;
		}
		else
		{
			$ar[]=$all[$y];
		}
	}
	return $y;
}

	/**
	 * !recursive!
	 * @param array &$atom_info what search?
	 * @param array &$match from parseDepens()
	 * @param array &$result for result
	 * @return false always
	 */
	function _depens(&$atom_info, &$match, &$result, $level=0)
	{
		for ($y=1; $y<count($match); $y++)
		{
			if (is_array($match[$y]))
			{
				if (_depens($atom_info, $match[$y], $result, $level+1))
				{
					$result[count($result)-1] .= ($level==0?')':$match[0].',');
					if ($level>0) return true; // Here may be global stop (not 'if') ?..
					else $result[]='';
				}
			}
			else if (preg_match('/^(?<compare>[^\w\d\-]+)?(?<category>[\w\-\*]+\/)?(?<name>[\w\-\.\*]+)(?<slot>\:[^\[]+)?(?<flag>\[[^\]]+\])?$/', $match[$y], $pk))
			{
				// Easy compare atom (not use '*')
				if (!empty($pk['category']) && $pk['category']!='*/')
				{
					if ($pk['category'] != $atom_info['category'].'/') continue;
				}
				if (strpos($pk['name'], $atom_info['name']) !== false)
				{
					$result[count($result)-1] = $match[$y].($level>0?'(':'');
					return true;
				}
			}
		}
		return false;
	}
/**
 * Search depens.
 * @param array $atom must be without '<>=!' (compare) !!!
 * @param array $depend_atom must be without '<>=!' (compare) !!!
 * @return string|bool 'atom[ or atom]...' | if error === false
 */
function findDepens($atom, $depend_atom)
{
	// $atom must be without '<>=!' (compare) !!!
	if (!is_array($atom) && ($atom=atomInfo($atom)) === false) return false;
	if (!is_array($depend_atom) && ($depend_atom=atomInfo($depend_atom)) === false) return false;
	
	global $Portageq;
	$category_package = $atom['category'].'/'.$atom['name'].'-'.$atom['version'];
	$depends = implode('', $Portageq->metadata($category_package, 'DEPEND RDEPEND PDEPEND'));
	if (empty($depends)) return false;
	
	$depends = str_replace('(',' ( ',$depends);
	$depends = str_replace(')',' ) ',$depends);
	$depends = str_replace(' ( + ) ','(+)',$depends);
	$depends = str_replace(' ( - ) ','(-)',$depends);
	
	$depends = preg_split("/\s+/",$depends,-1,PREG_SPLIT_NO_EMPTY);
	
	$match=array();
	parseDepens($match, $depends);
	
	$result=array('');
	_depens($depend_atom, $match, $result);
	return implode(' or ', $result);
}
