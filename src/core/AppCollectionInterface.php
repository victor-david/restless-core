<?php declare(strict_types=1);
namespace Restless\Core;

/**
 * Describes an instance that supports application definitions
 */
interface AppCollectionInterface
{
    /**
     * Gets a collection of applications.
     */
    public function getAppCollection() : ?AppCollection;
}
?>