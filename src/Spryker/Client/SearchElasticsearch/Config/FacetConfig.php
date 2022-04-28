<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SearchElasticsearch\Config;

use Generated\Shared\Transfer\FacetConfigTransfer;

class FacetConfig implements FacetConfigInterface
{
    /**
     * @var array<\Generated\Shared\Transfer\FacetConfigTransfer>
     */
    protected $facetConfigTransfers = [];

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     *
     * @return $this
     */
    public function addFacet(FacetConfigTransfer $facetConfigTransfer)
    {
        $this->assertFacetConfigTransfer($facetConfigTransfer);

        $this->facetConfigTransfers[$facetConfigTransfer->getName()] = $facetConfigTransfer;

        return $this;
    }

    /**
     * @param string $facetName
     *
     * @return \Generated\Shared\Transfer\FacetConfigTransfer|null
     */
    public function get(string $facetName): ?FacetConfigTransfer
    {
        return $this->facetConfigTransfers[$facetName] ?? null;
    }

    /**
     * @return array<\Generated\Shared\Transfer\FacetConfigTransfer>
     */
    public function getAll(): array
    {
        return $this->facetConfigTransfers;
    }

    /**
     * @return array<string>
     */
    public function getParamNames(): array
    {
        return array_keys($this->facetConfigTransfers);
    }

    /**
     * @param array<string, mixed> $requestParameters
     *
     * @return array<\Generated\Shared\Transfer\FacetConfigTransfer>
     */
    public function getActive(array $requestParameters): array
    {
        $activeFacets = [];

        foreach ($this->facetConfigTransfers as $facetName => $facetConfigTransfer) {
            if (array_key_exists($facetConfigTransfer->getParameterName(), $requestParameters)) {
                $activeFacets[$facetName] = $facetConfigTransfer;
            }
        }

        return $activeFacets;
    }

    /**
     * @param array<string, mixed> $requestParameters
     *
     * @return array<string>
     */
    public function getActiveParamNames(array $requestParameters): array
    {
        return array_keys($this->getActive($requestParameters));
    }

    /**
     * @param \Generated\Shared\Transfer\FacetConfigTransfer $facetConfigTransfer
     *
     * @return void
     */
    protected function assertFacetConfigTransfer(FacetConfigTransfer $facetConfigTransfer): void
    {
        $facetConfigTransfer
            ->requireName()
            ->requireFieldName()
            ->requireParameterName()
            ->requireType();
    }
}
