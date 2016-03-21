<?php

namespace Rcm\Validator;

use Rcm\Entity\Site;
use Rcm\Service\LayoutManager;
use Zend\Validator\AbstractValidator;

/**
 * Rcm Main Layout Validator
 *
 * Rcm Main Layout Validator. This validator will verify that the Main layout
 * exists.
 *
 * @category  Reliv
 * @package   Rcm
 * @author    Westin Shafer <wshafer@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: 1.0
 * @link      http://github.com/reliv
 *
 */
class MainLayout extends AbstractValidator
{
    /**
     * MAIN_LAYOUT
     */
    const MAIN_LAYOUT = 'pageTemplate';

    /**
     * @var array
     */
    protected $messageTemplates
        = [
            self::MAIN_LAYOUT => "'%value%' is not a valid layout."
        ];

    /**
     * @var \Rcm\Entity\Site
     */
    protected $currentSite;

    /**
     * @var \Rcm\Service\LayoutManager
     */
    protected $layoutManager;

    /**
     * Constructor
     *
     * @param Site          $currentSite   Rcm Current Site
     * @param LayoutManager $layoutManager Rcm Layout Manager
     */
    public function __construct(
        Site $currentSite,
        LayoutManager $layoutManager
    ) {
        $this->currentSite = $currentSite;
        $this->layoutManager = $layoutManager;

        parent::__construct();
    }

    /**
     * Is the layout valid?
     *
     * @param string $value Page to validate
     *
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (!$this->layoutManager->isLayoutValid($this->currentSite, $value)) {
            $this->error(self::MAIN_LAYOUT);

            return false;
        }

        return true;
    }
}
