<?php

declare(strict_types=1);

namespace App\Twig;

use Composer\InstalledVersions;

/**
 * @SuppressWarnings("PHPMD.StaticAccess")
 */
class ComposerVersion
{
    public function getVersion(): string
    {
        return InstalledVersions::getRootPackage()['pretty_version'];
    }

    public function __toString(): string
    {
        return sprintf('v%s', $this->getVersion());
    }
}
