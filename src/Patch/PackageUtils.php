<?php
namespace Vaimo\ComposerPatches\Patch;

class PackageUtils
{
    public function shouldReinstall($package, $patches)
    {
        $extra = $package->getExtra();

        if (!isset($extra['patches_applied'])) {
            return false;
        }

        if (!$applied = $extra['patches_applied']) {
            return false;
        }

        if (!array_diff_assoc($applied, $patches) && !array_diff_assoc($patches, $applied)) {
            return false;
        }

        return true;
    }

    public function hasPatchChanges($package, $patches)
    {
        $extra = $package->getExtra();

        if (isset($extra['patches_applied'])) {
            $appliedPatches = $extra['patches_applied'];

            if (!array_diff_assoc($appliedPatches, $patches)
                && !array_diff_assoc($patches, $appliedPatches)
            ) {
                return false;
            }
        }

        return (bool)count($patches);
    }

    public function resetAppliedPatches($package)
    {
        $extra = $package->getExtra();

        unset($extra['patches_applied']);

        $package->setExtra($extra);

        return true;
    }
}