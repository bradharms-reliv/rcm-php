<?php

namespace Rcm\View\Helper;

use Rcm\Entity\Page;
use Rcm\Entity\Revision as RevisionEntity;
use Zend\View\Helper\AbstractHelper;

/**
 * Rcm Container View Helper
 *
 * Rcm Container View Helper.  This helper will render plugin containers.  Use this
 * in your views to define a plugin container.
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 */
class RevisionHelper extends AbstractHelper
{
    /**
     * __invoke
     *
     * @return $this
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * getRevisionType
     *
     * @param Page           $page
     * @param RevisionEntity $revision
     *
     * @return string
     */
    public function getRevisionType(Page $page, RevisionEntity $revision)
    {
        $publishedId = $page->getPublishedRevision();
        $stagedId = $page->getStagedRevision();

        if (!empty($publishedId) && $publishedId->getRevisionId() == $revision->getRevisionId()) {
            return 'Published';
        } elseif (!empty($stagedId) && $stagedId->getRevisionId() == $revision->getRevisionId()) {
            return 'Staged';
        }

        return 'Draft';
    }

    /**
     * getRevisionLink
     *
     * @param Page           $page
     * @param RevisionEntity $revision
     *
     * @return string
     */
    public function getRevisionLink(Page $page, RevisionEntity $revision)
    {
        $view = $this->getView();

        $revisionId = $revision->getRevisionId();

        $publishedId = $page->getPublishedRevision();
        $stagedId = $page->getStagedRevision();

        if ((!empty($publishedId) && $publishedId->getRevisionId() == $revision->getRevisionId())
            || (!empty($stagedId) && $stagedId->getRevisionId() == $revision->getRevisionId())
        ) {
            $revisionId = null;
        }

        $html = '<a href="'.$view->urlToPage($page->getName(), $page->getPageType(), $revisionId).'">';
        $html .= $this->getRevisionType($page, $revision).' - '.$revision->getAuthor();
        $html .= '</a>';

        return $html;
    }
}
