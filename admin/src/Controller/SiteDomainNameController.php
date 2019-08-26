<?php

namespace RcmAdmin\Controller;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rcm\Entity\Site;
use Rcm\ImmutableHistory\VersionRepositoryInterface;
use Rcm\SecureRepo\PageSecureRepo;
use Rcm\SecureRepo\SiteSecureRepo;
use RcmUser\Api\Authentication\GetIdentity;
use Zend\Diactoros\Response\JsonResponse;
use \Zend\Http\Response;
use \Zend\Mvc\Controller\AbstractRestfulController;
use RcmUser\Api\Acl\IsAllowed;

class SiteDomainNameController implements MiddlewareInterface
{
    protected $currentSite;

    protected $isAllowed;

    protected $siteSecureRepo;

    protected $getIdentity;

    public function __construct(
        Site $currentSite,
        IsAllowed $isAllowed,
        ContainerInterface $requestContext,
        GetIdentity $getIdentity
    ) {
        $this->currentSite = $currentSite;
        $this->isAllowed = $isAllowed;
        $this->siteSecureRepo = $requestContext->get(PageSecureRepo::class);
        $this->getIdentity = $getIdentity;
    }

    /**
     * @example of changing the domain of the current website:
     * curl -X PUT local.reliv.com:3000/api/rcm/site/current/domain \
     * --data '{"host":"bob.local.reliv.com"}' \
     * --header "Content-Type: application/json;Cookie: rel" \
     * --header "Cookie: reliv_session_id_local=HUMAN_PUT_YOUR_COOKIE_VALUE_HERE"
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return ResponseInterface|JsonResponse
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        /** @oldControllerAclAccessCheckReplacedWithDeeperSecureRepoCheck */

        $body = $request->getParsedBody();

        if (!isset($body['host'])) {
            return new JsonResponse(['error' => '"host" field is required'], 400);
        }
        try {
            $this->siteSecureRepo->changeSiteDomainName($this->currentSite, $body['host']);
        } catch (NotAllowedException $e) {
            return $this->buildNotFoundOrAccessDeniedResponse();
        }

        return new JsonResponse(['host' => $this->currentSite->getDomain()->getDomainName()]);
    }

    protected function buildNotFoundOrAccessDeniedResponse()
    {
        return new JsonResponse(['errorMessage' => 'site not found', 404]);
    }
}
