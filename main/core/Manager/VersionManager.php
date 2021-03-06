<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\CoreBundle\Manager;

use Claroline\AppBundle\Persistence\ObjectManager;
use Claroline\BundleRecorder\Log\LoggableTrait;
use Claroline\CoreBundle\Entity\Update\Version;
use Claroline\CoreBundle\Library\PluginBundleInterface;
use Claroline\CoreBundle\Repository\VersionRepository;
use Claroline\InstallationBundle\Bundle\InstallableInterface;
use Composer\Json\JsonFile;
use Composer\Repository\InstalledFilesystemRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class VersionManager
{
    use LoggableTrait;

    /** @var ObjectManager */
    private $om;
    /** @var VersionRepository */
    private $repo;
    /** @var string */
    private $installedRepoFile;

    /**
     * VersionManager constructor.
     *
     * @param ObjectManager $om
     * @param string        $kernelDir
     */
    public function __construct(
        ObjectManager $om,
        $kernelDir
    ) {
        $this->om = $om;
        $this->repo = $this->om->getRepository('ClarolineCoreBundle:Update\Version');
        $this->installedRepoFile = $kernelDir.'/../vendor/composer/installed.json';
    }

    public function register(InstallableInterface $bundle)
    {
        $data = $this->getVersionFile();

        /** @var Version $version */
        $version = $this->repo->findOneBy(['version' => $data[0], 'bundle' => $bundle->getBundleFQCN()]);

        if (!empty($version)) {
            $this->log(
                sprintf('Version "%s" of "%s" already registered !', trim($version->getVersion()), $version->getBundle()),
                LogLevel::ERROR
            );

            return $version;
        }

        $this->log("Registering {$bundle->getBundleFQCN()} version {$data[0]}");
        $version = new Version($data[0], $data[1], $data[2], $bundle->getBundleFQCN());
        $this->om->persist($version);
        $this->om->flush();

        return $version;
    }

    public function execute(Version $version)
    {
        $version->setIsUpgraded(true);
        $this->om->persist($version);
        $this->om->flush();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    public function getCurrent()
    {
        return $this->getVersionFile()[0];
    }

    /**
     * @param string $bundle
     */
    public function getLatestUpgraded($bundle)
    {
        $fqcn = $bundle instanceof PluginBundleInterface ? $bundle->getBundleFQCN() : $bundle;

        try {
            return $this->repo->getLatestExecuted($fqcn);
        } catch (\Exception $e) {
            //table is not here yet if version < 10
            return null;
        }
    }

    public function getVersionFile()
    {
        $data = file_get_contents($this->getDistributionVersionFilePAth());

        return explode("\n", $data);
    }

    public function getDistributionVersion()
    {
        return trim($this->getVersionFile()[0]);
    }

    public function getDistributionVersionFilePAth()
    {
        return __DIR__.'/../../../VERSION.txt';
    }

    /**
     * @param string $repoFile
     * @param bool   $filter
     *
     * @return InstalledFilesystemRepository
     */
    public function openRepository($repoFile, $filter = true)
    {
        $json = new JsonFile($repoFile);

        if (!$json->exists()) {
            throw new \RuntimeException(
               "'{$repoFile}' must be writable",
               456 // this code is there for unit testing only
            );
        }

        $repo = new InstalledFilesystemRepository($json);

        if ($filter) {
            foreach ($repo->getPackages() as $package) {
                if ('claroline-core' !== $package->getType()
                    && 'claroline-plugin' !== $package->getType()) {
                    $repo->removePackage($package);
                }
            }
        }

        return $repo;
    }
}
