<?php

namespace Rcm\Block\Instance;

use Doctrine\ORM\EntityManager;
use Rcm\Core\Repository\AbstractRepository;
use Rcm\Entity\PluginInstance;

/**
 * @GammaRelease
 * Class BlockRepository
 *
 * @author    James Jervis
 * @license   License.txt
 * @link      https://github.com/jerv13
 */
class InstanceRepositoryBc extends AbstractRepository implements InstanceRepository
{
    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $pluginInstanceRepository;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->pluginInstanceRepository = $entityManager->getRepository(PluginInstance::class);
    }

    /**
     * getNew
     *
     * @param $id
     * @param $config
     * @param $data
     *
     * @return Instance
     */
    public function getNew($id, $config, $data)
    {
        return new InstanceBasic($id, $config, $data);
    }

    /**
     * findById
     *
     * @param $id
     *
     * @return null|Instance
     */
    public function findById($id)
    {
        /** @var PluginInstance $pluginInstance */
        $pluginInstance = $this->pluginInstanceRepository->find($id);

        if (empty($pluginInstance)) {
            return null;
        }

        $config = $pluginInstance->getInstanceConfig();

        $blockInstance = $this->getNew(
            $id,
            $config,
            []
        );

        return $blockInstance;
    }
}