<?php
/**
 * Redirect To Page Controller Plugin
 *
 * This file contains the Redirect To Page Controller Plugin.
 *
 * PHP version 5.3
 *
 * LICENSE: No License yet
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      http://github.com/reliv
 */
namespace Rcm\Controller\Plugin;

use Rcm\Acl\CmsPermissionChecks;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Redirect To Page Controller Plugin
 *
 * Redirect To Page Controller Plugin.  This plugin is used to redirect a user
 * to a CMS page by sending the URL to the page and the page type of that page.
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class ShouldShowRevisions extends AbstractPlugin
{
    /** @var \Rcm\Acl\CmsPermissionChecks  */
    public $checker;

    /**
     * ShouldShowRevisions constructor.
     *
     * @param CmsPermissionChecks $cmsPermissionChecks
     */
    public function __construct(CmsPermissionChecks $cmsPermissionChecks)
    {
        $this->checker = $cmsPermissionChecks;
    }

    /**
     * Redirect to a page
     *
     * @param string $siteId   Site Id
     * @param string $pageName Page Name
     * @param string $pageType Page Type
     *
     * @return \Zend\Http\Response
     */
    public function __invoke($siteId, $pageName, $pageType = 'n')
    {
        return $this->checker->shouldShowRevisions($siteId, $pageName, $pageType);
    }
}
