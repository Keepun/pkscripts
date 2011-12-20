<?php
/**
 * Portage information query tool
 * Version: 2.1.10.39
 *
 * Read "man 5 ebuild" for info about ATOMs.
 *
 * WARNING!!! I don't full test class!!!
 */
class PortageqClass
{
	public $EROOT;
	public $PORTDIR;
	public $PKGDIR;
	
	function __construct()
	{
		list($this->EROOT, $this->PORTDIR, $this->PKGDIR) = explode("\n", trim(`portageq envvar EROOT PORTDIR PKGDIR`));
	}
	
	/**
	 * Returns all best_visible packages (without .ebuild).
	 * @return array
	 */
	function all_best_visible()
	{
		return explode("\n", trim(`portageq all_best_visible {$this->EROOT}`));
	}
	
	/**
	 * Returns category/package-version (without .ebuild).
	 * @param string $category_package
	 * @return string
	 */
	function best_version($category_package)
	{
		return trim(`portageq best_version {$this->EROOT} $category_package`);
	}
	
	/**
	 * Returns category/package-version (without .ebuild).
	 * @param string $atom
	 * @param string $pkgtype ebuild|binary|installed
	 * @return string
	 */
	function best_visible($atom, $pkgtype = 'ebuild')
	{
		return trim(`portageq best_visible {$this->EROOT} $pkgtype $atom`);
	}
	
	/**
	 * Returns the CONFIG_PROTECT paths.
	 * @return string
	 */
	function config_protect()
	{
		return trim(`portageq config_protect`);
	}
	
	/**
	 * Returns the CONFIG_PROTECT_MASK paths.
	 * @return string
	 */
	function config_protect_mask()
	{
		return trim(`portageq config_protect_mask`);
	}
	
	/**
	 * List the files that are installed
	 * for a given package, with one file
	 * listed on each line.
	 *
	 * All file names will begin with EROOT.
	 *
	 * @param string $category_package
	 * @return array
	 */
	function contents($category_package)
	{
		return explode("\n", trim(`portageq contents {$this->EROOT} $category_package`));
	}
	
	/**
	 * Returns the DISTDIR path.
	 * @return string
	 */
	function distdir()
	{
		return trim(`portageq distdir`);
	}
	
	/**
	 * Returns a specific environment variable as exists prior to ebuild.sh.
	 * Similar to: emerge --verbose --info | egrep '^<variable>='
	 * @param string $variables
	 * @return array
	 */
	function envvar($variables)
	{
		return explode("\n", trim(`portageq envvar $variables`));
	}
	
	/**
	 * Returns a list of atoms expanded from a
	 * given virtual atom (GLEP 37 virtuals only),
	 * excluding blocker atoms. Satisfied
	 * virtual atoms are not included in the output, since
	 * they are expanded to real atoms which are displayed.
	 *
	 * Unsatisfied virtual atoms are displayed without
	 * any expansion. The "match" command can be used to
	 * resolve the returned atoms to specific installed
	 * packages.
	 *
	 * @param string $atom
	 * @return array
	 */
	function expand_virtual($atom)
	{
		return explode("\n", trim(`portageq expand_virtual {$this->EROOT} $atom`));
	}
	
	/**
	 * Read filenames from stdin and write them to stdout if they are protected.
	 * All filenames are delimited by \n and must begin with EROOT.
	 */
	/*function filter_protected()
	{
		//portageq filter_protected {$this->EROOT}
	}*/
	
	/**
	 * Returns the mirrors set to use in the portage configuration.
	 * @return array
	 */
	function gentoo_mirrors()
	{
		return explode("\n", trim(`portageq gentoo_mirrors`));
	}
	
	/**
	 * Returns the path to the repo named argv[1], argv[0] = EROOT
	 * @param string $repo_idS
	 * @return string
	 */
	function get_repo_path($repo_idS)
	{
		return trim(`portageq get_repo_path {$this->EROOT} $repo_idS`);
	}
	
	/**
	 * Returns all repos with names (repo_name file) argv[0] = EROOT
	 * @return string
	 */
	function get_repos()
	{
		return trim(`portageq get_repos {$this->EROOT}`);
	}
	
	/**
	 * @param string $category_package
	 * @return bool
	 */
	function has_version($category_package)
	{
		exec("portageq has_version {$this->EROOT} $category_package", $out, $retval);
		return ($retval==0?true:false);
	}
	
	/**
	 * Given a single filename.
	 * The filename must begin with EROOT.
	 * @param string $filename
	 * @return bool
	 */
	function is_protected($filename)
	{
		exec("portageq is_protected {$this->EROOT} $filename", $out, $retval);
		return ($retval==0?true:false);
	}
	
	/**
	 * Returns category/package-version (without .ebuild).
	 * @param string $category_packageS
	 * @return array
	 */
	function mass_best_version($category_packageS)
	{
		return explode("\n", trim(`portageq mass_best_version {$this->EROOT} $category_packageS`));
	}
	
	/**
	 * Returns category/package-version (without .ebuild).
	 * @param string $category_packageS
	 * @return array
	 */
	function mass_best_visible($category_packageS)
	{
		return explode("\n", trim(`portageq mass_best_visible {$this->EROOT} $category_packageS`));
	}
	
	/**
	 * Returns a list of category/package-version.
	 * When given an empty string, all installed packages will be listed.
	 * @param string $atom
	 * @return array
	 */
	function match($atom)
	{
		return explode("\n", trim(`portageq match {$this->EROOT} $atom`));
	}
	
	/**
	 * Returns metadata values for the specified package.
	 * @param string $category_package
	 * @param string $keyS DEFINED_PHASES DEPEND DESCRIPTION EAPI HOMEPAGE INHERITED IUSE KEYWORDS LICENSE PDEPEND PROPERTIES PROVIDE RDEPEND REQUIRED_USE RESTRICT SLOT SRC_URI
	 * @param string $pkgtype ebuild|binary|installed
	 * @return array
	 */
	function metadata($category_package, $keyS, $pkgtype = 'ebuild')
	{
		return explode("\n", trim(`portageq metadata {$this->EROOT} $pkgtype $category_package $keyS`));
	}
	
	/**
	 * Given a list of files, print the packages that own the files and which
	 * files belong to each package. Files owned by a package are listed on
	 * the lines below it, indented by a single tab character (\t). All file
	 * paths must either start with {$this->EROOT} or be a basename alone.
	 * Returns 1 if no owners could be found, and 0 otherwise.
	 */
	/*function owners($filenameS)
	{
		return trim(`portageq owners {$this->EROOT} $filenameS`);
	}*/

	/**
	 * Returns the PKGDIR path.
	 * Use $PKGDIR !!!
	 * @return string
	 */
	function pkgdir()
	{
		return trim(`portageq pkgdir`);
	}
	
	/**
	 * Returns the PORTDIR path.
	 * Use $PORTDIR !!!
	 * @return string
	 */
	function portdir()
	{
		return trim(`portageq portdir`);
	}
	
	/**
	 * Returns the PORTDIR_OVERLAY path.
	 * @return string
	 */
	function portdir_overlay()
	{
		return trim(`portageq portdir_overlay`);
	}
	
	/**
	 * Returns the path used for the
	 * var(installed) package database for the
	 * set environment/configuration options.
	 * @return string
	 */
	function vdb_path()
	{
		return trim(`portageq vdb_path`);
	}
}

$Portageq = new PortageqClass();
