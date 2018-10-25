<?php

namespace RcmAdmin\Controller;

use Rcm\Acl\ResourceName;
use Rcm\Http\Response;
use Rcm\View\Model\ApiJsonModel;
use RcmUser\Service\RcmUserService;
use Zend\View\Model\JsonModel;

/**
 * Class ApiAdminLanguageController
 *
 * API for Rcm Language
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmAdmin\Controller
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2017 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */

class ApiAdminLanguageController extends ApiAdminBaseController
{

    /**
     * getList
     *
     * @return mixed|JsonModel
     */
    public function getList()
    {
        /** @var RcmUserService $rcmUserService */
        $rcmUserService = $this->serviceLocator->get(RcmUserService::class);

        //ACCESS CHECK
        if (!$rcmUserService->isAllowed(
            ResourceName::RESOURCE_SITES,
            'admin'
        )
        ) {
            $this->getResponse()->setStatusCode(Response::STATUS_CODE_401);
            return $this->getResponse();
        }

        /** @var \Rcm\Repository\Language $languageRepo */
        $languageRepo = $this->getEntityManager()->getRepository(\Rcm\Entity\Language::class);

        try {
            $languages = $languageRepo->findBy([], ['languageName' => 'ASC']);
        } catch (\Exception $e) {
            return new ApiJsonModel(null, 1, 'An error occurred will getting languages.');
        }

        return new ApiJsonModel($languages, 0, 'Success');
    }
}
